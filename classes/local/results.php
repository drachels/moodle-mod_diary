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
 * Keyboard utilities for Diary.
 *
 * 2020071700 Moved these functions from lib.php to here.
 *
 * @package    mod_diary
 * @copyright  AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;
defined('MOODLE_INTERNAL') || die();

use stdClass;
use csv_export_writer;
use html_writer;

/**
 * Utility class for Diary results.
 *
 * @package    mod_diary
 * @copyright  AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results  {

    /**
     * Download entries in this diary activity.
     *
     * @param array $array
     * @param string $filename - The filename to use.
     * @param string $delimiter - The character to use as a delimiter.
     * @return nothing
     */
    public static function download_entries($context, $course, $id, $diary) {
        $filename = "export.csv";
        $delimiter = ";";

        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/csvlib.class.php');
        $data = new stdClass();
        $data->diary = $diary->id;

        // Trigger download_diary_entries event.
        $event = \mod_diary\event\download_diary_entries::create(array(
            'objectid' => $data->diary,
            'context' => $context
        ));
        $event->trigger();

        // Construct sql query and filename based on admin, teacher, or student.
        // Add filename details based on course and Diary activity name.
        $csv = new csv_export_writer();
        $strdiary = get_string('pluginname', 'diary');
        $whichuser = ''; // Leave blank for an admin or teacher.
        if (is_siteadmin($USER->id)) {
            $whichdiary = ('AND d.diary > 0');
            $csv->filename = clean_filename(get_string('exportfilenamep1', 'diary'));
        } else if (has_capability('mod/diary:manageentries', $context)) {
            $whichdiary = ('AND d.diary = ');
            $whichdiary .= ($diary->id);
            $csv->filename = clean_filename(($course->shortname).'_');
            $csv->filename .= clean_filename(($diary->name));
        } else if (has_capability('mod/diary:addentries', $context)) {
            $whichdiary = ('AND d.diary = ');
            $whichdiary .= ($diary->id);
            $whichuser = (' AND d.userid = '.$USER->id); // Not an admin or teacher so can only get their OWN entries.
            $csv->filename = clean_filename(($course->shortname).'_');
            $csv->filename .= clean_filename(($diary->name));
        }
        $csv->filename .= clean_filename(get_string('exportfilenamep2', 'diary').gmdate("Ymd_Hi").'GMT.csv');

        $fields = array();

        $fields = array(get_string('firstname'),
                        get_string('lastname'),
                        get_string('pluginname', 'diary'),
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
        $sql .= ($whichuser);
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

    /**
     * Prints the currently selected diary entry of student identified as $user, on the report page.
     *
     * @param integer $course
     * @param integer $user
     * @param integer $entry
     * @param integer $teachers
     * @param integer $grades
     */
    public static function diary_print_user_entry($course, $diary, $user, $entry, $teachers, $grades) {

        global $USER, $OUTPUT, $DB, $CFG;

        require_once($CFG->dirroot.'/lib/gradelib.php');
        $dcolor3 = get_config('mod_diary', 'entrybgc');
        $dcolor4 = get_config('mod_diary', 'entrytextbgc');

//print_object('1 in function diary_print_user_entry and printing $entry');
//print_object($entry);

        // Create a table for the current users entry with area for teacher feedback.
        echo '<table class="diaryuserentry" id="entry-'.$user->id.'">';

        // Add an entry label followed by the date of the entry.
        echo '<tr>';
        echo '<td style="width:35px;">'.get_string('entry', 'diary').'</td><td>';
        echo date('M d, Y', $entry->timecreated);
        echo '</td><td></td>';
        echo '</tr>';

        // Add first of two rows, this one containing details showing the user, timecreated, and time last edited.
        echo '<tr>';
        echo '<td class="userpix" rowspan="2">';
        echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
        echo '</td>';
        echo '<td class="userfullname">'.fullname($user);
        if ($entry) {
            echo ' <span class="lastedit">'
               .get_string("timecreated", 'diary')
               .':  '.userdate($entry->timecreated).' '
               .get_string("lastedited").': '
               .userdate($entry->timemodified).' </span>';
        }
        echo '</td><td style="width:55px;"></td>';
        echo '</tr>';

        // Add the secon of two rows, this one containing the users text for this entry.
        echo '<tr><td>';
        echo '<div align="left" style="font-size:1em; padding: 5px;
            font-weight:bold;background: '.$dcolor4.';
            border:1px solid black;
            -webkit-border-radius:16px;
            -moz-border-radius:16px;border-radius:16px;">';
        // If there is a user entry, format it and show it.
        if ($entry) {
            echo diary_format_entry_text($entry, $course);
        } else {
            print_string("noentry", "diary");
        }
        echo '</div></td><td style="width:55px;"></td></tr>';

        // If there is a user entry, add a teacher feedback area for grade
        // and comments. Add previous grades and comments, if available.
        if ($entry) {
            echo '<tr>';
            echo '<td class="userpix">';
            if (!$entry->teacher) {
                $entry->teacher = $USER->id;
            }
            if (empty($teachers[$entry->teacher])) {
                $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
            }
            echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id, 'alttext' => true));
            echo '</td>';
            echo '<td>'.get_string("feedback").':  ';

            $attrs = array();
            $hiddengradestr = '';
            $gradebookgradestr = '';
            $feedbackdisabledstr = '';
            $feedbacktext = $entry->entrycomment;

            // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
            $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));
//print_object('2 in function diary_print_user_entry and printing $gradinginfo');
//print_object($gradinginfo);
            if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
                if ($gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked
                    || $gradinginfo->items[0]->grades[$user->id]->overridden) {
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
//print_object('3 in function diary_print_user_entry and printing $attrs and $entry');
//print_object($attrs);
//print_object($entry);
            echo html_writer::label(fullname($user)." ".get_string('grade'), 'r'.$entry->id, true, array('class' => 'accesshide'));
//print_object($diary->assessed);
            if ($diary->assessed > 0) {
                echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
            }
            echo $hiddengradestr;
//print_object('4 in function diary_print_user_entry and printing $hiddengradestr');
//print_object($hiddengradestr);
            // Rewrote next three lines to show entry needs to be regraded due to resubmission.
            if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
                echo ' <span class="needsedit">'.get_string("needsregrade", "diary"). ' </span>';
            } else if ($entry->timemarked) {
                echo ' <span class="lastedit">'.userdate($entry->timemarked).' </span>';
            }
            echo $gradebookgradestr;
//print_object('5 in function diary_print_user_entry and printing $gradebookgradestr');
//print_object($gradebookgradestr);

            // Feedback text.
            echo html_writer::label(fullname($user)." "
                .get_string('feedback'), 'c'
                .$entry->id, true, array('class' => 'accesshide'));
            echo '<p><textarea id="c'
                .$entry->id
                .'" name="c'.$entry->id
                .'" rows="12" cols="60" $feedbackdisabledstr>';
            echo p($feedbacktext);
            echo '</textarea></p>';

            if ($feedbackdisabledstr != '') {
                echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }

    /**
     * Check to see if this Diary is available for use.
     *
     * Used in view.php. 20200718 Not found in view.php now.
     * @param int $diary
     */
    public static function is_available($diary) {
        $timeopen = $diary->timeopen;
        $timeclose = $diary->timeclose;
        return (($timeopen == 0 || time() >= $timeopen) && ($timeclose == 0 || time    () < $timeclose));
    }
}