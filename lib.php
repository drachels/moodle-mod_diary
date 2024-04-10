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
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine
use mod_diary\local\results;

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $diary Object containing required diary properties.
 * @return int Diary ID.
 */
function diary_add_instance($diary) {
    global $DB;

    if (empty($diary->assessed)) {
        $diary->assessed = 0;
    }
    // 20190917 First one always true as ratingtime does not exist.
    if (empty($diary->ratingtime) || empty($diary->assessed)) {
        $diary->assesstimestart = 0;
        $diary->assesstimefinish = 0;
    }
    $diary->timemodified = time();
    $diary->id = $DB->insert_record('diary', $diary);

    // 20200903 Added calendar dates.
    results::diary_update_calendar($diary, $diary->coursemodule);

    // 20200901 Added expected completion date.
    if (! empty($diary->completionexpected)) {
        \core_completion\api::update_completion_date_event($diary->coursemodule, 'diary', $diary->id, $diary->completionexpected);
    }

    diary_grade_item_update($diary);

    return $diary->id;
}

/**
 *
 * Given an object containing all the necessary diary data,
 * will update an existing instance with new diary data.
 *
 * @param object $diary
 *            Object containing required diary properties.
 * @return boolean True if successful.
 */
function diary_update_instance($diary) {
    global $DB;

    $diary->timemodified = time();
    $diary->id = $diary->instance;

    if (empty($diary->assessed)) {
        $diary->assessed = 0;
    }

    if (empty($diary->ratingtime) || empty($diary->assessed)) {
        $diary->assesstimestart = 0;
        $diary->assesstimefinish = 0;
    }

    if (empty($diary->notification)) {
        $diary->notification = 0;
    }

    $DB->update_record('diary', $diary);

    // 20200903 Added calendar dates.
    results::diary_update_calendar($diary, $diary->coursemodule);

    // 20200901 Added expected completion date.
    $completionexpected = (! empty($diary->completionexpected)) ? $diary->completionexpected : null;
    \core_completion\api::update_completion_date_event($diary->coursemodule, 'diary', $diary->id, $completionexpected);

    diary_grade_item_update($diary);

    return true;
}

/**
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 *            Diary ID.
 * @return boolean True if successful.
 */
function diary_delete_instance($id) {
    global $DB;

    $result = true;

    if (! $diary = $DB->get_record("diary", [
        "id" => $id,
    ])) {
        return false;
    }

    if (! $DB->delete_records("diary_entries", [
        "diary" => $diary->id,
    ])) {
        $result = false;
    }

    if (! $DB->delete_records("diary_prompts", [
        "diaryid" => $diary->id,
    ])) {
        $result = false;
    }

    if (! $DB->delete_records("diary", [
        "id" => $diary->id,
    ])) {
        $result = false;
    }

    return $result;
}

/**
 * Indicates API features that the diary supports.
 *
 * @uses FEATURE_MOD_PURPOSE:
 * @uses FEATURE__BACKUP_MOODLE2
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_RATE
 * @uses FEATURE_SHOW_DESCRIPTION
 * @param string $feature FEATURE_xx constant for requested feature.
 * @return mixed True if module supports feature, null if doesn't know.
 */
