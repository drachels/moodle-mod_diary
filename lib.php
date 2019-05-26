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
 * nd any data that depends on it.
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


function diary_get_view_actions() {
    return array('view','view all','view responses');
}

function diary_get_post_actions() {
    return array('add entry','update entry','update feedback');
}

function diary_user_outline($course, $user, $mod, $diary) {
    global $DB;

    if ($entry = $DB->get_record("diary_entries", array("userid" => $user->id, "diary" => $diary->id))) {

        $numwords = count(preg_split("/\w\b/", $entry->text)) - 1;

        $result->info = get_string("numwords", "", $numwords);
        $result->time = $entry->timemodified;
        return $result;
    }
    return null;
}


function diary_user_complete($course, $user, $mod, $diary) {
    global $DB, $OUTPUT;

    if ($entry = $DB->get_record("diary_entries", array("userid" => $user->id, "diary" => $diary->id))) {

        echo $OUTPUT->box_start();

        if ($entry->timemodified) {
            echo "<p><font size=\"1\">".get_string("lastedited").": ".userdate($entry->timemodified)."</font></p>";
        }
        if ($entry->text) {
            echo format_text($entry->text, $entry->format);
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
function diary_cron () {
    global $CFG, $USER, $DB;

    $cutofftime = time() - $CFG->maxeditingtime;

    if ($entries = diary_get_unmailed_graded($cutofftime)) {
        $timenow = time();

        $usernamefields = get_all_user_name_fields();
        $requireduserfields = 'id, auth, mnethostid, email, mailformat, maildisplay, lang, deleted, suspended, ' . implode(', ', $usernamefields);

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
                continue;  // Not an active participant
            }

            $diaryinfo = new stdClass();
            $diaryinfo->teacher = fullname($teacher);
            $diaryinfo->diary = format_string($entry->name,true);
            $diaryinfo->url = "$CFG->wwwroot/mod/diary/view.php?id=$mod->id";
            $modnamepl = get_string( 'modulenameplural','diary' );
            $msubject = get_string( 'mailsubject','diary' );

            $postsubject = "$course->shortname: $msubject: ".format_string($entry->name,true);
            $posttext  = "$course->shortname -> $modnamepl -> ".format_string($entry->name,true)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("diarymail", "diary", $diaryinfo)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            if ($user->mailformat == 1) {  // HTML
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                "<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$course->id\">diarys</a> ->".
                "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$mod->id\">".format_string($entry->name,true)."</a></font></p>";
                $posthtml .= "<hr /><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("diarymailhtml", "diary", $diaryinfo)."</p>";
                $posthtml .= "</font><hr />";
            } else {
              $posthtml = "";
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo "Error: diary cron: Could not send out mail for id $entry->id to user $user->id ($user->email)\n";
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
function diary_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $DB, $OUTPUT;

    if (!get_config('diary', 'showrecentactivity')) {
        return false;
    }

    $content = false;
    $diarys = NULL;

    // log table should not be used here

    $select = "time > ? AND
               course = ? AND
               module = 'diary' AND
               (action = 'add entry' OR action = 'update entry')";
    if (!$logs = $DB->get_records_select('log', $select, array($timestart, $course->id), 'time ASC')){
        return false;
    }

    $modinfo = & get_fast_modinfo($course);
    foreach ($logs as $log) {
        ///Get diary info.  I'll need it later
        $j_log_info = diary_log_info($log);

        $cm = $modinfo->instances['diary'][$j_log_info->id];
        if (!$cm->uservisible) {
            continue;
        }

        if (!isset($diarys[$log->info])) {
            $diarys[$log->info] = $j_log_info;
            $diarys[$log->info]->time = $log->time;
            $diarys[$log->info]->url = str_replace('&', '&amp;', $log->url);
        }
    }

    if ($diarys) {
        $content = true;
        echo $OUTPUT->heading(get_string('newdiaryentries', 'diary').':', 3);
        foreach ($diarys as $diary) {
            print_recent_activity_note($diary->time, $diary, $diary->name,
                                       $CFG->wwwroot.'/mod/diary/'.$diary->url);
        }
    }

    return $content;
}

/**
 * Returns the users with data in one diary
 * (users with records in diary_entries, students and teachers)
 * @param int $diaryid diary ID
 * @return array Array of user ids
 */
function diary_get_participants($diaryid) {
    global $DB;

    //Get students
    $students = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {diary_entries} d
                                      WHERE d.diary = '$diaryid' and
                                      u.id = d.userid");
    //Get teachers
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {diary_entries} d
                                      WHERE d.diary = '$diaryid' and
                                      u.id = d.teacher");

    //Add teachers to students
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    //Return students array (it contains an array of unique users)
    return ($students);
}

/**
 * This function returns true if a scale is being used by one diary
 * @param int $diaryid diary ID
 * @param int $scaleid Scale ID
 * @return boolean True if a scale is being used by one diary
 */
function diary_scale_used ($diaryid,$scaleid) {
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
    $mform->addElement('advcheckbox', 'reset_diary', get_string('removemessages','diary'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function diary_reset_course_form_defaults($course) {
    return array('reset_diary'=>1);
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
                   $strdiary.': <a '.($diary->visible?'':' class="dimmed"').
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

function diary_get_user_grades($diary, $userid=0) {

    global $DB;

    if ($userid) {
        $userstr = 'AND userid = '.$userid;
    } else {
        $userstr = '';
    }

    if (!$diary) {
        return false;

    } else {

        $sql = "SELECT userid, timemodified as datesubmitted, format as feedbackformat,
                rating as rawgrade, entrycomment as feedback, teacher as usermodifier, timemarked as dategraded
                FROM {diary_entries}
                WHERE diary = '$diary->id' ".$userstr;

        $grades = $DB->get_records_sql($sql);

        if ($grades) {
            foreach ($grades as $key=>$grade) {
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
 * @param object   $diary      if is null, all diarys
 * @param int      $userid       if is false al users
 * @param boolean  $nullifnone   return null if grade does not exist
 */
function diary_update_grades($diary=null, $userid=0, $nullifnone=true) {

    global $CFG, $DB;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($diary != null) {
        if ($grades = diary_get_user_grades($diary, $userid)) {
            diary_grade_item_update($diary, $grades);
        } else if ($userid && $nullifnone) {
            $grade = new object();
            $grade->userid   = $userid;
            $grade->rawgrade = NULL;
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
function diary_grade_item_update($diary, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $diary)) {
        $params = array('itemname'=>$diary->name, 'idnumber'=>$diary->cmidnumber);
    } else {
        $params = array('itemname'=>$diary->name);
    }

    if ($diary->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $diary->grade;
        $params['grademin']   = 0;
        $params['multfactor'] = 1.0;

    } else if($diary->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$diary->grade;

    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
        $params['multfactor'] = 1.0;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
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

    return grade_update('mod/diary', $diary->course, 'mod', 'diary', $diary->id, 0, NULL, array('deleted'=>1));
}


function diary_get_users_done($diary, $currentgroup) {
    global $DB;
//print_object($diary);
//print_object($currentgroup);
    $params = array();

    $sql = "SELECT DISTINCT u.* FROM {diary_entries} de
            JOIN {user} u ON de.userid = u.id ";

    // Group users
    if ($currentgroup != 0) {
        $sql.= "JOIN {groups_members} gm ON gm.userid = u.id AND gm.groupid = ?";
        $params[] = $currentgroup;
    }

    $sql.= " WHERE de.diary = ? ORDER BY de.timemodified DESC";
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
 * Counts all the journal entries (optionally in a given group).
 */
function diary_count_entries($diary, $groupid = 0) {
    global $DB;

    $cm = diary_get_coursemodule($diary->id);
    $context = context_module::instance($cm->id);

    if ($groupid) {     // How many in a particular group?

        $sql = "SELECT DISTINCT u.id FROM {diary_entries} d
                JOIN {groups_members} g ON g.userid = d.userid
                JOIN {user} u ON u.id = g.userid
                WHERE d.diary = $diary->id AND g.groupid = '$groupid'";
        $diarys = $DB->get_records_sql($sql);

    } else { // Count all the entries from the whole course

        $sql = "SELECT DISTINCT u.id FROM {diary_entries} d
                JOIN {user} u ON u.id = d.userid
                WHERE d.diary = '$diary->id'";
        $diarys = $DB->get_records_sql($sql);
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

function diary_get_unmailed_graded($cutofftime) {
    global $DB;

    $sql = "SELECT de.*, d.course, d.name FROM {diary_entries} de
            JOIN {diary} d ON de.diary = d.id
            WHERE de.mailed = '0' AND de.timemarked < '$cutofftime' AND de.timemarked > 0";
    return $DB->get_records_sql($sql);
}

function diary_log_info($log) {
    global $DB;

    $sql = "SELECT d.*, u.firstname, u.lastname
            FROM {diary} d
            JOIN {diary_entries} de ON de.diary = d.id
            JOIN {user} u ON u.id = de.userid
            WHERE de.id = '$log->info'";
    return $DB->get_record_sql($sql);
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
                                WHERE cm.instance = '$diaryid' AND m.name = 'diary'");
}


// OTHER diary FUNCTIONS ///////////////////////////////////////////////////////////////////

function diary_print_user_entry($course, $user, $entry, $teachers, $grades) {

    global $USER, $OUTPUT, $DB, $CFG;

    require_once($CFG->dirroot.'/lib/gradelib.php');

    echo "\n<table class=\"diaryuserentry\">";

    echo "\n<tr>";
    echo "\n<td class=\"userpix\" rowspan=\"2\">";
    echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
    echo "</td>";
    echo "<td class=\"userfullname\">".fullname($user);
    if ($entry) {
        echo " <span class=\"lastedit\">".get_string("lastedited").": ".userdate($entry->timemodified)."</span>";
    }
    echo "</td>";
    echo "</tr>";

    echo "\n<tr><td>";
    if ($entry) {
        echo format_text($entry->text, $entry->format);
    } else {
        print_string("noentry", "diary");
    }
    echo "</td></tr>";

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
        $grading_info = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));
        if (!empty($grading_info->items[0]->grades[$entry->userid]->str_long_grade)) {
            if ($gradingdisabled = $grading_info->items[0]->grades[$user->id]->locked || $grading_info->items[0]->grades[$user->id]->overridden) {
                $attrs['disabled'] = 'disabled';
                $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
                $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
                $gradebooklink.= $grading_info->items[0]->grades[$user->id]->str_long_grade.'</a>';
                $gradebookgradestr = '<br/>'.get_string("gradeingradebook", "diary").':&nbsp;'.$gradebooklink;

                $feedbackdisabledstr = 'disabled="disabled"';
                $feedbacktext = $grading_info->items[0]->grades[$user->id]->str_feedback;
            }
        }

        // Grade selector
        $attrs['id'] = 'r' . $entry->id;
        echo html_writer::label(fullname($user) . " " . get_string('grade'), 'r' . $entry->id, true, array('class' => 'accesshide'));
        echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
        echo $hiddengradestr;
        // Rewrote next three lines to show entry needs to be regraded due to resubmission.
        if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
            echo " <span class=\"lastedit\">".get_string("needsregrade", "diary"). "</span>";
        } else if ($entry->timemarked) {
            echo " <span class=\"lastedit\">".userdate($entry->timemarked)."</span>";
        }
        echo $gradebookgradestr;

        // Feedback text
        echo html_writer::label(fullname($user) . " " . get_string('feedback'), 'c' . $entry->id, true, array('class' => 'accesshide'));
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

function diary_print_feedback($course, $entry, $grades) {

    global $CFG, $DB, $OUTPUT;

    require_once($CFG->dirroot.'/lib/gradelib.php');

    if (! $teacher = $DB->get_record('user', array('id' => $entry->teacher))) {
        print_error('Weird diary error');
    }

    echo '<table class="feedbackbox">';

    echo '<tr>';
    echo '<td class="left picture">';
    echo $OUTPUT->user_picture($teacher, array('courseid' => $course->id, 'alttext' => true));
    echo '</td>';
    echo '<td class="entryheader">';
    echo '<span class="author">'.fullname($teacher).'</span>';
    echo '&nbsp;&nbsp;<span class="time">'.userdate($entry->timemarked).'</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td class="left side">&nbsp;</td>';
    echo '<td class="entrycontent">';

    echo '<div class="grade">';

    // Gradebook preference
    $grading_info = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($entry->userid));
    if (!empty($grading_info->items[0]->grades[$entry->userid]->str_long_grade)) {
        echo get_string('grade').': ';
        echo $grading_info->items[0]->grades[$entry->userid]->str_long_grade;
    } else {
        print_string('nograde');
    }
    echo '</div>';

    // Feedback text
    echo format_text($entry->entrycomment, FORMAT_PLAIN);
    echo '</td></tr></table>';
}

