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
 * Transfer Journal entries to Diary entries.
 *
 * Lists Journals and Diary's in a course where an admin can
 * transfer Journal entries and make them Diary entries.
 *
 * @package    mod_diary
 * @copyright  2021 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

use mod_diary\event\course_module_viewed;

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

global $DB, $OUTPUT, $PAGE, $USER;

// Fetch URL parameters.
$id = optional_param('id', 0, PARAM_INT); // Course ID.
$xfrcountck = 0;
$xfrcountxfrd = 0;
if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$diary = $DB->get_record('diary', ['id' => $cm->instance], '*', MUST_EXIST);

// 20211109 Check to see if Transfer the entries button is clicked and returning 'Transfer the entries' to trigger insert record.
$param1 = optional_param('button1', '', PARAM_TEXT); // Transfer entry.
$param2 = optional_param('button2', '', PARAM_TEXT); // Not currently used.
$param3 = optional_param('transferwemail', '', PARAM_TEXT); // Transfer with email.
$param4 = optional_param('transferwfb', '', PARAM_TEXT); // Transfer with feedback.

// DB transfer.
if (isset($param1) && get_string('transfer', 'diary') == $param1) {
    $journalfromid = optional_param('journalid', '', PARAM_INT);
    $diarytoid = optional_param('diaryid', '', PARAM_INT);

    $sql = 'SELECT *
              FROM {journal_entries} je
             WHERE je.journal = :journalid
          ORDER BY je.id ASC';

    // 20211112 Check and make sure journal transferring from and diary transferring too, actually exist.
    // Verify journal and diary exists.
    if (($journalck = $DB->get_record('journal', ['id' => $journalfromid], '*', MUST_EXIST))
        && ($diaryto = $DB->get_record('diary', ['id' => $diarytoid], '*', MUST_EXIST))) {

        // 20211113 Adding transferred from note to the feedback via $feedbacktag, below.
        $journalck = $DB->get_record('journal', ['id' => $journalfromid], '*', MUST_EXIST);
        $journalentries = $DB->get_records_sql($sql, ['journalid' => $journalfromid]);

        foreach ($journalentries as $journalentry) {
            $feedbacktag = new stdClass();
            $xfrcountck++;
            if ($param4 === "checked") {
                // If enabled, transfer message will be added to the feedback.
                $feedbacktag = get_string('transferwfbmsg', 'diary', ($journalck->name));
            } else {
                // By default, transfer message is not added to the feedback.
                $feedbacktag = '';
            }

            $newdiaryentry = new stdClass();
            $newdiaryentry->diary = $diarytoid;
            $newdiaryentry->userid = $journalentry->userid;
            // Journal entries do not have a timecreated so using last modified time.
            $newdiaryentry->timecreated = $journalentry->modified;
            $newdiaryentry->timemodified = $journalentry->modified;
            $newdiaryentry->text = $journalentry->text;
            $newdiaryentry->format = $journalentry->format;
            if ($journalentry->rating) {
                $newdiaryentry->rating = $journalentry->rating;
                $newdiaryentry->entrycomment = $journalentry->entrycomment.$feedbacktag;
                $newdiaryentry->teacher = $journalentry->teacher;
                $newdiaryentry->timemarked = $journalentry->timemarked;
            }

            if ($param3 === 'checked') {
                $newdiaryentry->mailed = 0;
            } else {
                $newdiaryentry->mailed = $journalentry->mailed;
            }

            // 20211112 Check to see if the diary entry record already exists.
            $sql = 'SELECT case when "text" = "$journalentry->text" then "True" else "False" end
                      FROM {diary_entries} de
                     WHERE de.diary = $diarytoid
                       AND de.userid = $journalentry->userid
                       AND de.timemodified = $journalentry->modified
                  ORDER BY de.id ASC';
            if (!$DB->record_exists('diary_entries',
                [
                    'diary' => $diarytoid,
                    'userid' => $journalentry->userid,
                    'timemodified' => $journalentry->modified,
                ]
            )) {
                // 20211228 Bump count of transfers.
                $xfrcountxfrd++;
                // 20211228 Create and insert a new Diary entry from the old Journal entry.
                $DB->insert_record('diary_entries', $newdiaryentry, false);
                // Possibly need to log the event that a journal entry was transfered to a diary here.
            }
        }
    }

    // Trigger transferred journal entries to diary entries event.
    $event = \mod_diary\event\journal_to_diary_entries_transfer::create(
        [
            'objectid' => $diary->id,
            'context' => $context,
            'other' =>
                [
                    'journalname' => $param1,
                    'diaryname' => $diaryto->name,
                    'diaryto' => $diaryto->id,
                    'jeprocessed' => $xfrcountck,
                    'jexfrd' => $xfrcountxfrd,
                ],
        ]
    );
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();
}

