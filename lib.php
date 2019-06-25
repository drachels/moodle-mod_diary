<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This page opens the current lib instance of diary.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 * @param object $diary Object containing required diary properties
 * @return int diary ID
 */
function diary_add_instance($diary) {
    global $DB;

    $diary->timemodified = time();
    $diary->id = $DB->insert_record("diary", $diary);

    diary_grade_item_update($diary);

    return $diary->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 * @param object $diary Object containing required diary properties
 * @return boolean True if successful
 */
function diary_update_instance($diary) {
    global $DB;

    $diary->timemodified = time();
    $diary->id = $diary->instance;

    $result = $DB->update_record("diary", $diary);

    diary_grade_item_update($diary);

    diary_update_grades($diary, 0, false);

    return $result;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 * @param int $id diary ID
 * @return boolean True if successful
 */
function diary_delete_instance($id) {
    global $DB;

    $result = true;

    if (! $diary = $DB->get_record("diary", array("id" => $id))) {
        return false;
    }

    if (! $DB->delete_records("diary_entries", array("diary" => $diary->id))) {
        $result = false;
    }

    if (! $DB->delete_records("diary", array("id" => $diary->id))) {
        $result = false;
    }

    return $result;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function diary_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function diary_get_view_actions() {
    return array('view', 'view all', 'view responses');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function diary_get_post_actions() {
    return array('add entry', 'update entry', 'update feedback');
}

/**
 * Returns a summary of data activity of this user.
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $data
 * @return object|null
 */
function diary_user_outline($course, $user, $mod, $diary) {
    global $DB;

    if ($entry = $DB->get_record("diary_entries", array("userid" => $user->id, "diary" => $diary->id))) {

        $numwords = count(preg_split("/\w\b/", $entry->text)) - 1;

        $result = new stdClass();
        $result->info = get_string("numwords", "", $numwords);
        $result->time = $entry->timemodified;
        return $result;
    }
    return null;
}

/**
 * Prints all the records uploaded by this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $data
 */
function diary_user_complete($course, $user, $mod, $diary) {
    global $DB, $OUTPUT;

    if ($entry = $DB->get_record("diary_entries", array("userid" => $user->id, "diary" => $diary->id))) {

        echo $OUTPUT->box_start();

        if ($entry->timemodified) {
            echo "<p><font size=\"1\">".get_string("lastedited").": ".userdate($entry->timemodified)."</font></p>";
        }
        if ($entry->text) {
            // echo format_text($entry->text, $entry->format);
            echo diary_format_entry_text($entry, $course, $mod);
        }
        if ($entry->teacher) {
            $grades = make_grades_menu($diary->grade);
            diary_print_feedback($course, $entry, $grades);
        }

        echo $OUTPUT->box_end();

    } else {
        print_string("noentry", "diary");
    }
}

/**
 * Function to be run periodically according to the moodle cron.
 * Finds all diary notifications that have yet to be mailed out, and mails them.
 */
function diary_cron() {
    global $CFG, $USER, $DB;

    $cutofftime = time() - $CFG->maxeditingtime;

    if ($entries = diary_get_unmailed_graded($cutofftime)) {
        $timenow = time();

        $usernamefields = get_all_user_name_fields();
        $requireduserfields = 'id, auth, mnethostid, email, mailformat, maildisplay, lang, deleted, suspended, '.implode(', ', $usernamefields);

        // To save some db queries.
        $users = array();
        $courses = array();

        foreach ($entries as $entry) {

            echo "Processing diary entry $entry->id\n";

            if (!empty($users[$entry->userid])) {
                $user = $users[$entry->userid];
            } else {
                if (!$user = $DB->get_record("user", array("id" => $entry->userid), $requireduserfields)) {
                    echo "Could not find user $entry->userid\n";
                    continue;
                }
                $users[$entry->userid] = $user;
            }

            $USER->lang = $user->lang;

            if (!empty($courses[$entry->course])) {
                $course = $courses[$entry->course];
            } else {
                if (!$course = $DB->get_record('course', array('id' => $entry->course), 'id, shortname')) {
                    echo "Could not find course $entry->course\n";
                    continue;
                }
                $courses[$entry->course] = $course;
            }

            if (!empty($users[$entry->teacher])) {
                $teacher = $users[$entry->teacher];
            } else {
                if (!$teacher = $DB->get_record("user", array("id" => $entry->teacher), $requireduserfields)) {
                    echo "Could not find teacher $entry->teacher\n";
                    continue;
                }
                $users[$entry->teacher] = $teacher;
            }

            // All cached.
            $coursediarys = get_fast_modinfo($course)->get_instances_of('diary');
            if (empty($coursediarys) || empty($coursediarys[$entry->diary])) {
                echo "Could not find course module for diary id $entry->diary\n";
                continue;
            }
            $mod = $coursediarys[$entry->diary];

            // This is already cached internally.
            $context = context_module::instance($mod->id);
            $canadd = has_capability('mod/diary:addentries', $context, $user);
            $entriesmanager = has_capability('mod/diary:manageentries', $context, $user);

            if (!$canadd and $entriesmanager) {
                continue;  // Not an active participant.
            }

            $diaryinfo = new stdClass();
            $diaryinfo->teacher = fullname($teacher);
            $diaryinfo->diary = format_string($entry->name, true);
            $diaryinfo->url = "$CFG->wwwroot/mod/diary/view.php?id=$mod->id";
            $modnamepl = get_string( 'modulenameplural', 'diary' );
            $msubject = get_string( 'mailsubject', 'diary' );

            $postsubject = "$course->shortname: $msubject: ".format_string($entry->name, true);
            $posttext  = "$course->shortname -> $modnamepl -> ".format_string($entry->name, true)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("diarymail", "diary", $diaryinfo)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            if ($user->mailformat == 1) {  // HTML.
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                "<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$course->id\">diarys</a> ->".
                "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$mod->id\">".format_string($entry->name, true)."</a></font></p>";
                $posthtml .= "<hr /><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("diarymailhtml", "diary", $diaryinfo)."</p>";
                $posthtml .= "</font><hr />";
            } else {
                $posthtml = "";
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo "Error: Diary cron: Could not send out mail for id $entry->id to user $user->id ($user->email)\n";
            }
            if (!$DB->set_field("diary_entries", "mailed", "1", array("id" => $entry->id))) {
                echo "Could not update the mailed field for id $entry->id\n";
            }
        }
    }

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in diary activities and print it out.
 * Return true if there was output, or false if there was none.
 *
 * @global stdClass $DB
 * @global stdClass $OUTPUT
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return bool
 */
function diary_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    if (!get_config('diary', 'showrecentactivity')) {
        return false;
    }

    $dbparams = array($timestart, $course->id, 'diary');
    $namefields = user_picture::fields('u', null, 'userid');
    $sql = "SELECT de.id, de.timemodified, cm.id AS cmid, $namefields
         FROM {diary_entries} de
              JOIN {diary} d         ON d.id = de.diary
              JOIN {course_modules} cm ON cm.instance = d.id
              JOIN {modules} md        ON md.id = cm.module
              JOIN {user} u            ON u.id = de.userid
         WHERE de.timemodified > ? AND
               d.course = ? AND
               md.name = ?
         ORDER BY u.lastname ASC, u.firstname ASC
    ";
    // Changed on 06/22/2019 original line 310: ORDER BY de.timemodified ASC
    $newentries = $DB->get_records_sql($sql, $dbparams);

    $modinfo = get_fast_modinfo($course);

    $show    = array();

    foreach ($newentries as $anentry) {
        if (!array_key_exists($anentry->cmid, $modinfo->get_cms())) {
            continue;
        }
        $cm = $modinfo->get_cm($anentry->cmid);

        if (!$cm->uservisible) {
            continue;
        }
        if ($anentry->userid == $USER->id) {
            $show[] = $anentry;
            continue;
        }
        $context = context_module::instance($anentry->cmid);

        // Only teachers can see other students entries.
        if (!has_capability('mod/diary:manageentries', $context)) {
            continue;
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups',  $context)) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups = groups_get_all_groups($course->id, $anentry->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $anentry;
    }

    if (empty($show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newdiaryentries', 'diary').':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->get_cm($submission->cmid);
        $context = context_module::instance($submission->cmid);
        if (has_capability('mod/diary:manageentries', $context)) {
            $link = $CFG->wwwroot.'/mod/diary/report.php?id='.$cm->id;
        } else {
            $link = $CFG->wwwroot.'/mod/diary/view.php?id='.$cm->id;
        }
        print_recent_activity_note($submission->timemodified,
                                   $submission,
                                   $cm->name,
                                   $link,
                                   false,
                                   $viewfullnames);
    }
    return true;
}
/**
 * Returns the users with data in one diary
 * (users with records in diary_entries, students and teachers)
 * @param int $diaryid diary ID
 * @return array Array of user ids
 */
function diary_get_participants($diaryid) {
    global $DB;

    // Get students.
    $students = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {diary_entries} d
                                      WHERE d.diary = '$diaryid' and
                                      u.id = d.userid");
    // Get teachers.
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {diary_entries} d
                                      WHERE d.diary = '$diaryid' and
                                      u.id = d.teacher");

    // Add teachers to students.
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    // Return students array, (it contains an array of unique users).
    return ($students);
}

/**
 * This function returns true if a scale is being used by one diary
 * @param int $diaryid diary ID
 * @param int $scaleid Scale ID
 * @return boolean True if a scale is being used by one diary
 */
function diary_scale_used ($diaryid, $scaleid) {
    global $DB;
    $return = false;

    $rec = $DB->get_record("diary", array("id" => $diaryid, "grade" => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of diary
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any diary
 */
function diary_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->get_records('diary', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the diary.
 *
 * @param object $mform form passed by reference
 */
function diary_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'diaryheader', get_string('modulenameplural', 'diary'));
    $mform->addElement('advcheckbox', 'reset_diary', get_string('removemessages', 'diary'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function diary_reset_course_form_defaults($course) {
    return array('reset_diary' => 1);
}

/**
 * Removes all entries
 *
 * @param object $data
 */
function diary_reset_userdata($data) {

    global $CFG, $DB;

    $status = array();
    if (!empty($data->reset_diary)) {

        $sql = "SELECT d.id
                FROM {diary} d
                WHERE d.course = ?";
        $params = array($data->courseid);

        $DB->delete_records_select('diary_entries', "diary IN ($sql)", $params);

        $status[] = array('component' => get_string('modulenameplural', 'diary'),
                          'item' => get_string('removeentries', 'diary'),
                          'error' => false);
    }

    return $status;
}

/**
 * Print diary overview
 *
 * @param object   $courses
 * @param boolean  $nullifnone   return null if grade does not exist
 */
function diary_print_overview($courses, &$htmlarray) {

    global $USER, $CFG, $DB;

    if (!get_config('diary', 'overview')) {
        return array();
    }

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$diarys = get_all_instances_in_courses('diary', $courses)) {
        return array();
    }

    $strdiary = get_string('modulename', 'diary');

    $timenow = time();
    foreach ($diarys as $diary) {

        if (empty($courses[$diary->course]->format)) {
            $courses[$diary->course]->format = $DB->get_field('course', 'format', array('id' => $diary->course));
        }

        if ($courses[$diary->course]->format == 'weeks' AND $diary->days) {

            $coursestartdate = $courses[$diary->course]->startdate;

            $diary->timestart  = $coursestartdate + (($diary->section - 1) * 608400);
            if (!empty($diary->days)) {
                $diary->timefinish = $diary->timestart + (3600 * 24 * $diary->days);
            } else {
                $diary->timefinish = 9999999999;
            }
            $diaryopen = ($diary->timestart < $timenow && $timenow < $diary->timefinish);

        } else {
            $diaryopen = true;
        }

        if ($diaryopen) {
            $str = '<div class="diary overview"><div class="name">'.
                   $strdiary.': <a '.($diary->visible ? '' : ' class="dimmed"').
                   ' href="'.$CFG->wwwroot.'/mod/diary/view.php?id='.$diary->coursemodule.'">'.
                   $diary->name.'</a></div></div>';

            if (empty($htmlarray[$diary->course]['diary'])) {
                $htmlarray[$diary->course]['diary'] = $str;
            } else {
                $htmlarray[$diary->course]['diary'] .= $str;
            }
        }
    }
}

/**
 * Get diary grades for a user.
 *
 * @param object   $diary        if is null, all diarys
 * @param int      $userid       if is false al users
 * @param boolean  $nullifnone   return null if grade does not exist
 */
function diary_get_user_grades($diary, $userid=0) {

    global $DB;

    $params = array();
    if ($userid) {
        $userstr = 'AND userid = :uid';
        $params['uid'] = $userid;
    } else {
        $userstr = '';
    }

    if (!$diary) {
        return false;

    } else {

        $sql = "SELECT DISTINCT userid, timemodified as datesubmitted, format as feedbackformat,
                rating as rawgrade, entrycomment as feedback, teacher as usermodifier, timemarked as dategraded
                FROM {diary_entries}
                WHERE diary = :did ".$userstr;
        $params['did'] = $diary->id;

        $grades = $DB->get_records_sql($sql, $params);

        if ($grades) {
            foreach ($grades as $key => $grade) {
                $grades[$key]->id = $grade->userid;
            }
        } else {
            return false;
        }

        return $grades;
    }

}

/**
 * Update diary grades in 1.9 gradebook
 *
 * @param object   $diary        if is null, all diarys
 * @param int      $userid       if is false al users
 * @param boolean  $nullifnone   return null if grade does not exist
 */
function diary_update_grades($diary=null, $userid=0, $nullifnone=true) {

    global $CFG, $DB;

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($diary != null) {
        if ($grades = diary_get_user_grades($diary, $userid)) {
            diary_grade_item_update($diary, $grades);
        } else if ($userid && $nullifnone) {
            $grade = new stdClass();
            $grade->userid   = $userid;
            $grade->rawgrade = null;
            diary_grade_item_update($diary, $grade);
        } else {
            diary_grade_item_update($diary);
        }
    } else {
        $sql = "SELECT d.*, cm.idnumber as cmidnumber
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {diary} d ON cm.instance = d.id
                WHERE m.name = 'diary'";
        if ($recordset = $DB->get_records_sql($sql)) {
            foreach ($recordset as $diary) {
                if ($diary->grade != false) {
                    diary_update_grades($diary);
                } else {
                    diary_grade_item_update($diary);
                }
            }
        }
    }
}


/**
 * Create grade item for given diary
 *
 * @param object $diary object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function diary_grade_item_update($diary, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $diary)) {
        $params = array('itemname' => $diary->name, 'idnumber' => $diary->cmidnumber);
    } else {
        $params = array('itemname' => $diary->name);
    }

    if ($diary->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $diary->grade;
        $params['grademin']   = 0;
        $params['multfactor'] = 1.0;

    } else if ($diary->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$diary->grade;

    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
        $params['multfactor'] = 1.0;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/diary', $diary->course, 'mod', 'diary', $diary->id, 0, $grades, $params);
}

/**
 * Delete grade item for given diary
 *
 * @param   object   $diary
 * @return  object   grade_item
 */
function diary_grade_item_delete($diary) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/diary', $diary->course, 'mod', 'diary', $diary->id, 0, null, array('deleted' => 1));
}

/**
 * Return only the users that have entries in the specified diary activity.
 *
 * return object $diarys
 */
function diary_get_users_done($diary, $currentgroup) {
    global $DB;

    $params = array();

    $sql = "SELECT DISTINCT u.* FROM {diary_entries} de
            JOIN {user} u ON de.userid = u.id ";

    // Group users.
    if ($currentgroup != 0) {
        $sql .= "JOIN {groups_members} gm ON gm.userid = u.id AND gm.groupid = ?";
        $params[] = $currentgroup;
    }
    // The old version of this line puts users with new entries at the bottom of report.
    // However, with DESC, newest entries are at the top, except for admin?
    // $sql .= " WHERE de.diary = ? ORDER BY de.timemodified DESC";

    // Modified 06/15/2019 to give alphabetical listing on report.php page.
    $sql .= " WHERE de.diary = ? ORDER BY u.lastname ASC, u.firstname ASC";

    $params[] = $diary->id;
    $diarys = $DB->get_records_sql($sql, $params);

    $cm = diary_get_coursemodule($diary->id);
    if (!$diarys || !$cm) {
        return null;
    }

    // Remove unenrolled participants.
    foreach ($diarys as $key => $user) {

        $context = context_module::instance($cm->id);

        $canadd = has_capability('mod/diary:addentries', $context, $user);
        $entriesmanager = has_capability('mod/diary:manageentries', $context, $user);

        if (!$entriesmanager and !$canadd) {
            unset($diarys[$key]);
        }
    }
    return $diarys;
}

/**
 * Counts all the diary entries (optionally in a given group).
 *
 *
 */
function diary_count_entries($diary, $groupid = 0) {
    global $DB;

    $cm = diary_get_coursemodule($diary->id);
    $context = context_module::instance($cm->id);

    if ($groupid) {     // How many in a particular group?

        $sql = "SELECT DISTINCT u.id FROM {diary_entries} d
                JOIN {groups_members} g ON g.userid = d.userid
                JOIN {user} u ON u.id = g.userid
                WHERE d.diary = ? AND g.groupid = ?";
        $diarys = $DB->get_records_sql($sql, array($diary->id, $groupid));

    } else { // Count all the entries from the whole course.

        $sql = "SELECT DISTINCT u.id FROM {diary_entries} d
                JOIN {user} u ON u.id = d.userid
                WHERE d.diary = ?";
        $diarys = $DB->get_records_sql($sql, array($diary->id));
    }

    if (!$diarys) {
        return 0;
    }

    $canadd = get_users_by_capability($context, 'mod/diary:addentries', 'u.id');
    $entriesmanager = get_users_by_capability($context, 'mod/diary:manageentries', 'u.id');

    // Remove unenrolled participants.
    foreach ($diarys as $userid => $notused) {

        if (!isset($entriesmanager[$userid]) && !isset($canadd[$userid])) {
            unset($diarys[$userid]);
        }
    }

    return count($diarys);
}

/**
 * Return entries that have not been emailed.
 *
 * return
 */
function diary_get_unmailed_graded($cutofftime) {
    global $DB;

    $sql = "SELECT de.*, d.course, d.name FROM {diary_entries} de
            JOIN {diary} d ON de.diary = d.id
            WHERE de.mailed = '0' AND de.timemarked < ? AND de.timemarked > 0";
    return $DB->get_records_sql($sql, array($cutofftime));
}

/**
 * Return diary log info.
 *
 * return
 */
function diary_log_info($log) {
    global $DB;

    $sql = "SELECT d.*, u.firstname, u.lastname
            FROM {diary} d
            JOIN {diary_entries} de ON de.diary = d.id
            JOIN {user} u ON u.id = de.userid
            WHERE de.id = ?";
    return $DB->get_record_sql($sql, array($log->info));
}

/**
 * Returns the diary instance course_module id
 *
 * @param integer $diary
 * @return object
 */
function diary_get_coursemodule($diaryid) {

    global $DB;

    return $DB->get_record_sql("SELECT cm.id FROM {course_modules} cm
                                JOIN {modules} m ON m.id = cm.module
                                WHERE cm.instance = ? AND m.name = 'diary'", array($diaryid));
}

/**
 * Serves the diary files.
 *
 * @package  mod_diary
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function diary_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $context)) {
        return false;
    }

    // Args[0] should be the entry id.
    $entryid = intval(array_shift($args));
    $entry = $DB->get_record('diary_entries', array('id' => $entryid), 'id, userid', MUST_EXIST);

    $canmanage = has_capability('mod/diary:manageentries', $context);
    if (!$canmanage && !has_capability('mod/diary:addentries', $context)) {
        // Even if it is your own entry.
        return false;
    }

    // Students can only see their own entry.
    if (!$canmanage && $USER->id !== $entry->userid) {
        return false;
    }

    if ($filearea !== 'entry') {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_diary/$filearea/$entryid/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath));

    // Finally send the file.
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Return formatted text.
 *
 * return
 */
function diary_format_entry_text($entry, $course = false, $cm = false) {

    if (!$cm) {
        if ($course) {
            $courseid = $course->id;
        } else {
            $courseid = 0;
        }
        $cm = get_coursemodule_from_instance('diary', $entry->diary, $courseid);
    }

    $context = context_module::instance($cm->id);
    $entrytext = file_rewrite_pluginfile_urls($entry->text, 'pluginfile.php', $context->id, 'mod_diary', 'entry', $entry->id);

    $formatoptions = array(
        'context' => $context,
        'noclean' => false,
        'trusted' => false
    );
    return format_text($entrytext, $entry->format, $formatoptions);
}

/**
 * Prints the currently selected diary entry of student identified as $user.
 *
 * @param integer $course
 * @param integer $user
 * @param integer $entry
 * @param integer $teachers
 * @param integer $grades
 */
function diary_print_user_entry($course, $user, $entry, $teachers, $grades) {

    global $USER, $OUTPUT, $DB, $CFG;

    require_once($CFG->dirroot.'/lib/gradelib.php');
    $dcolor3 = get_config('mod_diary', 'entrybgc');
    $dcolor4 = get_config('mod_diary', 'entrytextbgc');

    echo "\n<table class=\"diaryuserentry\" id=\"entry-" . $user->id . "\" bgcolor=\"$dcolor4\">";

    echo "\n<tr>";
    echo "\n<td class=\"userpix\" rowspan=\"2\">";
    echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
    echo "</td>";
    echo "<td class=\"userfullname\">".fullname($user);
    if ($entry) {
        echo " <span class=\"lastedit\">".get_string("lastedited").": ".userdate($entry->timemodified)." </span>";
    }

    echo "</td>";
    echo "</tr>";

    echo "\n<tr><td>";

    // If there is a user entry, format it and show it.
    if ($entry) {
        echo diary_format_entry_text($entry, $course);
    } else {
        print_string("noentry", "diary");
    }
    echo "</td></tr>";

    // If there is a user entry, add a teacher feedback area for grade and comments. Add previous grades and comments, if available.
    if ($entry) {
        echo "\n<tr>";
        echo "<td class=\"userpix\">";
        if (!$entry->teacher) {
            $entry->teacher = $USER->id;
        }
        if (empty($teachers[$entry->teacher])) {
            $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
        }
        echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id, 'alttext' => true));
        echo "</td>";
        echo "<td>".get_string("feedback").":";

        $attrs = array();
        $hiddengradestr = '';
        $gradebookgradestr = '';
        $feedbackdisabledstr = '';
        $feedbacktext = $entry->entrycomment;

        // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));
        if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
            if ($gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked || $gradinginfo->items[0]->grades[$user->id]->overridden) {
                $attrs['disabled'] = 'disabled';
                $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
                $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
                $gradebooklink .= $gradinginfo->items[0]->grades[$user->id]->str_long_grade.'</a>';
                $gradebookgradestr = '<br/>'.get_string("gradeingradebook", "diary").':&nbsp;'.$gradebooklink;

                $feedbackdisabledstr = 'disabled="disabled"';
                $feedbacktext = $gradinginfo->items[0]->grades[$user->id]->str_feedback;
            }
        }

        // Grade selector.
        $attrs['id'] = 'r' . $entry->id;
        echo html_writer::label(fullname($user)." ".get_string('grade'), 'r'.$entry->id, true, array('class' => 'accesshide'));
        echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
        echo $hiddengradestr;
        // Rewrote next three lines to show entry needs to be regraded due to resubmission.
        if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
            echo " <span class=\"needsedit\">".get_string("needsregrade", "diary"). " </span>";
        } else if ($entry->timemarked) {
            echo " <span class=\"lastedit\">".userdate($entry->timemarked)." </span>";
        }
        echo $gradebookgradestr;

        // Feedback text.
        echo html_writer::label(fullname($user)." ".get_string('feedback'), 'c'.$entry->id, true, array('class' => 'accesshide'));
        echo "<p><textarea id=\"c$entry->id\" name=\"c$entry->id\" rows=\"12\" cols=\"60\" $feedbackdisabledstr>";
        p($feedbacktext);
        echo "</textarea></p>";

        if ($feedbackdisabledstr != '') {
            echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
        }
        echo "</td></tr>";
    }
    echo "</table>\n";
}

/**
 * Set current entry to show.
 * @param int $entryid
 */
function set_currententry($entryid = -1) {
    global $DB;

    $entries = $DB->get_records('diary_entries', array('diary' => $this->instance->id), 'id ASC');
    if (empty($entries)) {
        // Create the first round.
        $round = new StdClass();
        $round->starttime = time();
        $round->endtime = 0;
        $round->diary = $this->instance->id;
        //$round->id = $DB->insert_record('diary_entries', $round);
        $entries[] = $round;
    }

    if ($entryid != -1 && array_key_exists($entryid, $entries)) {
        $this->currententry = $entries[$entryid];

        $ids = array_keys($entries);
        // Search previous round.
        $currentkey = array_search($entryid, $ids);
        if (array_key_exists($currentkey - 1, $ids)) {
            $this->preventry = $entries[$ids[$currentkey - 1]];
        } else {
            $this->preventry = null;
        }
        // Search next round.
        if (array_key_exists($currentkey + 1, $ids)) {
            $this->nextentry = $entries[$ids[$currentkey + 1]];
        } else {
            $this->nextentry = null;
        }
    } else {
        // Use the last round.
        $this->currententry = array_pop($entries);
        $this->preventry = array_pop($entries);
        $this->nextentry = null;
    }
    return $entryid;
}

/**
 * Download entries in this diary activity.
 *
 * @param array $array
 * @param string $filename - The filename to use.
 * @param string $delimiter - The character to use as a delimiter.
 * @return nothing
 */
//function download_entries($array, $filename = "export.csv", $delimiter=";") {
function download_entries($context, $course, $id, $diary) {
    $filename = "export.csv";
    $delimiter=";";

    global $CFG, $DB, $USER;
    require_once($CFG->libdir.'/csvlib.class.php');

    $data = new StdClass();
    $data->diary = $diary->id;
    //$context = context_module::instance($diary->id);
    // Trigger download_diary_entries event.
    $event = \mod_diary\event\download_diary_entries::create(array(
        'objectid' => $data->diary,
        'context' => $context
    ));
    $event->trigger();

    // Construct sql query and filename based on admin or teacher.
    // Add filename details based on course and Diary activity name.
    $csv = new csv_export_writer();
    $strdiary = get_string('pluginname', 'diary');
    if (is_siteadmin($USER->id)) {
        $whichdiary = ('AND d.diary > 0');
        $csv->filename = clean_filename(get_string('exportfilenamep1', 'diary'));
    } else {
        $whichdiary = ('AND d.diary = ');
        $whichdiary .= ($diary->id);
        $csv->filename = clean_filename(($course->shortname).'_');
        $csv->filename .= clean_filename(($diary->name));
    }
    $csv->filename .= clean_filename(get_string('exportfilenamep2', 'diary').gmdate("Ymd_Hi").'GMT.csv');

    $fields = array();

    $fields = array(get_string('firstname'),
                    get_string('lastname'),
                    get_string('pluginname', 'diary'),
                   // get_string('userid', 'diary'),
                    get_string('userid', 'diary'),
                    get_string('timecreated', 'diary'),
                    get_string('timemodified', 'diary'),
                    get_string('format', 'diary'),
                    get_string('rating', 'diary'),
                    get_string('entrycomment', 'diary'),
                    get_string('teacher', 'diary'),
                    get_string('timemarked', 'diary'),
                    get_string('mailed', 'diary'),
                    get_string('text', 'diary'));
    // Add the headings to our data array.
    $csv->add_data($fields);
    if ($CFG->dbtype == 'pgsql') {
        $sql = "SELECT d.id AS entry,
                       u.firstname AS firstname,
                       u.lastname AS lastname,
                       d.diary AS diary,
                       d.userid AS userid,
                       to_char(to_timestamp(d.timecreated), 'YYYY-MM-DD HH24:MI:SS') AS timecreated,
                       to_char(to_timestamp(d.timemodified), 'YYYY-MM-DD HH24:MI:SS') AS timemodified,
                       d.text AS text,
                       d.format AS format,
                       d.rating AS rating,
                       d.entrycomment AS entrycomment,
                       d.teacher AS teacher,
                       to_char(to_timestamp(d.timemarked), 'YYYY-MM-DD HH24:MI:SS') AS timemarked,
                       d.mailed AS mailed
                FROM {diary_entries} d
                JOIN {user} u ON u.id = d.userid
                WHERE d.userid > 0 ";
    } else {
        $sql = "SELECT d.id AS entry,
                       u.firstname AS 'firstname',
                        u.lastname AS 'lastname',
                        d.diary AS diary,
                        d.userid AS userid,
                        FROM_UNIXTIME(d.timecreated) AS TIMECREATED,
                        FROM_UNIXTIME(d.timemodified) AS TIMEMODIFIED,
                        d.text AS text,
                        d.format AS format,
                        d.rating AS rating,
                        d.entrycomment AS entrycomment,
                        d.teacher AS teacher,
                        FROM_UNIXTIME(d.timemarked) AS TIMEMARKED,
                        d.mailed AS mailed
                    FROM {diary_entries} d
                    JOIN {user} u ON u.id = d.userid
                    WHERE d.userid > 0 ";
    }

    $sql .= ($whichdiary);
    $sql .= "     GROUP BY u.lastname, u.firstname, d.diary, d.id
                  ORDER BY u.lastname ASC, u.firstname ASC, d.diary ASC, d.id ASC";

    // Add the list of users and diarys to our data array.
    if ($ds = $DB->get_records_sql($sql, $fields)) {
        foreach ($ds as $d) {
            $output = array($d->firstname, $d->lastname, $d->diary, $d->userid, $d->timecreated, $d->timemodified, $d->format,
            $d->rating, $d->entrycomment, $d->teacher, $d->timemarked, $d->mailed, $d->text);
            $csv->add_data($output);
        }
    }
    // Download the completed array.
    $csv->download_file();
    exit;
}