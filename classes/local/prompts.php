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
 * Results utilities for Diary.
 *
 * 2020071700 Moved these functions from lib.php to here.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine
// define('DIARY_EVENT_TYPE_OPEN', 'open');
// define('DIARY_EVENT_TYPE_CLOSE', 'close');
use mod_diary\local\results;
use mod_diary\local\prompts;
use stdClass;
use csv_export_writer;
use html_writer;
use context_module;
use calendar_event;

/**
 * Utility class for Diary results.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompts {


    /**
     * Returns availability status.
     * Added 20200903.
     *
     * @param array $diary
     */
    public static function diary_available($diary) {
        $timeopen = $diary->timeopen;
        $timeclose = $diary->timeclose;
        return (($timeopen == 0 || time() >= $timeopen) && ($timeclose == 0 || time() < $timeclose));
    }

    /**
     * Download entries in this diary activity. - THIS IS NOT BEING USED!
     *
     * @param array $context Context for this download.
     * @param array $course Course for this download.
     * @param array $diary Diary to download.
     * @return nothing
     */
    public static function download_entries($context, $course, $diary) {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/csvlib.class.php');
        $data = new stdClass();
        $data->diary = $diary->id;

        // Trigger download_diary_entries event.
        $event = \mod_diary\event\download_diary_entries::create(
            [
                'objectid' => $data->diary,
                'context' => $context,
            ]
        );
        $event->trigger();

        // Construct sql query and filename based on admin, teacher, or student.
        // Add filename details based on course and Diary activity name.
        $csv = new csv_export_writer();
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

        $fields = [];
        $fields = [
            get_string('firstname'),
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
            get_string('text', 'diary'),
        ];
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
        $sql .= "       GROUP BY u.lastname, u.firstname, d.diary, d.id
                  ORDER BY u.lastname ASC, u.firstname ASC, d.diary ASC, d.id ASC";

        // Add the list of users and diaries to our data array.
        if ($ds = $DB->get_records_sql($sql, $fields)) {
            foreach ($ds as $d) {
                $output = [
                    $d->firstname,
                    $d->lastname,
                    $d->diary,
                    $d->userid,
                    $d->timecreated,
                    $d->timemodified,
                    $d->format,
                    $d->rating,
                    $d->entrycomment,
                    $d->teacher,
                    $d->timemarked,
                    $d->mailed,
                    $d->text,
                ];
                $csv->add_data($output);
            }
        }
        // Download the completed array.
        $csv->download_file();
    }

    /**
     * Prints the currently selected diary entry of student identified as $user, on the report page.
     *
     * @param array $context
     * @param array $course
     * @param array $diary
     * @param array $user
     * @param array $entry
     * @param array $teachers
     * @param array $grades
     */
    public static function diary_print_user_entry($context, $course, $diary, $user, $entry, $teachers, $grades) {
        global $USER, $OUTPUT, $DB, $CFG;
        $id = required_param('id', PARAM_INT); // Course module.
        $diaryid = optional_param('diary', $diary->id, PARAM_INT); // Diaryid.
        $action = required_param('action', PARAM_TEXT); // Current sort Action.

        // 20210605 Changed to this format.
        require_once(__DIR__ .'/../../../../lib/gradelib.php');
        require_once($CFG->dirroot.'/rating/lib.php');
        // 20210705 Added new activity color setting.
        $dcolor4 = $diary->entrytextbgc;

        // Create a table for the current users entry with area for teacher feedback.
        echo '<table class="diaryuserentry" id="entry-'.$user->id.'">';
        if ($entry) {

            // 20211109 needed for, Add to feedback/Clear feedback, buttons. 20211219 Moved here.
            $param1 = optional_param('button1'.$entry->id, '', PARAM_TEXT); // Transfer entry.
            $param2 = optional_param('button2'.$entry->id, '', PARAM_TEXT); // Clear entry.

            // Add an entry label followed by the date of the entry.
            echo '<tr>';
            echo '<td style="width:35px;">'.get_string('entry', 'diary').':</td><td>';
            echo userdate($entry->timecreated);
            // 20201202 Added link to show all entries for a single user.
            echo '  <a href="reportsingle.php?id='.$id
                .'&user='.$user->id
                .'&action=allentries">'.get_string('reportsingle', 'diary')
                .'</a></td><td></td>';
            echo '</tr>';
        }

        // Add first of two rows, this one showing the user picture and users name.
        echo '<tr>';
        echo '<td class="userpix" rowspan="2">';
        echo $OUTPUT->user_picture($user,
            [
                'courseid' => $course->id,
                'alttext' => true,
            ]
        );
        echo '</td>';
        echo '<td class="userfullname">'.fullname($user).'<br>';
        echo '</td><td style="width:55px;"></td>';
        echo '</tr>';

        // Add the second of two rows, this one containing the users text for this entry.
        echo '<tr><td>';
        echo '<div class="entry" style="background: '.$dcolor4.';">';

        // If there is a user entry, format it and show it.
        if ($entry) {
            $temp = $entry;
            echo self::diary_format_entry_text($entry, $course);
            // 20210701 Moved copy 1 of 2 here due to new stats.
            echo '</div></td><td style="width:55px;"></td></tr>';

            // 20210703 Moved to here from up above so the table gets rendered in the right spot.
            $statsdata = diarystats::get_diary_stats($temp, $diary);
            // 20211212 Moved the echo for output here instead of in the function in the diarystats file.
            echo $statsdata;

            // 20211212 Added separate function to get the common error data here.
            $comerrdata = diarystats::get_common_error_stats($temp, $diary);
            echo $comerrdata;
            // 20211212 List all the auto rating data.
            list($autoratingdata,
                 $currentratingdata)
                 = diarystats::get_auto_rating_stats($temp, $diary);
            // 20211212 Added list function to get and print the autorating data here.
            echo $autoratingdata;
        } else {
            print_string("noentry", "diary");
            // 20210701 Moved copy 2 of 2 here due to new stats.
            echo '</div></td><td style="width:55px;"></td></tr>';
        }

        echo '</table>';

        echo '<table class="diaryuserentry" id="entry-'.$user->id.'">';

        // If there is a user entry, add a teacher feedback area for grade
        // and comments. Add previous grades and comments, if available.
        if ($entry) {
            echo '<tr>';
            echo '<td class="userpix">';
            if (! $entry->teacher) {
                $entry->teacher = $USER->id;
            }
            if (empty($teachers[$entry->teacher])) {
                $teachers[$entry->teacher] = $DB->get_record('user',
                    [
                        'id' => $entry->teacher,
                    ]
                );
            }
            // 20200816 Get the current rating for this user!
            if ($diary->assessed != RATING_AGGREGATE_NONE) {
                $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $diary->id, $user->id);
                $gradeitemgrademax = $gradinginfo->items[0]->grademax;
                $userfinalgrade = $gradinginfo->items[0]->grades[$user->id];
                $currentuserrating = $userfinalgrade->str_long_grade;
            } else {
                $currentuserrating = '';
            }
            // Get the type of rating so we can use it as a label.
            $aggregatestr = self::get_diary_aggregation($diary->assessed);

            // Add picture of the last teacher to rate this entry.
            echo $OUTPUT->user_picture($teachers[$entry->teacher],
                [
                    'courseid' => $course->id,
                    'alttext' => true,
                ]
            );
            echo '</td>';
            // 20210707 Added teachers name to go with their picture.
            // 20211027 Added button to add/delete auto grade stats and rating to feedback.
            echo '<td>'.$teachers[$entry->teacher]->firstname.' '.$teachers[$entry->teacher]->lastname.

                 ' <input class="btn btn-warning btn-sm"
                         role="button"
                         style="border-radius: 8px"
                         name="button1'.$entry->id.'"
                         onClick="return clClick()"
                         type="submit"
                         value="'.get_string('addtofeedback', 'diary').'"></input> '.

                 '<input class="btn btn-warning  btn-sm"
                         style="border-radius: 8px"
                         name="button2'.$entry->id.'"
                         onClick="return clClick()"
                         type="submit"
                         value="'.get_string('clearfeedback', 'diary').'"></input>';

            // 20211228 Create a test anchor link for testing.
            // echo '<a href="#'.$entry->id.'">xxxxx</a>';

            // 20211228 Create an anchor right after Add/Clear buttons.
            echo  '<a id="'.$entry->id.'"></a>';
            echo '<br>'.get_string('rating', 'diary').':  ';

            $attrs = [];
            $hiddengradestr = '';
            $gradebookgradestr = '';
            $feedbackdisabledstr = '';
            $feedbacktext = $entry->entrycomment;
            // 20220107 If the, Add to feedback, button is clicked process it here.
            if (isset($param1) && get_string('addtofeedback', 'diary') == $param1) {
                // 20220105 Do an immediate update here.
                $entry->rating = $currentratingdata;
                $feedbacktext .= $statsdata.$comerrdata.$autoratingdata;
                $entry->entrycomment = $statsdata.$comerrdata.$autoratingdata;
                $DB->update_record('diary_entries', $entry, $bulk = false);
            }
            // 20220107 If the, Clear feedback, button is clicked process it here.
            if (isset($param2) && get_string('clearfeedback', 'diary') == $param2) {
                // 20220105 Reset the entry rating and entry comment to null.
                $entry->rating = null;
                $feedbacktext = null;
                $entry->entrycomment = null;
                // 20220105 Update the actual diary entry.
                $DB->update_record('diary_entries', $entry, $bulk = false);
                // 20220107 Verify there is a rating for this entry then delete it.
                if ($rec = $DB->get_record('rating',  ['itemid' => $entry->id])) {
                    $DB->delete_records('rating', ['itemid' => $entry->id]);
                    // 20220107 Recalculate the rating for this user for this diary activity.
                    diary_update_grades($diary, $entry->userid);
                }
            }

            // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
            $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary,
                [
                    $user->id,
                ]
            );

            if (! empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
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

            // 20210510 Modified Grade selector to check for Moodle version.
            $attrs['id'] = 'r'.$entry->id;
            if ($CFG->branch < 311) {
                echo html_writer::label(fullname($user)." ".get_string('grade'),
                    'r'.$entry->id, true, ['class' => 'accesshide']);
            } else {
                echo html_writer::label(fullname($user)." ".get_string('gradenoun'),
                    'r'.$entry->id, true, ['class' => 'accesshide']);
            }

            if ($diary->assessed > 0) {
                echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
            }
            echo $hiddengradestr;

            // Rewrote next three lines to show entry needs to be regraded due to resubmission.
            if (! empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
                echo ' <span class="needsedit">'.get_string("needsregrade", "diary").'</span>';
            } else if ($entry->timemarked) {
                echo ' <span class="lastedit"> '.userdate($entry->timemarked).'</span>';
            }
            echo $gradebookgradestr;

            // 20200816 Added overall rating type and rating.
            echo '<br>'.$aggregatestr.' '.$currentuserrating;

            // Feedback text.
            echo html_writer::label(fullname($user)." ".get_string('feedback'), 'c'.$entry->id, true,
                [
                    'class' => 'accesshide',
                ]
            );
            echo '<p><textarea id="c'.$entry->id.'" name="c'.$entry->id.'" rows="6" cols="60" $feedbackdisabledstr>';
            echo p($feedbacktext);
            echo '</textarea></p>';

            // 20210630 Switched from plain textarea to an editor.
            $editor = editors_get_preferred_editor(FORMAT_HTML);
            echo $editor->use_editor('c'.$entry->id,
                                    ['context' => $context, 'autosave' => false],
                                    ['return_types' => FILE_EXTERNAL]);

            if ($feedbackdisabledstr != '') {
                echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }

    /**
     * Print the teacher feedback.
     * This renders the teacher feedback on the view.php page.
     *
     * @param array $course
     * @param array $entry
     * @param array $grades
     */
    public static function diary_print_feedback($course, $entry, $grades) {
        global $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot . '/lib/gradelib.php');

        if (! $teacher = $DB->get_record('user', ['id' => $entry->teacher])) {
            throw new moodle_exception(get_string('generalerror', 'diary'));
        }

        echo '<table class="feedbackbox">';

        echo '<tr>';
        echo '<td class="left picture">';

        echo $OUTPUT->user_picture($teacher,
            [
                'courseid' => $course->id,
                'alttext' => true,
            ]
        );
        echo '</td>';
        echo '<td class="entryheader">';
        echo '<span class="author">' . fullname($teacher) . '</span>';
        echo '&nbsp;&nbsp;<span class="time">' . userdate($entry->timemarked) . '</span>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="entrycontent">';

        echo '<div class="grade">';

        // Gradebook preference.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary,
            [
                $entry->userid,
            ]
        );

        // 20210609 Added branch check for string compatibility.
        if (! empty($entry->rating)) {
            if ($CFG->branch > 310) {
                echo get_string('gradenoun') . ': ';
            } else {
                echo get_string('grade') . ': ';
            }
            echo $entry->rating.'/' . number_format($gradinginfo->items[0]->grademax, 2);
        } else {
            print_string('nograde');
        }
        echo '</div>';

        // Feedback text.
        // Original code, echo format_text($entry->entrycomment, FORMAT_PLAIN); followed by new code.
        echo format_text($entry->entrycomment, FORMAT_HTML);
        echo '</td></tr></table>';
    }

    /**
     * Return formatted text.
     *
     * @param array $entry
     * @param array $course
     * @param array $cm
     * @return string $entrytext Text string containing a user entry.
     * @return int $entry-format Format for user entry.
     * @return array $formatoptions Array of options for a user entry.
     */
    public static function diary_format_entry_text($entry, $course = false, $cm = false) {
        if (! $cm) {
            if ($course) {
                $courseid = $course->id;
            } else {
                $courseid = 0;
            }
            $cm = get_coursemodule_from_instance('diary', $entry->diary, $courseid);
        }

        $context = context_module::instance($cm->id);
        $entrytext = file_rewrite_pluginfile_urls($entry->text, 'pluginfile.php', $context->id, 'mod_diary', 'entry', $entry->id);

        $formatoptions = [
            'context' => $context,
            'noclean' => false,
            'trusted' => false,
        ];
        return format_text($entrytext, $entry->format, $formatoptions);
    }

    /**
     * Return the editor and attachment options when editing a diary entry.
     *
     * @param array $course Course object.
     * @param array $context Context object.
     * @param array $diary Diary object.
     * @param array $entry Entry object.
     * @param string $action Action object.
     * @param string $firstkey Firstkey object.
     * @return array $editoroptions Array containing the editor and attachment options.
     * @return array $attachmentoptions Array containing the editor and attachment options.
     */
    public static function diary_get_editor_and_attachment_options($course, $context, $diary, $entry, $action, $firstkey) {
        $maxfiles = 99; // TODO: add some setting.
        $maxbytes = $course->maxbytes; // TODO: add some setting.

        // 20210613 Added more custom data to use in edit_form.php to prevent illegal access.
        $editoroptions = [
            'timeclose' => $diary->timeclose,
            'editall' => $diary->editall,
            'editdates' => $diary->editdates,
            'action' => $action,
            'firstkey' => $firstkey,
            'trusttext' => true,
            'maxfiles' => $maxfiles,
            'maxbytes' => $maxbytes,
            'context' => $context,
            'subdirs' => false,
        ];
        $attachmentoptions = [
            'subdirs' => false,
            'maxfiles' => $maxfiles,
            'maxbytes' => $maxbytes,
        ];

        return [
            $editoroptions,
            $attachmentoptions,
        ];
    }



    /**
     * Counts all the diary prompts for a diary activity.
     * Called from prompt_edit.php and view.php. Probably needed in other locations.
     * @param array $diary
     * @return int count($tcount) Total count of prompts for current diary.
     * @return int count($past) Count of prompts for which the date is already in the past.
     * @return int count($current) Count of prompts for which the date is current. Error if greater than 1.
     * @return int count($future) Count of prompts for which the date is in the future.
     */
    public static function diary_count_prompts($diary) {
        global $DB;

        $tcount = 0;
        $past = 0;
        $current = 0;
        $future = 0;

        $prompts = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'datestart ASC, datestop ASC');

        // If they exist, count the total prompts, as well as the number of each past, current, or future ones.
        if ($prompts) {
            foreach ($prompts as $prompts) {
                $status = '';
                    $tcount++;

                if ($prompts->datestop < time()) {
                    $past++;
                } else if (($prompts->datestart < time()) && $prompts->datestop > time()) {
                    $current++;
                } else if ($prompts->datestart > time() && $prompts->datestop > time()) {
                    $future++;
                }
            }
        }
        return [$tcount, $past, $current, $future];
    }

    /**
     * Remove the selected prompt.
     *
     * @param array $cm
     * @return object
     */
    public static function prompt_remove($cm) {
        global $CFG, $DB;

        $context = context_module::instance($cm->id);

        if (null !== (required_param('promptid', PARAM_INT))) {
            $promptid = required_param('promptid', PARAM_INT);
            $itemid = required_param('promptid', PARAM_INT);
            $dbquestion = $DB->get_record('diary_prompts', ['id' => $promptid]);

            $DB->delete_records('diary_prompts', ['id' => $promptid]);
            // Trigger prompt_remove event.
            if ($CFG->version > 2014051200) { // If newer than Moodle 2.7+ use new event logging.
                $params = [
                    'objectid' => $cm->id,
                    'context' => $context,
                    'other' => [
                        'promptid' => $promptid,
                    ],
                ];
                $event = \mod_diary\event\prompt_removed::create($params);
                $event->trigger();
            } else {
                add_to_log($cm->id, 'diary', 'remove prompt',
                    "view.php?id={$cm->id}&round=$rid", $rid, $cm->id);
            }
        }
        return;
    }

    /**
     * Can this prompt be removed?
     *
     * @param array $cm
     * @param int $promptid The id of the prompt we want to delete.
     * @return object $promptcount Number of prompts for this diary activity.
     */
    public static function prompt_in_use($cm, $promptid) {
        global $CFG, $DB;
        $context = context_module::instance($cm->id);

        // Need code to implment check to see if the prompt is in use, and deny the delete if it is in use.
        // Count the number of mdl_diary_entries using promptid. Count greater than zero means it is in use.
        $promptcount = count($DB->get_records('diary_entries', ['promptid' => $promptid], $sort = ''));

        if ($promptcount) {
            // Trigger event, remove denied because the prompt is in_use.
            $params = [
                'objectid' => $cm->id,
                'context' => $context,
                'other' => [
                    'promptid' => $promptid,
                ],
            ];
            $event = \mod_diary\event\prompt_in_use::create($params);
            $event->trigger();
        }
        return $promptcount;
    }

    /**
     * Get current prompts for view/use.
     *
     * @param array $diary The settings for this diary activity.
     * @return object
     */
    public static function prompts_viewcurrent($diary) {
        global $CFG, $DB;

        $data = new stdClass();
        $output = '';
        $line = [];
        $counter = 0;
        $past = 0;
        $current = 0;
        $future = 0;
        $promptsall = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'datestart ASC, datestop ASC');
        $diary->intro = '';
        // If there are any prompts for this diary, create a list of them.
        if ($promptsall) {
            foreach ($promptsall as $prompts) {
                $status = '';
                    $counter++;

                if ($prompts->datestop < time()) {
                    $past++;
                } else if (($prompts->datestart < time()) && $prompts->datestop > time()) {
                    $current++;
                } else if ($prompts->datestart > time() && $prompts->datestop > time()) {
                    $future++;
                }

                if (($prompts->datestart < time()) && $prompts->datestop > time()) {
                    $data->entryid = $prompts->id;
                    $data->id = $prompts->id;
                    $data->diaryid = $prompts->diaryid;
                    $data->datestart = $prompts->datestart;
                    $data->datestop = $prompts->datestop;
                    $data->text = $prompts->text;
                    $data->format = FORMAT_HTML;
                    $data->minchar = $prompts->minchar;
                    $data->maxchar = $prompts->maxchar;
                    $data->minmaxcharpercent = $prompts->minmaxcharpercent;
                    $data->minword = $prompts->minword;
                    $data->maxword = $prompts->maxword;
                    $data->minmaxwordpercent = $prompts->minmaxwordpercent;
                    $data->minsentence = $prompts->minsentence;
                    $data->maxsentence = $prompts->maxsentence;
                    $data->minmaxsentencepercent = $prompts->minmaxsentencepercent;
                    $data->minparagraph = $prompts->minparagraph;
                    $data->maxparagraph = $prompts->maxparagraph;
                    $data->minmaxparagraphpercent = $prompts->minmaxparagraphpercent;

                    $start = '<td>'.userdate($data->datestart).'</td>';
                    $stop = '<td>'.userdate($data->datestop).'</td>';

                    $prompttext = '<b><td>'.
                                  get_string('writingpromptlable', 'diary',
                                  ['counter' => $counter,
                                  'entryid' => $data->entryid,
                                  'starton' => $start,
                                  'endon' => $stop,
                                  'datatext' => $data->text,
                                  ]).
                                  '</td>';
                    $characters = '<td>'.get_string('chars', 'diary')
                                  .get_string('minc', 'diary').$data->minchar
                                  .get_string('maxc', 'diary').$data->maxchar
                                  .get_string('errp', 'diary').$data->minmaxcharpercent.', </td>';
                    $words = '<td>'.get_string('words', 'diary')
                             .get_string('minc', 'diary').$data->minword
                             .get_string('maxc', 'diary').$data->maxword
                             .get_string('errp', 'diary').$data->minmaxwordpercent.', </td>';
                    $sentences = '<td>'.get_string('sentences', 'diary')
                                 .get_string('minc', 'diary').$data->minsentence
                                 .get_string('maxc', 'diary').$data->maxsentence
                                  .get_string('errp', 'diary').$data->minmaxsentencepercent.', </td>';
                    $paragraphs = '<td>'.get_string('paragraphs', 'diary')
                                  .get_string('minc', 'diary').$data->minparagraph
                                  .get_string('maxc', 'diary').$data->maxparagraph
                                  .get_string('errp', 'diary').$data->minmaxparagraphpercent.'</td>';
                    $status .= $status.$prompttext.$characters.$words.$sentences.$paragraphs;
                    if ($status) {
                        $diary->intro .= $status.'</b><hr>';
                    }
                }
            }
        }
        return;
    }

    /**
     * Is there a current prompt?
     *
     * @param array $diary The settings for this diary activity.
     * @return int $promptid The current promptid or zero if not available.
     */
    public static function get_current_promptid($diary) {
        global $CFG, $DB;
        $counter = 0;
        $past = 0;
        $current = 0;
        $future = 0;
        $promptid = 0;
        if (! $promptsall = $DB->get_records('diary_prompts',
                                            ['diaryid' => $diary->id],
                                            $sort = 'datestart ASC, datestop ASC')) {
            $promptid = new stdClass();
            $promptid = 0;
        } else {
            foreach ($promptsall as $prompts) {
                $status = '';
                    $counter++;
                if ($prompts->datestop < time()) {
                    $past++;
                } else if (($prompts->datestart < time()) && $prompts->datestop > time()) {
                    $current++;
                    $promptid = $prompts->id;
                } else if ($prompts->datestart > time() && $prompts->datestop > time()) {
                    $future++;
                }
            }
        }
        return $promptid;
    }
}