function diary_supports($feature) {
    global $CFG;
    if ((int)$CFG->branch > 311) {
        if ($feature === FEATURE_MOD_PURPOSE) {
            return MOD_PURPOSE_COLLABORATION;
        }
    }
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_RATE:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
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
 * crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 * be considered as view action.
 *
 * @return array
 */
function diary_get_view_actions() {
    return [
        'view',
        'view all',
        'view responses',
    ];
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 * crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 * will be considered as post action.
 *
 * @return array
 */
function diary_get_post_actions() {
    return [
        'add entry',
        'update entry',
        'update feedback',
    ];
}

/**
 * Returns a summary of data activity of this user.
 *
 * Not used yet, as of 20200718.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $diary
 * @return object|null
 */
function diary_user_outline($course, $user, $mod, $diary) {
    global $DB;

    if ($entry = $DB->get_record("diary_entries", [
        "userid" => $user->id,
        "diary" => $diary->id,
    ])) {

        $numwords = count(preg_split("/\w\b/", $entry->text)) - 1;

        $result = new stdClass();
        $result->info = get_string("numwords", "", $numwords);
        $result->time = $entry->timemodified;
        return $result;
    }
    return null;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in diary activities and print it out.
 * Return true if there was output, or false if there was none.
 *
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return bool
 */
function diary_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    if (! get_config('diary', 'showrecentactivity')) {
        return false;
    }

    $dbparams = [
        $timestart,
        $course->id,
        'diary',
    ];
    // 20210611 Added Moodle branch check.
    if ($CFG->branch < 311) {
        $namefields = user_picture::fields('u', null, 'userid');
    } else {
        $userfieldsapi = \core_user\fields::for_userpic();
        $namefields = $userfieldsapi->get_sql('u', false, '', 'userid', false)->selects;;
    }
    $sql = "SELECT de.id, de.timemodified, cm.id AS cmid, $namefields
              FROM {diary_entries} de
              JOIN {diary} d ON d.id = de.diary
              JOIN {course_modules} cm ON cm.instance = d.id
              JOIN {modules} md ON md.id = cm.module
              JOIN {user} u ON u.id = de.userid
             WHERE de.timemodified > ? AND d.course = ? AND md.name = ?
          ORDER BY u.lastname ASC, u.firstname ASC
    ";
    // Changed on 20190622 original line 310: ORDER BY de.timemodified ASC.
    $newentries = $DB->get_records_sql($sql, $dbparams);

    $modinfo = get_fast_modinfo($course);

    $show = [];

    foreach ($newentries as $anentry) {
        if (! array_key_exists($anentry->cmid, $modinfo->get_cms())) {
            continue;
        }
        $cm = $modinfo->get_cm($anentry->cmid);

        if (! $cm->uservisible) {
            continue;
        }
        if ($anentry->userid == $USER->id) {
            $show[] = $anentry;
            continue;
        }
        $context = context_module::instance($anentry->cmid);

        // Only teachers can see other students entries.
        if (! has_capability('mod/diary:manageentries', $context)) {
            continue;
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS && ! has_capability('moodle/site:accessallgroups', $context)) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (! $modinfo->get_groups($cm->groupingid)) {
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

    echo $OUTPUT->heading(get_string('newdiaryentries', 'diary') . ':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->get_cm($submission->cmid);
        $context = context_module::instance($submission->cmid);
        if (has_capability('mod/diary:manageentries', $context)) {
            $link = $CFG->wwwroot . '/mod/diary/report.php?id=' . $cm->id;
        } else {
            $link = $CFG->wwwroot . '/mod/diary/view.php?id=' . $cm->id;
        }
        print_recent_activity_note($submission->timemodified, $submission, $cm->name, $link, false, $viewfullnames);
    }
    return true;
}

/**
 * Returns the users with data in one diary.
 * Users with records in diary_entries - students and teachers.
 *
 * @param int $diaryid
 *            Diary ID.
 * @return array Array of user ids.
 */
function diary_get_participants($diaryid) {
    global $DB;

    // Get students.
    $students = $DB->get_records_sql("SELECT DISTINCT u.id
                                        FROM {user} u, {diary_entries} d
                                       WHERE d.diary = ? AND u.id = d.userid", [$diaryid]);
    // Get teachers.
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id
                                        FROM {user} u, {diary_entries} d
                                       WHERE d.diary = ? AND u.id = d.teacher", [$diaryid]);

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
 * This function returns true if a scale is being used by one diary.
 *
 * @param int $diaryid
 *            Diary ID.
 * @param int $scaleid
 *            Scale ID.
 * @return boolean True if a scale is being used by one diary.
 */
function diary_scale_used($diaryid, $scaleid) {
    global $DB;
    $return = false;

    $rec = $DB->get_record("diary", [
        "id" => $diaryid,
        "grade" => - $scaleid,
    ]);

    if (! empty($rec) && ! empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of diary.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid
 * @return boolean True if the scale is used by any diary.
 */
function diary_scale_used_anywhere($scaleid) {
    global $DB;

    if (empty($scaleid)) {
        return false;
    }

    return $DB->record_exists('diary', ['scale' => $scaleid * - 1]);
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the diary.
 *
 * @param object $mform Form passed by reference.
 */
function diary_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'diaryheader', get_string('modulenameplural', 'diary'));
    $mform->addElement('advcheckbox', 'reset_diary', get_string('removemessages', 'diary'));
    $mform->addElement('checkbox', 'reset_diary_tags', get_string('removealldiarytags', 'diary'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function diary_reset_course_form_defaults($course) {
    return ['reset_diary' => 1];
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * data responses for course $data->courseid.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function diary_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/filelib.php');
    require_once($CFG->dirroot . '/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'diary');
    $status = [];

    $alldatassql = "SELECT d.id
                      FROM {diary} d
                     WHERE d.course=?";

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_diary';
    $ratingdeloptions->ratingarea = 'entry';

    // Set the file storage - may need it to remove files later.
    $fs = get_file_storage();

    // Delete entries if requested.
    if (!empty($data->reset_diary)) {

        $DB->delete_records_select('diary_entries', "diary IN ($alldatassql)", [$data->courseid]);

        if ($datas = $DB->get_records_sql($alldatassql, [$data->courseid])) {
            foreach ($datas as $dataid => $unused) {
                if (!$cm = get_coursemodule_from_instance('diary', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                // Delete any files that may exist.
                $fs->delete_area_files($datacontext->id, 'mod_diary', 'content');

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);

                core_tag_tag::delete_instances('mod_diary', null, $datacontext->id);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // Remove all grades from gradebook.
            diary_reset_gradebook($data->courseid);
        }

        $status[] = [
            'component' => $componentstr,
            'item' => get_string('removeentries', 'diary'),
            'error' => false,
        ];
    }

    // Remove entries by users not enrolled into the course.
    if (!empty($data->reset_data_notenrolled)) {
        $recordssql = "SELECT de.id, de.userid, de.diary, u.id AS userexists, u.deleted AS userdeleted
                         FROM {diary_entries} de
                              JOIN {diary} d ON d.id = de.diary
                              LEFT JOIN {user} u ON de.userid = u.id
                        WHERE d.course = ? AND de.userid > 0";

        $coursecontext = context_course::instance($data->courseid);
        $notenrolled = [];
        $fields = [];
        $rs = $DB->get_recordset_sql($recordssql, [$data->courseid]);
        foreach ($rs as $record) {
            if (array_key_exists($record->userid, $notenrolled) || !$record->userexists || $record->userdeleted
              || !is_enrolled($coursecontext, $record->userid)) {
                // Delete ratings.
                if (!$cm = get_coursemodule_from_instance('diary', $record->dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);
                $ratingdeloptions->contextid = $datacontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                // Delete any files that may exist.
                if ($contents = $DB->get_records('diary_entries', ['recordid' => $record->id], '', 'id')) {
                    foreach ($contents as $content) {
                        $fs->delete_area_files($datacontext->id, 'mod_data', 'content', $content->id);
                    }
                }
                $notenrolled[$record->userid] = true;

                core_tag_tag::remove_all_item_tags('mod_diary', 'diary_entries', $record->id);

                $DB->delete_records('diary_entries', ['recordid' => $record->id]);
            }
        }
        $rs->close();
        $status[] = ['component' => $componentstr, 'item' => get_string('deletenotenrolled', 'diary'), 'error' => false];
    }

    // Remove all ratings.
    if (!empty($data->reset_data_ratings)) {
        if ($datas = $DB->get_records_sql($alldatassql, [$data->courseid])) {
            foreach ($datas as $dataid => $unused) {
                if (!$cm = get_coursemodule_from_instance('diary', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // Remove all grades from gradebook.
            diary_reset_gradebook($data->courseid);
        }

        $status[] = ['component' => $componentstr, 'item' => get_string('deleteallratings'), 'error' => false];
    }

    // Remove all the tags.
    if (!empty($data->reset_data_tags)) {
        if ($datas = $DB->get_records_sql($alldatassql, [$data->courseid])) {
            foreach ($datas as $dataid => $unused) {
                if (!$cm = get_coursemodule_from_instance('data', $dataid)) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_diary', null, $context->id);

            }
        }
        $status[] = ['component' => $componentstr, 'item' => get_string('tagsdeleted', 'data'), 'error' => false];
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates('diary', ['timeopen', 'timeclose'], $data->timeshift, $data->courseid);
        $status[] = ['component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false];
    }

    return $status;
}

/**
 * Returns gradebook data in module.
 *
 * @param  object $courseid
 * @param  object $type
 * @return array
 */
function diary_reset_gradebook($courseid, $type = '') {
    global $DB;

    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {diary} d, {course_modules} cm, {modules} m
             WHERE m.name='diary' AND m.id=cm.module AND cm.instance=d.id AND d.course=?";

    if ($diaries = $DB->get_records_sql($sql, [$courseid])) {
        foreach ($diaries as $diary) {
            diary_grade_item_update($diary);
        }
    }
}

/**
 * Print diary overview.
 *
 * @param object $courses
 * @param array $htmlarray
 */
function diary_print_overview($courses, $htmlarray) {
    global $USER, $CFG, $DB;

    if (! get_config('diary', 'overview')) {
        return [];
    }

    if (empty($courses) || ! is_array($courses) || count($courses) == 0) {
        return [];
    }

    if (! $diarys = get_all_instances_in_courses('diary', $courses)) {
        return [];
    }

    $strdiary = get_string('modulename', 'diary');

    $timenow = time();
    foreach ($diarys as $diary) {
        if (empty($courses[$diary->course]->format)) {
            $courses[$diary->course]->format = $DB->get_field('course', 'format', ['id' => $diary->course]);
        }
        if ($courses[$diary->course]->format == 'weeks' && $diary->days) {

            $coursestartdate = $courses[$diary->course]->startdate;

            $diary->timestart = $coursestartdate + (($diary->section - 1) * 608400);
            if (! empty($diary->days)) {
                $diary->timefinish = $diary->timestart + (3600 * 24 * $diary->days);
            } else {
                $diary->timefinish = 9999999999;
            }
            $diaryopen = ($diary->timestart < $timenow && $timenow < $diary->timefinish);
        } else {
            $diaryopen = true;
        }
        if ($diaryopen) {
            // 20230810 Changed based on pull rquest #29.
            $url = new moodle_url($CFG->wwwroot.'/mod/diary/view.php', ['id' => $diary->coursemodule]);
            $str = '<div class="diary overview"><div class="name">'
                .$strdiary.': <a '
                .($diary->visible ? '' : ' class="dimmed"')
                .' href="'.$url->out(false).'">'
                .$diary->name.'</a></div></div>';
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
 * @param object $diary
 *            if is null, all diarys
 * @param int $userid
 *            if is false all users
 * @return object $grades
 */
function diary_get_user_grades($diary, $userid = 0) {
    global $CFG;
    require_once($CFG->dirroot . '/rating/lib.php');
    // 20200812 Fixed ratings.
    $ratingoptions = new stdClass();
    $ratingoptions->component = 'mod_diary';
    $ratingoptions->ratingarea = 'entry';
    $ratingoptions->modulename = 'diary';
    $ratingoptions->moduleid = $diary->id;
    $ratingoptions->userid = $userid;
    $ratingoptions->aggregationmethod = $diary->assessed;
    $ratingoptions->scaleid = $diary->scale;
    $ratingoptions->itemtable = 'diary_entries';
    $ratingoptions->itemtableusercolumn = 'userid';
    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}

/**
 * Update diary activity grades.
 *
 * @category grade
 * @param object $diary If is null, then all diaries.
 * @param int $userid If is false, then all users.
 * @param boolean $nullifnone Return null if grade does not exist.
 */
function diary_update_grades($diary, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $cm = get_coursemodule_from_instance('diary', $diary->id);
    $diary->cmidnumber = $cm->idnumber;
    if (! $diary->assessed) {
        diary_grade_item_update($diary);
    } else if ($grades = diary_get_user_grades($diary, $userid)) {
        diary_grade_item_update($diary, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        diary_grade_item_update($diary, $grade);
    } else {
        diary_grade_item_update($diary);
    }
}

/**
 * Update or create grade item for given diary.
 *
 * @param stdClass $diary Object with extra cmidnumber.
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise.
 */
function diary_grade_item_update($diary, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $diary->name,
        'idnumber' => $diary->cmidnumber,
    ];

    if (! $diary->assessed || $diary->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;
    } else if ($diary->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $diary->scale;
        $params['grademin'] = 0;
    } else if ($diary->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = - $diary->scale;
    }
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;

    }
    return grade_update('mod/diary', $diary->course, 'mod', 'diary', $diary->id, 0, $grades, $params);
}

/**
 * Delete grade item for given diary.
 *
 * @param object $diary
 * @return object grade_item
 */
function diary_grade_item_delete($diary) {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/diary', $diary->course, 'mod', 'diary', $diary->id, 0, null, ['deleted' => 1]);
}

/**
 * Return only the users that have entries in the specified diary activity.
 * Used by report.php.
 *
 * @param object $diary
 * @param object $currentgroup
 * @param object $sortoption return object $diarys
 */
function diary_get_users_done($diary, $currentgroup, $sortoption) {
    global $DB;

    $params = [];

    $sql = "SELECT DISTINCT u.*
              FROM {diary_entries} de
              JOIN {user} u ON de.userid = u.id ";

    // Group users.
    if ($currentgroup != 0) {
        $sql .= "JOIN {groups_members} gm ON gm.userid = u.id AND gm.groupid = ?";
        $params[] = $currentgroup;
    }
    // 20201014 Changed to a sort option preference to sort lastname ascending or descending.
    $sql .= " WHERE de.diary = ? ORDER BY " . $sortoption;

    $params[] = $diary->id;

    $diarys = $DB->get_records_sql($sql, $params);

    $cm = diary_get_coursemodule($diary->id);
    if (! $diarys || ! $cm) {
        return null;
    }

    // Remove unenrolled participants.
    foreach ($diarys as $key => $user) {

        $context = context_module::instance($cm->id);

        $canadd = has_capability('mod/diary:addentries', $context, $user);
        $entriesmanager = has_capability('mod/diary:manageentries', $context, $user);

        if (! $entriesmanager && ! $canadd) {
            unset($diarys[$key]);
        }
    }
    return $diarys;
}

/**
 * Returns the diary instance course_module id.
 *
 * @param integer $diaryid
 * @return object
 */
function diary_get_coursemodule($diaryid) {
    global $DB;

    return $DB->get_record_sql("SELECT cm.id
                                  FROM {course_modules} cm
                                  JOIN {modules} m ON m.id = cm.module
                                 WHERE cm.instance = ?
                                   AND m.name = 'diary'", [$diaryid]);
}

/**
 * Serves the diary files.
 * THIS FUNCTION MAY BE ORPHANED. APPEARS TO BE SO IN JOURNAL.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param stdClass $context Context object.
 * @param string $filearea File area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if file not found, does not return if found - just send the file.
 */
function diary_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (! $course->visible && ! has_capability('moodle/course:viewhiddencourses', $context)) {
        return false;
    }

    // Args[0] should be the entry id.
    $entryid = intval(array_shift($args));
    $entry = $DB->get_record('diary_entries', ['id' => $entryid], 'id, userid', MUST_EXIST);

    $canmanage = has_capability('mod/diary:manageentries', $context);
    if (! $canmanage && ! has_capability('mod/diary:addentries', $context)) {
        // Even if it is your own entry.
        return false;
    }

    // Students can only see their own entry.
    if (! $canmanage && $USER->id !== $entry->userid) {
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
 * Extends the settings navigation with the diary settings.
 *
 * This function is called when the context for the page is a diary module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav
 * @param navigation_node $navref
 */
function diary_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $navref) {
    global $PAGE, $DB, $USER;

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    // Link to add automatic time released prompts to Diary activities. Visible to teachers and admin only.
    if (has_capability('mod/diary:addinstance', $context)) {
        $link = new moodle_url('/mod/diary/prompt_edit.php', ['id' => $cm->id]);
        $linkname = get_string('promptstitle', 'diary');
        $icon = new pix_icon('icon', '', 'diary', ['class' => 'icon']);
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING, null, null, $icon);
    }

    // Link to transfer Journal entries to Diary entries. Visible to admin only.
    if (is_siteadmin()) {
        $link = new moodle_url('/mod/diary/journaltodiaryxfr.php', ['id' => $cm->id]);
        $linkname = get_string('journaltodiaryxfrtitle', 'diary');
        $icon = new pix_icon('icon', '', 'diary', ['class' => 'icon']);
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING, null, null, $icon);
    }
}
