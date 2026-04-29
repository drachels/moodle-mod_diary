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

defined('MOODLE_INTERNAL') || die(); // phpcs:ignore
// phpcs:ignore
// ...define('DIARY_EVENT_TYPE_OPEN', 'open');...
// phpcs:ignore
// ...define('DIARY_EVENT_TYPE_CLOSE', 'close');...
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
    /** Prompt mode: keep existing time-window behavior. */
    const PROMPTMODE_SEQUENTIAL = 0;
    /** Prompt mode: user selects from prompt pool. */
    const PROMPTMODE_CHOICE = 1;
    /** Prompt mode: system assigns a random prompt. */
    const PROMPTMODE_RANDOM = 2;
    /** Prompt mode: user must complete all prompts in pool. */
    const PROMPTMODE_COMPLETEALL = 3;
    /** Prompt mode: user chooses X prompts from the pool. */
    const PROMPTMODE_CHOICECOMPLETE = 4;
    /** Backward-compatible alias for previous internal name. */
    const PROMPTMODE_PARTIALCOMPLETE = self::PROMPTMODE_CHOICECOMPLETE;
    /** Prompt mode: system assigns random prompts until X are completed. */
    const PROMPTMODE_RANDOMCOMPLETE = 5;

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
        require_once($CFG->libdir . '/csvlib.class.php');
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
            $csv->filename = clean_filename(($course->shortname) . '_');
            $csv->filename .= clean_filename(($diary->name));
        } else if (has_capability('mod/diary:addentries', $context)) {
            $whichdiary = ('AND d.diary = ');
            $whichdiary .= ($diary->id);
            $whichuser = (' AND d.userid = ' . $USER->id); // Not an admin or teacher so can only get their OWN entries.
            $csv->filename = clean_filename(($course->shortname) . '_');
            $csv->filename .= clean_filename(($diary->name));
        }
        $csv->filename .= clean_filename(get_string('exportfilenamep2', 'diary') . gmdate("Ymd_Hi") . 'GMT.csv');

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
        require_once(__DIR__ . '/../../../../lib/gradelib.php');
        require_once($CFG->dirroot . '/rating/lib.php');
        // 20210705 Added new activity color setting.
        $dcolor4 = $diary->entrytextbgc;

        // Create a table for the current users entry with area for teacher feedback.
        echo '<table class="diaryuserentry" id="entry-' . $user->id . '">';
        if ($entry) {
            // 20211109 needed for, Add to feedback/Clear feedback, buttons. 20211219 Moved here.
            $param1 = optional_param('button1' . $entry->id, '', PARAM_TEXT); // Transfer entry.
            $param2 = optional_param('button2' . $entry->id, '', PARAM_TEXT); // Clear entry.

            // Add an entry label followed by the date of the entry.
            echo '<tr>';
            echo '<td style="width:35px;">' . get_string('entry', 'diary') . ':</td><td>';
            echo userdate($entry->timecreated);
            // 20201202 Added link to show all entries for a single user.
            echo '  <a href="reportsingle.php?id=' . $id
                . '&user=' . $user->id
                . '&action=allentries">' . get_string('reportsingle', 'diary')
                . '</a></td><td></td>';
            echo '</tr>';
        }

        // Add first of two rows, this one showing the user picture and users name.
        echo '<tr>';
        echo '<td class="userpix" rowspan="2">';
        echo $OUTPUT->user_picture(
            $user,
            [
                'courseid' => $course->id,
                'alttext' => true,
            ]
        );
        echo '</td>';
        echo '<td class="userfullname">' . fullname($user) . '<br>';
        echo '</td><td style="width:55px;"></td>';
        echo '</tr>';

        // Add the second of two rows, this one containing the users text for this entry.
        echo '<tr><td>';
        echo '<div class="entry" style="background: ' . $dcolor4 . ';">';

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
            [$autoratingdata,
                 $currentratingdata]
                 = diarystats::get_auto_rating_stats($temp, $diary);
            // 20211212 Added list function to get and print the autorating data here.
            echo $autoratingdata;
        } else {
            print_string('noentry', 'diary');
            // 20210701 Moved copy 2 of 2 here due to new stats.
            echo '</div></td><td style="width:55px;"></td></tr>';
        }

        echo '</table>';

        echo '<table class="diaryuserentry" id="entry-' . $user->id . '">';

        // If there is a user entry, add a teacher feedback area for grade
        // and comments. Add previous grades and comments, if available.
        if ($entry) {
            echo '<tr>';
            echo '<td class="userpix">';
            if (! $entry->teacher) {
                $entry->teacher = $USER->id;
            }
            if (empty($teachers[$entry->teacher])) {
                $teachers[$entry->teacher] = $DB->get_record(
                    'user',
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
            echo $OUTPUT->user_picture(
                $teachers[$entry->teacher],
                [
                    'courseid' => $course->id,
                    'alttext' => true,
                ]
            );
            echo '</td>';
            // 20210707 Added teachers name to go with their picture.
            // 20211027 Added button to add/delete auto grade stats and rating to feedback.
            echo '<td>' . $teachers[$entry->teacher]->firstname . ' ' . $teachers[$entry->teacher]->lastname .

                 ' <input class="btn btn-warning btn-sm"
                         role="button"
                         style="border-radius: 8px"
                         name="button1' . $entry->id . '"
                         onClick="return clClick()"
                         type="submit"
                         value="' . get_string('addtofeedback', 'diary') . '"></input> ' .

                 '<input class="btn btn-warning  btn-sm"
                         style="border-radius: 8px"
                         name="button2' . $entry->id . '"
                         onClick="return clClick()"
                         type="submit"
                         value="' . get_string('clearfeedback', 'diary') . '"></input>';

            // 20211228 Create a test anchor link for testing.
            // echo '<a href="#'.$entry->id.'">xxxxx</a>';

            // 20211228 Create an anchor right after Add/Clear buttons.
            echo  '<a id="' . $entry->id . '"></a>';
            echo '<br>' . get_string('rating', 'diary') . ':  ';

            $attrs = [];
            $hiddengradestr = '';
            $gradebookgradestr = '';
            $feedbackdisabledstr = '';
            $feedbacktext = $entry->entrycomment;
            // 20220107 If the, Add to feedback, button is clicked process it here.
            if (isset($param1) && get_string('addtofeedback', 'diary') == $param1) {
                // 20220105 Do an immediate update here.
                $entry->rating = $currentratingdata;
                $feedbacktext .= $statsdata . $comerrdata . $autoratingdata;
                $entry->entrycomment = $statsdata . $comerrdata . $autoratingdata;
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
                if ($rec = $DB->get_record('rating', ['itemid' => $entry->id])) {
                    $DB->delete_records('rating', ['itemid' => $entry->id]);
                    // 20220107 Recalculate the rating for this user for this diary activity.
                    diary_update_grades($diary, $entry->userid);
                }
            }

            // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
            $gradinginfo = grade_get_grades(
                $course->id,
                'mod',
                'diary',
                $entry->diary,
                [
                    $user->id,
                ]
            );

            if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
                if (
                    $gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked
                    || $gradinginfo->items[0]->grades[$user->id]->overridden
                ) {
                    $attrs['disabled'] = 'disabled';
                    $hiddengradestr = '<input type="hidden" name="r' . $entry->id . '" value="' . $entry->rating . '"/>';
                    $gradebooklink = '<a href="' . $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $course->id . '">';
                    $gradebooklink .= $gradinginfo->items[0]->grades[$user->id]->str_long_grade . '</a>';
                    $gradebookgradestr = '<br/>' . get_string("gradeingradebook", "diary") . ':&nbsp;' . $gradebooklink;

                    $feedbackdisabledstr = 'disabled="disabled"';
                    $feedbacktext = $gradinginfo->items[0]->grades[$user->id]->str_feedback;
                }
            }

            // 20210510 Modified Grade selector to check for Moodle version.
            $attrs['id'] = 'r' . $entry->id;
            echo html_writer::label(
                fullname($user) .
                " " .
                get_string(
                    'gradenoun'
                ),
                'r' .
                $entry->id,
                true,
                [
                    'class' => 'accesshide',
                ]
            );

            if ($diary->assessed > 0) {
                echo html_writer::select($grades, 'r' . $entry->id, $entry->rating, get_string("nograde") . '...', $attrs);
            }
            echo $hiddengradestr;

            // Rewrote next three lines to show entry needs to be regraded due to resubmission.
            if (! empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
                echo ' <span class="needsedit">' . get_string("needsregrade", "diary") . '</span>';
            } else if ($entry->timemarked) {
                echo ' <span class="lastedit"> ' . userdate($entry->timemarked) . '</span>';
            }
            echo $gradebookgradestr;

            // 20200816 Added overall rating type and rating.
            echo '<br>' . $aggregatestr . ' ' . $currentuserrating;

            // Feedback text.
            echo html_writer::label(
                fullname($user) .
                " " .
                get_string(
                    'feedback'
                ),
                'c' .
                $entry->id,
                true,
                [
                    'class' => 'accesshide',
                ]
            );
            echo '<p><textarea id="c' .
                $entry->id .
                '" name="c' .
                $entry->id .
                '" rows="6" cols="60" ' .
                $feedbackdisabledstr .
                '>';
            echo p($feedbacktext);
            echo '</textarea></p>';

            // 20210630 Switched from plain textarea to an editor.
            $editor = editors_get_preferred_editor(FORMAT_HTML);
            echo $editor->use_editor(
                'c' .
                $entry->id,
                [
                    'context' => $context,
                    'autosave' => false,
                ],
                [
                    'return_types' => FILE_EXTERNAL,
                ]
            );

            if ($feedbackdisabledstr != '') {
                echo '<input type="hidden" name="c' . $entry->id . '" value="' . $feedbacktext . '"/>';
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

        echo $OUTPUT->user_picture(
            $teacher,
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
        $gradinginfo = grade_get_grades(
            $course->id,
            'mod',
            'diary',
            $entry->diary,
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
            echo $entry->rating . '/' . number_format($gradinginfo->items[0]->grademax, 2);
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
        $maxfiles = 99; // Need to add some setting.
        $maxbytes = $course->maxbytes; // Need to add some setting.

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
     * Return normalized prompt mode value.
     *
     * @param stdClass $diary Diary settings.
     * @return int
     */
    public static function get_prompt_mode($diary) {
        $mode = isset($diary->promptmode) ? (int)$diary->promptmode : self::PROMPTMODE_SEQUENTIAL;
        if ($mode < self::PROMPTMODE_SEQUENTIAL || $mode > self::PROMPTMODE_RANDOMCOMPLETE) {
            $mode = self::PROMPTMODE_SEQUENTIAL;
        }
        return $mode;
    }

    /**
     * Return all prompt ids for a diary, ordered consistently.
     *
     * @param int $diaryid Diary id.
     * @return int[]
     */
    protected static function get_all_prompt_ids($diaryid) {
        global $DB;

        $records = $DB->get_records('diary_prompts', ['diaryid' => (int)$diaryid], 'datestart ASC, datestop ASC, id ASC', 'id');
        if (!$records) {
            return [];
        }

        return array_map('intval', array_keys($records));
    }

    /**
     * Return prompt ids that are currently open (within date window) for this diary.
     *
     * A prompt is open when now >= datestart (or datestart is 0) AND now <= datestop (or datestop is 0).
     *
     * @param int $diaryid Diary id.
     * @return int[]
     */
    protected static function get_open_prompt_ids($diaryid) {
        global $DB;

        $now = time();
        $sql = "SELECT id FROM {diary_prompts}
                 WHERE diaryid = :diaryid
                   AND (datestart = 0 OR datestart <= :now1)
                   AND (datestop = 0 OR datestop >= :now2)
                 ORDER BY datestart ASC, datestop ASC, id ASC";
        $params = ['diaryid' => (int)$diaryid, 'now1' => $now, 'now2' => $now];
        $records = $DB->get_records_sql($sql, $params);
        if (!$records) {
            return [];
        }

        return array_map('intval', array_keys($records));
    }

    /**
     * Return prompt ids completed by this user in this diary.
     *
     * Completion is based on entries linked to prompt ids.
     *
     * @param int $diaryid Diary id.
     * @param int $userid User id.
     * @return int[]
     */
    protected static function get_completed_prompt_ids($diaryid, $userid) {
        global $DB;

        if (empty($userid)) {
            return [];
        }

        $sql = "SELECT DISTINCT promptid
                  FROM {diary_entries}
                 WHERE diary = :diaryid
                   AND userid = :userid
                   AND promptid > 0";
        $params = ['diaryid' => (int)$diaryid, 'userid' => (int)$userid];
        $records = $DB->get_records_sql($sql, $params);
        if (!$records) {
            return [];
        }

        $ids = [];
        foreach ($records as $record) {
            $ids[] = (int)$record->promptid;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Return the user preference key used for random prompt persistence.
     *
     * @param int $diaryid Diary id.
     * @return string
     */
    protected static function get_random_prompt_preference_name($diaryid) {
        return 'diary_randomprompt_' . (int)$diaryid;
    }

    /**
     * Resolve a stable random prompt from the current available pool.
     *
     * @param stdClass $diary Diary settings.
     * @param int $userid User id.
     * @param array $allpromptids All prompt ids.
     * @param array $remaining Remaining prompt ids.
     * @param int $requestedpromptid Requested prompt id.
     * @return int
     */
    protected static function resolve_random_promptid($diary, $userid, array $allpromptids, array $remaining, $requestedpromptid = 0) {
        $pool = !empty($remaining) ? array_values($remaining) : array_values($allpromptids);
        if (empty($pool)) {
            return 0;
        }

        if (!empty($userid)) {
            $preference = self::get_random_prompt_preference_name((int)$diary->id);
            $assigned = (int)get_user_preferences($preference, 0, (int)$userid);
            if ($assigned > 0 && in_array($assigned, $pool)) {
                return $assigned;
            }

            shuffle($pool);
            $assigned = (int)reset($pool);
            set_user_preference($preference, $assigned, (int)$userid);
            return $assigned;
        }

        shuffle($pool);
        return (int)reset($pool);
    }

    /**
     * Resolve the current prompt using configured prompt mode.
     *
     * @param stdClass $diary Diary settings.
     * @param int $userid User id (optional).
     * @param int $requestedpromptid Prompt id requested by user/input (optional).
     * @return int
     */
    protected static function resolve_promptid_for_mode($diary, $userid = 0, $requestedpromptid = 0) {
        $mode = self::get_prompt_mode($diary);

        if ($mode === self::PROMPTMODE_SEQUENTIAL) {
            return self::get_current_promptid_sequential($diary);
        }

        $allpromptids = self::get_all_prompt_ids((int)$diary->id);
        if (empty($allpromptids)) {
            return 0;
        }

        // For all non-sequential modes, limit the selectable pool to currently-open prompts.
        $openpromptids = self::get_open_prompt_ids((int)$diary->id);

        $requestedpromptid = (int)$requestedpromptid;
        $completed = self::get_completed_prompt_ids((int)$diary->id, (int)$userid);
        // Remaining = open prompts not yet completed.
        $remaining = array_values(array_diff($openpromptids, $completed));

        if ($mode === self::PROMPTMODE_CHOICE) {
            if ($requestedpromptid > 0 && in_array($requestedpromptid, $openpromptids)) {
                return $requestedpromptid;
            }
            // Fall back to first open prompt, else first of all prompts.
            return !empty($openpromptids) ? (int)reset($openpromptids) : (int)reset($allpromptids);
        }

        if ($mode === self::PROMPTMODE_RANDOM) {
            return self::resolve_random_promptid($diary, (int)$userid, $openpromptids, $remaining, $requestedpromptid);
        }

        if ($mode === self::PROMPTMODE_COMPLETEALL) {
            if (empty($remaining)) {
                return 0;
            }
            if ($requestedpromptid > 0 && in_array($requestedpromptid, $remaining)) {
                return $requestedpromptid;
            }
            return (int)reset($remaining);
        }

        if ($mode === self::PROMPTMODE_CHOICECOMPLETE) {
            $required = isset($diary->requiredpromptcount) ? (int)$diary->requiredpromptcount : 0;
            if ($required < 0) {
                $required = 0;
            }

            if ($required === 0) {
                if ($requestedpromptid > 0 && in_array($requestedpromptid, $openpromptids)) {
                    return $requestedpromptid;
                }
                return !empty($openpromptids) ? (int)reset($openpromptids) : (int)reset($allpromptids);
            }

            $required = min($required, count($allpromptids));
            if (count($completed) >= $required) {
                return 0;
            }

            if (empty($remaining)) {
                return 0;
            }

            if ($requestedpromptid > 0 && in_array($requestedpromptid, $remaining)) {
                return $requestedpromptid;
            }

            return (int)reset($remaining);
        }

        if ($mode === self::PROMPTMODE_RANDOMCOMPLETE) {
            $required = isset($diary->requiredpromptcount) ? (int)$diary->requiredpromptcount : 0;
            if ($required < 0) {
                $required = 0;
            }

            if ($required === 0) {
                return self::resolve_random_promptid($diary, (int)$userid, $openpromptids, $remaining, $requestedpromptid);
            }

            $required = min($required, count($allpromptids));
            if (count($completed) >= $required) {
                return 0;
            }

            if (empty($remaining)) {
                return 0;
            }

            return self::resolve_random_promptid($diary, (int)$userid, $openpromptids, $remaining, $requestedpromptid);
        }

        return self::get_current_promptid_sequential($diary);
    }

    /**
     * Return a short plain-text prompt summary for compact prompt listings.
     *
     * @param stdClass $prompt Prompt record.
     * @return string
     */
    protected static function get_prompt_summary_text($prompt) {
        // Prefer the optional title field when it is set and non-empty.
        if (!empty($prompt->title)) {
            return clean_param(trim((string)$prompt->title), PARAM_TEXT);
        }
        $summary = trim(preg_replace('/\s+/', ' ', strip_tags((string)$prompt->text)));
        if ($summary === '') {
            return get_string('writingpromptlable2', 'diary');
        }
        return shorten_text($summary, 90, true, '...');
    }

    /**
     * Return the date-availability status of a prompt: 'open', 'future', or 'closed'.
     *
     * - 'future' : datestart is set and now is before it.
     * - 'closed' : datestop is set and now is after it.
     * - 'open'   : otherwise (no date constraints, or within the window).
     *
     * @param stdClass $prompt Prompt record.
     * @return string 'open'|'future'|'closed'
     */
    protected static function get_prompt_date_status($prompt) {
        $now = time();
        if (!empty($prompt->datestart) && $now < (int)$prompt->datestart) {
            return 'future';
        }
        if (!empty($prompt->datestop) && $now > (int)$prompt->datestop) {
            return 'closed';
        }
        return 'open';
    }

    /**
     * Render the compact prompt picker for non-sequential modes.
     *
     * @param stdClass $diary Diary settings.
     * @param array $promptsall Prompt records keyed by id.
     * @param int $selectedpromptid Selected/current prompt id.
     * @param int $userid User id.
     * @return string
     */
    protected static function render_prompt_picker($diary, array $promptsall, $selectedpromptid, $userid) {
        $promptmode = self::get_prompt_mode($diary);
        if ($promptmode === self::PROMPTMODE_SEQUENTIAL || empty($promptsall)) {
            return '';
        }

        $completed = self::get_completed_prompt_ids((int)$diary->id, (int)$userid);
        $allowselection = in_array($promptmode, [
            self::PROMPTMODE_CHOICE,
            self::PROMPTMODE_COMPLETEALL,
            self::PROMPTMODE_CHOICECOMPLETE,
        ]);

        if ($promptmode === self::PROMPTMODE_CHOICE) {
            $heading = get_string('promptmodepickerchoice', 'diary');
        } else if ($promptmode === self::PROMPTMODE_RANDOM) {
            $heading = get_string('promptmodepickerrandom', 'diary');
        } else if ($promptmode === self::PROMPTMODE_COMPLETEALL) {
            $heading = get_string('promptmodepickercompleteall', 'diary');
        } else if ($promptmode === self::PROMPTMODE_CHOICECOMPLETE) {
            $required = isset($diary->requiredpromptcount) ? (int)$diary->requiredpromptcount : 0;
            $required = max(0, min($required, count($promptsall)));
            $remainingrequired = max(0, $required - count($completed));
            if ($remainingrequired > 0) {
                $heading = get_string('promptmodepickerchoicecomplete', 'diary', [
                    'remaining' => $remainingrequired,
                    'required' => $required,
                ]);
            } else {
                $heading = get_string('promptmodepickerdone', 'diary');
            }
        } else {
            $required = isset($diary->requiredpromptcount) ? (int)$diary->requiredpromptcount : 0;
            $required = max(0, min($required, count($promptsall)));
            $remainingrequired = max(0, $required - count($completed));
            if ($remainingrequired > 0) {
                $heading = get_string('promptmodepickerrandomcomplete', 'diary', [
                    'remaining' => $remainingrequired,
                    'required' => $required,
                ]);
            } else {
                $heading = get_string('promptmodepickerdone', 'diary');
            }
        }

        $cmid = optional_param('id', 0, PARAM_INT);
        $items = [];
        $counter = 0;
        foreach ($promptsall as $prompt) {
            $counter++;
            $currentpromptid = (int)$prompt->id;
            $datestatus = self::get_prompt_date_status($prompt);
            $isopen = $datestatus === 'open';
            $classes = ['diary-prompt-choice'];
            if ($currentpromptid === (int)$selectedpromptid) {
                $classes[] = 'is-current';
            }
            if (in_array($currentpromptid, $completed)) {
                $classes[] = 'is-completed';
            }
            if (!$isopen) {
                $classes[] = 'is-unavailable';
            }

            // Pill label: use the optional title if set, otherwise "Prompt N".
            if (!empty($prompt->title)) {
                $pilllabel = clean_param(trim((string)$prompt->title), PARAM_TEXT);
            } else {
                $pilllabel = get_string('promptmodepickeritem', 'diary', [
                    'counter' => $counter,
                    'text' => self::get_prompt_summary_text($prompt),
                ]);
            }
            $label = $pilllabel;

            // Tooltip: always show a preview of the body text so hovering reveals the prompt content.
            $bodypreview = trim(preg_replace('/\s+/', ' ', strip_tags((string)$prompt->text)));
            $tooltiptext = shorten_text($bodypreview, 200, true, '...');

            $badges = [];
            if ($currentpromptid === (int)$selectedpromptid) {
                $badges[] = html_writer::span(get_string('promptmodepickercurrent', 'diary'), 'diary-prompt-choice-badge');
            }
            if (in_array($currentpromptid, $completed)) {
                $badges[] = html_writer::span(get_string('promptmodepickercompleted', 'diary'), 'diary-prompt-choice-badge');
            }
            if ($datestatus === 'future') {
                $badges[] = html_writer::span(get_string('promptdatestatusfuture', 'diary'), 'diary-prompt-choice-badge is-future');
            } else if ($datestatus === 'closed') {
                $badges[] = html_writer::span(get_string('promptdatestatusclosed', 'diary'), 'diary-prompt-choice-badge is-closed');
            }
            $badgehtml = implode('', $badges);

            // Only open, unselected, unfinished prompts are clickable links.
            if ($allowselection && $cmid > 0 && $isopen && !in_array($currentpromptid, $completed)) {
                $url = new \moodle_url('/mod/diary/view.php', [
                    'id' => $cmid,
                    'action' => 'currententry',
                    'promptid' => $currentpromptid,
                ]);
                $content = html_writer::link($url, $label, ['class' => 'diary-prompt-choice-link']) . $badgehtml;
            } else {
                $content = html_writer::span($label, 'diary-prompt-choice-text') . $badgehtml;
            }

            $items[] = html_writer::tag('li', $content, [
                'class' => implode(' ', $classes),
                'data-pickertitle' => $tooltiptext,
            ]);
        }

        $output = html_writer::div(
            html_writer::tag('strong', $heading),
            'diary-prompt-picker-heading'
        );
        $output .= html_writer::tag('ul', implode('', $items), ['class' => 'diary-prompt-picker']);
        return html_writer::div($output, 'diary-prompt-picker-wrap');
    }

    /**
     * Original sequential current-prompt resolver (date-window based).
     *
     * @param stdClass $diary Diary settings.
     * @return int
     */
    protected static function get_current_promptid_sequential($diary) {
        global $DB;

        $promptid = 0;
        $promptsall = $DB->get_records(
            'diary_prompts',
            ['diaryid' => $diary->id],
            'datestart ASC, datestop ASC'
        );

        if (!$promptsall) {
            return 0;
        }

        foreach ($promptsall as $prompts) {
            if (($prompts->datestart < time()) && $prompts->datestop > time()) {
                $promptid = (int)$prompts->id;
            }
        }

        return $promptid;
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
                add_to_log(
                    $cm->id,
                    'diary',
                    'remove prompt',
                    "view.php?id={$cm->id}&round=$rid",
                    $rid,
                    $cm->id
                );
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
     * @param stdClass $diary The settings for this diary activity.
     * @param string $action Current page action.
     * @param int $promptid Current prompt id.
     * @return string
     */
    public static function prompts_viewcurrent($diary, $action, $promptid) {
        global $CFG, $DB, $USER;
        // Prefer passed values, but keep request fallback for existing call patterns.
        $requestaction = optional_param('action', '', PARAM_ALPHANUMEXT);
        if (empty($action) && $requestaction !== '') {
            $action = $requestaction;
        }
        if (empty($action)) {
            $action = 'currententry';
        }
        $firstkey = optional_param('firstkey', 0, PARAM_INT); // Which diary_entries id to edit.
        if (empty($promptid)) {
            $promptid = optional_param('promptid', 0, PARAM_INT); // Current entries promptid.
        }

        $data = new stdClass();
        $output = '';
        $line = [];
        $counter = 0;
        $past = 0;
        $current = 0;
        $future = 0;

        $entry = $DB->get_record('diary_entries', ['id' => $firstkey, 'diary' => $diary->id]);
        $promptsall = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'datestart ASC, datestop ASC');
        $promptsone = $DB->get_record('diary_prompts', ['id' => $promptid, 'diaryid' => $diary->id]);
        $promptmode = self::get_prompt_mode($diary);
        if ($promptmode !== self::PROMPTMODE_SEQUENTIAL && !empty($promptsone) && !empty($promptid)) {
            $action = 'editentry';
        }
        $bordercssvars = \diary_get_border_css_vars((int)$diary->id);

        $diary->intro = '';
        if ($promptmode !== self::PROMPTMODE_SEQUENTIAL && !empty($promptsall)) {
            $diary->intro .= self::render_prompt_picker($diary, $promptsall, (int)$promptid, (int)$USER->id);
        }
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

                if ((($prompts->datestart < time()) && $prompts->datestop > time()) && ($action <> 'editentry')) {
                    $data->entryid = $prompts->id;
                    $data->id = $prompts->id;
                    $data->diaryid = $prompts->diaryid;
                    $data->datestart = $prompts->datestart;
                    $data->datestop = $prompts->datestop;
                    $data->text = $prompts->text;
                    $data->format = FORMAT_HTML;
                    $data->promptbgc = $prompts->promptbgc;
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

                    $start = '<td>' . userdate($data->datestart) . '</td>';
                    $stop = '<td>' . userdate($data->datestop) . '</td>';

                    $prompttext = '<div class="promptentry diary-prompt-themed" style="--diary-prompt-bg: '
                        . s($data->promptbgc) . ';' . s($bordercssvars) . '"><td>' .
                        get_string(
                            'writingpromptlable',
                            'diary',
                            [
                                'counter' => $counter,
                                'entryid' => $data->entryid,
                                'starton' => $start,
                                'endon' => $stop,
                                'datatext' => '<b>' . $data->text . '</b>',
                            ]
                        ) .
                        '<br><br></td>';
                    $characters = '<td>' . get_string('chars', 'diary')
                                  . get_string('minc', 'diary') . $data->minchar
                                  . get_string('maxc', 'diary') . $data->maxchar
                                  . get_string('errp', 'diary') . $data->minmaxcharpercent . ', </td>';
                    $words = '<td>' . get_string('words', 'diary')
                             . get_string('minc', 'diary') . $data->minword
                             . get_string('maxc', 'diary') . $data->maxword
                             . get_string('errp', 'diary') . $data->minmaxwordpercent . ', </td>';
                    $sentences = '<td>' . get_string('sentences', 'diary')
                                 . get_string('minc', 'diary') . $data->minsentence
                                 . get_string('maxc', 'diary') . $data->maxsentence
                                  . get_string('errp', 'diary') . $data->minmaxsentencepercent . ', </td>';
                    $paragraphs = '<td>' . get_string('paragraphs', 'diary')
                                  . get_string('minc', 'diary') . $data->minparagraph
                                  . get_string('maxc', 'diary') . $data->maxparagraph
                                  . get_string('errp', 'diary') . $data->minmaxparagraphpercent . '</td>';
                    $editlimitnote = diarystats::get_edit_limit_note_html($diary, (int)$data->id);
                    if ($editlimitnote !== '') {
                        $paragraphs .= '<br>' . $editlimitnote;
                    }
                    $paragraphs .= '</div>';

                    $status .= $status . $prompttext . $characters . $words . $sentences . $paragraphs;
                    if ($status) {
                        $diary->intro .= $status . '<hr>';
                    }
                } else if (
                    (($action == 'editentry') && ($prompts->id == $promptid))
                    || (($action == 'editentry') && ($prompts->id == $promptid))
                ) {
                    // To get the last use case to work, I think I will also need to include $firstkey
                    // so that I can check to see what the $entry->promptid is and use it to
                    // compare to $promptid.

                    $data->entryid = $prompts->id;
                    $data->id = $prompts->id;
                    $data->diaryid = $prompts->diaryid;
                    $data->datestart = $prompts->datestart;
                    $data->datestop = $prompts->datestop;
                    $data->text = $prompts->text;
                    $data->format = FORMAT_HTML;
                    $data->promptbgc = $prompts->promptbgc;
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

                    $start = '<td>' . userdate($data->datestart) . '</td>';
                    $stop = '<td>' . userdate($data->datestop) . '</td>';

                    $prompttext = '<div class="promptentry diary-prompt-themed" style="--diary-prompt-bg: '
                        . s($data->promptbgc) . ';' . s($bordercssvars) . '"><b><td>' .
                        get_string(
                            'writingpromptlable',
                            'diary',
                            [
                                'counter' => $counter,
                                'entryid' => $data->entryid,
                                'starton' => $start,
                                'endon' => $stop,
                                'datatext' => '</b>' . $data->text,
                            ]
                        ) .
                        '<br><br></td>';
                    $characters = '<td>' . get_string('chars', 'diary')
                                  . get_string('minc', 'diary') . $data->minchar
                                  . get_string('maxc', 'diary') . $data->maxchar
                                  . get_string('errp', 'diary') . $data->minmaxcharpercent . ', </td>';
                    $words = '<td>' . get_string('words', 'diary')
                             . get_string('minc', 'diary') . $data->minword
                             . get_string('maxc', 'diary') . $data->maxword
                             . get_string('errp', 'diary') . $data->minmaxwordpercent . ', </td>';
                    $sentences = '<td>' . get_string('sentences', 'diary')
                                 . get_string('minc', 'diary') . $data->minsentence
                                 . get_string('maxc', 'diary') . $data->maxsentence
                                  . get_string('errp', 'diary') . $data->minmaxsentencepercent . ', </td>';
                    $paragraphs = '<td>' . get_string('paragraphs', 'diary')
                                  . get_string('minc', 'diary') . $data->minparagraph
                                  . get_string('maxc', 'diary') . $data->maxparagraph
                                  . get_string('errp', 'diary') . $data->minmaxparagraphpercent . '</td>';
                    $editlimitnote = diarystats::get_edit_limit_note_html($diary, (int)$data->id);
                    if ($editlimitnote !== '') {
                        $paragraphs .= '<br>' . $editlimitnote;
                    }
                    $paragraphs .= '</div>';

                    $status .= $status . $prompttext . $characters . $words . $sentences . $paragraphs;
                    if ($status) {
                        $diary->intro .= $status . '<hr>';
                    }
                }
            }
        }
        return;
    }

    /**
     * Return all autograde rules for a prompt.
     *
     * @param int $promptid Prompt id.
     * @return array
     */
    public static function get_autograde_rules($promptid) {
        global $DB;

        return $DB->get_records(
            'diary_prompt_autograde_rules',
            ['promptid' => (int)$promptid],
            'sortorder ASC, id ASC'
        );
    }

    /**
     * Return one autograde rule by id.
     *
     * @param int $ruleid Rule id.
     * @param int $promptid Optional prompt id guard.
     * @return false|object
     */
    public static function get_autograde_rule($ruleid, $promptid = 0) {
        global $DB;

        $params = ['id' => (int)$ruleid];
        if (!empty($promptid)) {
            $params['promptid'] = (int)$promptid;
        }
        return $DB->get_record('diary_prompt_autograde_rules', $params);
    }

    /**
     * Delete one autograde rule.
     *
     * @param int $ruleid Rule id.
     * @param int $promptid Optional prompt id guard.
     * @return bool
     */
    public static function delete_autograde_rule($ruleid, $promptid = 0) {
        global $DB;

        $params = ['id' => (int)$ruleid];
        if (!empty($promptid)) {
            $params['promptid'] = (int)$promptid;
        }
        return $DB->delete_records('diary_prompt_autograde_rules', $params);
    }

    /**
     * Insert or update one autograde rule.
     *
     * @param object $rule Rule record.
     * @return int Rule id.
     */
    public static function save_autograde_rule($rule) {
        global $DB, $USER;

        $now = time();
        $record = new stdClass();
        if (!empty($rule->id)) {
            $record->id = (int)$rule->id;
        }
        $record->diaryid = (int)$rule->diaryid;
        $record->promptid = (int)$rule->promptid;
        $record->phrase = trim((string)$rule->phrase);
        $record->matchtype = self::normalize_matchtype($rule->matchtype ?? 0);
        $record->casesensitive = empty($rule->casesensitive) ? 0 : 1;
        $record->fullmatch = empty($rule->fullmatch) ? 0 : 1;
        $record->ignorebreaks = empty($rule->ignorebreaks) ? 0 : 1;
        $record->weightpercent = max(0, (int)($rule->weightpercent ?? 0));
        $record->required = empty($rule->required) ? 0 : 1;
        $record->studentvisible = isset($rule->studentvisible) ? (empty($rule->studentvisible) ? 0 : 1) : 1;
        $record->sortorder = max(0, (int)($rule->sortorder ?? 0));
        $record->usermodified = (int)$USER->id;
        $record->timemodified = $now;

        if (!empty($record->id)) {
            $DB->update_record('diary_prompt_autograde_rules', $record);
            return $record->id;
        }

        if (empty($record->sortorder)) {
            $record->sortorder = self::next_autograde_rule_sortorder($record->promptid);
        }
        $record->timecreated = $now;
        return $DB->insert_record('diary_prompt_autograde_rules', $record);
    }

    /**
     * Calculate next sort order value for a prompt rule.
     *
     * @param int $promptid Prompt id.
     * @return int
     */
    public static function next_autograde_rule_sortorder($promptid) {
        global $DB;

        $max = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {diary_prompt_autograde_rules} WHERE promptid = ?',
            [(int)$promptid]
        );
        if ($max === false || $max === null) {
            return 1;
        }
        return ((int)$max) + 1;
    }

    /**
     * Normalize matchtype to known values.
     *
     * @param int $matchtype Match mode.
     * @return int
     */
    public static function normalize_matchtype($matchtype) {
        $matchtype = (int)$matchtype;
        if ($matchtype < 0 || $matchtype > 2) {
            return 0;
        }
        return $matchtype;
    }

    /**
     * Is there a current prompt?
     *
     * @param array $diary The settings for this diary activity.
     * @return int $promptid The current promptid or zero if not available.
     */
    public static function get_current_promptid($diary, $userid = 0, $requestedpromptid = 0) {
        return self::resolve_promptid_for_mode($diary, (int)$userid, (int)$requestedpromptid);
    }
}
