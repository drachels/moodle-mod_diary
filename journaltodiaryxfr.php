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
 * Lists Journals that can be transfer entries to a Diary.
 *
 * @package    mod_diary
 * @copyright  2021 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

use \mod_diary\event\course_module_viewed;

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

global $DB, $OUTPUT, $PAGE, $USER;

// Fetch URL parameters.
$id = optional_param('id', 0, PARAM_INT); // Course ID.

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$diary = $DB->get_record('diary', array('id' => $cm->instance) , '*', MUST_EXIST);

// Setup up debugging array.
//$debug = array();
/*
print_object('spacer 1');
print_object('spacer 2');
print_object('spacer 3');
print_object('spacer 4');
print_object('spacer 5');
*/

// 20211109 Check to see if Transfer the entries button is clicked and returning 'Transfer the entries' to trigger insert record.
$param1 = optional_param('button1', '', PARAM_TEXT);
$param2 = optional_param('button2', '', PARAM_TEXT);
$param3 = optional_param('transferwemail', '', PARAM_TEXT);
$param4 = optional_param('transferwfb', '', PARAM_TEXT);

// DB transfer.
//if ((isset($param1) && get_string('transfer', 'diary') == $param1) ||
//    (isset($param2) && get_string('transferwoe', 'diary') == $param2)) {
if (isset($param1) && get_string('transfer', 'diary') == $param1)  {

    $journalfromid = optional_param('journalid', '', PARAM_RAW);
    $diarytoid = optional_param('diaryid', '', PARAM_RAW);

    //$debug['This is $param1: '] = $param1;
    //$debug['This is $param2: '] = $param2;
    //$debug['This is $param3: '] = $param3;
    //$debug['This is $param4: '] = $param4;
    //$debug['This is $journalfromid: '] = $journalfromid;
    //$debug['This is $diarytoid: '] = $diarytoid;

    $sql = 'SELECT *
              FROM {journal_entries} je
             WHERE je.journal = '.$journalfromid.'
          ORDER BY je.id ASC';

    // 20211112 Check and make sure journal transferring from and diary transferring too, actually exist.

    // Need to see about adding the courseid to the check also.
    // Verify journal and diary exists.
    if (($journalck = $DB->get_record('journal', array('id' => $journalfromid), '*', MUST_EXIST))
        && ($DB->get_record('diary', array('id' => $diarytoid), '*', MUST_EXIST))) {

        // 20211113 Adding transferred from note to the feedback via $feedbacktag, below.
        $journalck = $DB->get_record('journal', array('id' => $journalfromid), '*', MUST_EXIST);
        $journalentries = $DB->get_records_sql($sql);

        foreach ($journalentries as $journalentry) {
            $feedbacktag = new stdClass();
            //if (($param4 === "checked") && get_string('transferwfb', 'diary') == $param4) {
            if ($param4 === "checked") {
                // If enabled, transfer message will be added to the feedback.
                $feedbacktag = get_string('transferwfbmsg', 'diary', ($journalck->name));
                //$debug['This is $param4 and it should be checked to see this entry: '] = $param4;
            } else {
               // By default, transfer message is not added to the feedback.
               $feedbacktag = '';
               //$debug['This is $param4 and it should be blank: '] = $param4;
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

            } else {
                //$now = time();
                //$newdiaryentry->entrycomment = $feedbacktag;
                //$newdiaryentry->teacher = $USER->id;
                //$newdiaryentry->timemarked = userdate($now);
                //$newdiaryentry->timemarked = $now;

            }
            //$newdiaryentry->entrycomment = $journalentry->entrycomment.$feedbacktag;
            //$newdiaryentry->timemarked = $journalentry->timemarked;
            //if ($param1) {
            if ($param3 === 'checked') {
                //print_object('Entry will be transferred and an email sent to the user.');
                $newdiaryentry->mailed = 0;
                //$debug['This is $param3 and it should be checked to see this entry: '] = $param3;

            } else {
                $newdiaryentry->mailed = $journalentry->mailed;
                //$debug['This is $param3 and it should be blank: '] = $param3;

            //} else if ($param2) {
            //    print_object('Entry will be transferred without sending an email to the user.');
            //    $newdiaryentry->mailed = 1;
            }
            //$debug['This is the $newdiaryentry for user: '.$newdiaryentry->userid.': '] = $newdiaryentry;

            // 20211112 Check to see if the diary entry record already exists.
            $sql = 'SELECT case when "text" = "$journalentry->text" then "True" else "False" end
                      FROM {diary_entries} de
                     WHERE de.diary = $diarytoid
                       AND de.userid = $journalentry->userid
                       AND de.timemodified = $journalentry->modified
                  ORDER BY de.id ASC';
            if ($DB->record_exists('diary_entries', ['diary' => $diarytoid,
                                                     'userid' => $journalentry->userid,
                                                     'timemodified' => $journalentry->modified])) {
                // Possibly need to log the event that a journal entry transfer failed here.
                // Hardcoded text needs to be changed to a string.
                //print_object('The current record already exists in this Diary, so no transfer!');
            } else {
                // Hardcoded text needs to be changed to a string.
                //print_object('The current record does not exist, or is not an exact duplicate, so adding it to this Diary.');
                $DB->insert_record('diary_entries', $newdiaryentry, false);
                // Possibly need to log the event that a journal entry was transfered to a diary here.
            }
           
            //$DB->insert_record('diary_entries', $newdiaryentry, false);
            //print_object($debug);
        }
    }
}