// Print the page header.
$PAGE->set_url('/mod/diary/journaltodiaryxfr.php', ['id' => $id]);

$PAGE->set_heading($course->fullname);
// Output starts here.
echo $OUTPUT->header();

// 20211108 Get the a background default Diary text background color for our table background here.
$color3 = $diary->entrytextbgc;

// Add colored background with border.
echo '<div class="w-75 p-3" style="font-size:1em;
    background: '.$color3.';
    border:2px solid black;
    -webkit-border-radius:16px;
    -moz-border-radius:16px;border-radius:16px;">';

// Start page form and add lesson name selector.
echo '<form method="POST">';

// 20211105 Setup a url that takes you back to the Diary you came from.
// 20230810 Changed based on pull request #29.
$url1 = new moodle_url($CFG->wwwroot.'/mod/diary/view.php', ['id' => $id]);
$url2 = new moodle_url($CFG->wwwroot.'/mod/diary/journaltodiaryxfr.php', ['id' => $cm->id]);

// 20211202 Add some instructions and information to the page.
echo '<h3 style="text-align:center;"><b>'.get_string('journaltodiaryxfrtitle', 'diary').'</b></h3>';
echo get_string('journaltodiaryxfrp1', 'diary');
echo get_string('journaltodiaryxfrp2', 'diary');
echo get_string('journaltodiaryxfrp3', 'diary');
echo get_string('journaltodiaryxfrp4', 'diary', ['one' => $course->fullname, 'two' => $cm->course]);
echo get_string('journaltodiaryxfrp5', 'diary');

$jsql = 'SELECT *
           FROM {journal} j
          WHERE j.course = :course
       ORDER BY j.id ASC';

$journals = $DB->get_records_sql($jsql, ['course' => $cm->course]);

echo get_string('journaltodiaryxfrjid', 'diary');

if ($journals) {
    foreach ($journals as $journal) {
        echo '<b>    '.$journal->id.'</b>  '.$journal->course.'  '.$journal->name.'<br>';
    }
} else {
    echo '<b>'.get_string('journalmissing', 'diary').'</b><br>';
}

$dsql = 'SELECT *
           FROM {diary} d
          WHERE d.course = :course
       ORDER BY d.id ASC';

$diarys = $DB->get_records_sql($dsql, ['course' => $cm->course]);

echo get_string('journaltodiaryxfrdid', 'diary');

foreach ($diarys as $diary) {
    echo '<b>    '.$diary->id.'</b>  '.$diary->course.'  '.$diary->name.'<br>';
}
    // Set up place to enter Journal ID to transfer entries from.
    echo '<br><br>'.get_string('journalid', 'diary').': <input type="text"
                                                               name="journalid"
                                                               id="journalid">';

    // Set up place to enter Diary ID to transfer entries to.
    echo '<br><br>'.get_string('diaryid', 'diary').': <input type="text"
                                                             name="diaryid"
                                                             id="diaryid">';


    // Set up option to send email showing the entry is transfered.
    echo '<br><br><input type="checkbox"
                         name="transferwemail"
                         id="transferwemail"
                         value="checked">'
                         .get_string('transferwemail', 'diary');

    // Set up option to include feedback that the entry was transferred.
    echo '<br><input type="checkbox"
                     name="transferwfb"
                     id="transferwfb"
                     value="checked">'
                     .get_string('transferwfb', 'diary');

// Add a transfer button.
// Add a cancel button that clears the input boxes and reloads the page.
// 20230810 Changed based on pull request #29.
echo '<br><br><input class="btn btn-warning"
                     style="border-radius: 8px"
                     name="button1"
                     onClick="return clClick()"
                     type="submit" value="'
                     .get_string('transfer', 'diary').'"> <a href="'.$url2->out(false).'"
                     class="btn btn-secondary"
                     style="border-radius: 8px">'
                     .get_string('cancel', 'diary').'</a></input>';

// 20211206 Added results so the admin knows what has occured.
if ($xfrcountck > 0) {
    $xfrresults = get_string('xfrresults', 'diary', ['one' => $xfrcountck , 'two' => $xfrcountxfrd]);
} else {
    $xfrresults = '';
}
// 20230810 Changed based on pull request #29.
echo '<br><br><a href="'.$url1->out(false)
    .'" class="btn btn-success" style="border-radius: 8px">'
    .get_string('returnto', 'diary', $diary->name)
    .'</a> '.$xfrresults;
echo '</form>';
echo '</div>';

echo $OUTPUT->footer();