//print_object($debug);

// Print the page header.
$PAGE->set_url('/mod/diary/journaltodiaryxfr.php', array('id' => $id));

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
$url1 = $CFG->wwwroot . '/mod/diary/view.php?id='.$id;
$url2 = $CFG->wwwroot . '/mod/diary/journaltodiaryxfr.php?id='.$cm->id;
// 20211202 Add some instructions and information to the page.
echo '<h3 style="text-align:center;"><b>'.get_string('journaltodiaryxfrtitle', 'diary').'</b></h3>';
//echo get_string('journaltodiaryxfrtitle', 'diary');
echo get_string('journaltodiaryxfrp1', 'diary');
echo get_string('journaltodiaryxfrp2', 'diary');
echo get_string('journaltodiaryxfrp3', 'diary');
echo get_string('journaltodiaryxfrp4', 'diary', ['one' => $course->fullname, 'two' => $cm->course]);
echo get_string('journaltodiaryxfrp5', 'diary');

$jsql = 'SELECT *
           FROM {journal} j
          WHERE j.course = '.$cm->course.'
       ORDER BY j.id ASC';

$journals = $DB->get_records_sql($jsql);

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
          WHERE d.course = '.$cm->course.'
       ORDER BY d.id ASC';

$diarys = $DB->get_records_sql($dsql);

echo get_string('journaltodiaryxfrdid', 'diary');

foreach ($diarys as $diary) {
    echo '<b>    '.$diary->id.'</b>  '.$diary->course.'  '.$diary->name.'<br>';
}
    // Set up place to enter Journal ID to transfer entries from.
    echo '<br><br>'.get_string('journalid', 'diary').': <input type="text" name="journalid" id="journalid">';

    // Set up place to enter Diary ID to transfer entries to.
    echo '<br><br>'.get_string('diaryid', 'diary').': <input type="text" name="diaryid" id="diaryid">';


    // Set up option to send email showing the entry is transfered.
    echo '<br><br><input type="checkbox" name="transferwemail" id="transferwemail" value="checked">'.get_string('transferwemail', 'diary');

    // Set up option to include feedback that the entry was transferred.
    echo '<br><input type="checkbox" name="transferwfb" id="transferwfb" value="checked">'.get_string('transferwfb', 'diary');

// Add a confirm transfer and send an email button.
// Add a confirm transfer without sending an email button.
// Add a cancel button that clears the input boxes and reloads the page.
/*echo '<br><br><input
         class="btn btn-warning"
         style="border-radius: 8px"
         name="button"
         onClick="return clClick()"
         type="submit" value="'
         .get_string('transfer', 'diary').'"> <a href="'.$url2.'"</a></input>

         <input
         class="btn btn-warning"
         style="border-radius: 8px"
         name="button2"
         onClick="return clClick()"
         type="submit" value="'
         .get_string('transferwoe', 'diary').'"> <a href="'.$url2.'"

         class="btn btn-secondary"
         style="border-radius: 8px">'
        .get_string('cancel', 'diary').'</a></input>';
*/
echo '<br><br><input
         class="btn btn-warning"
         style="border-radius: 8px"
         name="button1"
         onClick="return clClick()"
         type="submit" value="'
         .get_string('transfer', 'diary').'"> <a href="'.$url2.'"

         class="btn btn-secondary"
         style="border-radius: 8px">'
        .get_string('cancel', 'diary').'</a></input>';

echo '<br><br><a href="'.$url1
    .'" class="btn btn-success" style="border-radius: 8px">'
    .get_string('returnto', 'diary', $diary->name)
    .'</a><br><br></form>';

echo '</div>';
echo $OUTPUT->footer();
