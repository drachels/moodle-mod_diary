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

global $DB, $OUTPUT, $PAGE;

// Fetch URL parameters.
$id = optional_param('id', 0, PARAM_INT); // Course ID.

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    print_error("Course Module ID was incorrect");
}
if (! $course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error("Course is misconfigured");
}
require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$diary = $DB->get_record('diary', array('id' => $cm->instance) , '*', MUST_EXIST);

// 20211109 Check to see if Transfer the entries button is clicked and returning 'Transfer the entries' to trigger insert record.
$param1 = optional_param('button', '', PARAM_TEXT);

// DB transfer.
if (isset($param1) && get_string('transfer', 'diary') == $param1 ) {
    $journalfromid = optional_param('journalid', '', PARAM_RAW);
    $diarytoid = optional_param('diaryid', '', PARAM_RAW);
    print_object('This is $param1: '.$param1);
    print_object('This is $journalfromid: '.$journalfromid);
    print_object('This is $diarytoid: '.$diarytoid);

    $sql = 'SELECT *
              FROM {journal_entries} je
             WHERE je.journal = '.$journalfromid.'
          ORDER BY je.id ASC';

    if ($journalck = $DB->get_record('journal', array('id' => $journalfromid), '*', MUST_EXIST)) {
    //    echo $journalfromid.' is not a valid Journal ID!';
    //    die;
    //} else {
        $journalentries = $DB->get_records_sql($sql);
        foreach ($journalentries as $journalentry) {
            echo $journalentry->id.'  '.$journalentry->journal.'  '.$journalentry->userid.'  '.$journalentry->modified.' '.$journalentry->text.'<br>';

            $newdiaryentry = new stdClass();
            $newdiaryentry->diary = $diarytoid;
            $newdiaryentry->userid = $diarytoid;
            $newdiaryentry->timecreated = $journalentry->modified;
            $newdiaryentry->timemodified = $journalentry->modified;
            $newdiaryentry->text = $journalentry->text;
            $newdiaryentry->format = $journalentry->format;
            $newdiaryentry->rating = $journalentry->rating;
            $newdiaryentry->entrycomment = $journalentry->entrycomment;
            $newdiaryentry->teacher = $journalentry->teacher;
            $newdiaryentry->timemarked = $journalentry->timemarked;
            $newdiaryentry->mailed = $journalentry->mailed;
            print_object($newdiaryentry);
            // Check to see if the record already exists.

            $sql = 'SELECT *
                      FROM {diary_entries} de
                     WHERE de.diary = $diarytoid
                       AND de.userid = $diarytoid
                       AND de.timemodified = $journalentry->modified
                       AND de.text = $journalentry->text
                  ORDER BY de.id ASC';
print_object('the record already exists!');
            
            //if (! $DB->record_exists('diary_entries', array ($newdiaryentry=null))) {
            //    $DB->insert_record('diary_entries', $newdiaryentry, false);
            //} else {
            //    echo 'Found existing record!';
            //}
        }
        //die;
    }
}

// Print the page header.
$PAGE->set_url('/mod/diary/journaltodiaryxfr.php', array('id' => $id));
//$PAGE->set_pagelayout('course');

//$PAGE->set_title(get_string('etitle', 'diary'));
$PAGE->set_heading($course->fullname);
// Output starts here.
echo $OUTPUT->header();

//echo 'This is an admin only function to transfer Journal entries to Diary entries. This is a test capability only, at the moment.<br><br>';
//echo 'The course ID for this course is: $cm->course = '.$cm->course.'<br>';

//echo '=================================================================<br>';

//echo 'Second thing to do is list the Journals in this course. Will need SQL to retrieve that.<br>';
// echo 'Probably need to develop the SQL in Ad-hoc reports.<br><br>.';
/*
$sql = 'SELECT *
            FROM {journal} j
           WHERE j.course = '.$cm->course.'
        ORDER BY j.timemodified ASC';
// echo 'The SQL for this is: <br>'.$sql.'<br><br>';


$journals = $DB->get_records_sql($sql);

echo 'This is a list of each Journal activity in this course, '.$cm->course.'.<br>';
echo '<b>    id course Journal name</b><br>';
echo '<b>---------------------------</b><br>';
foreach ($journals as $journal) {
    echo '    '.$journal->id.'  '.$journal->course.'  '.$journal->name.'<br>';
}
echo 'This is where I need to add a selector so I can pick which Journal to copy from.<br>';
echo '=================================================================<br><br>';
*/
// echo 'Third, I need to list each Diary in this course.<br>Will need another SQL to get this data.<br><br>';

/*
$sql = 'SELECT *
            FROM {diary} d
           WHERE d.course = '.$cm->course.'
        ORDER BY d.timemodified ASC';
// echo 'The SQL for this is: <br>'.$sql.'<br><br>';

$diarys = $DB->get_records_sql($sql);



echo 'This is a list of each Diary activity in this course, '.$cm->course.'.<br>';
echo '<b>    id-course-Diary name</b><br>';
echo '<b>---------------------------</b><br>';

foreach ($diarys as $diary) {
    echo '    '.$diary->id.'  '.$diary->course.'  '.$diary->name.'<br>';
}

echo '=================================================================<br><br>';
*/
/*
echo 'I now have the Journal and Diary activity data and will need to decide which Journal to copy from and which Diary to copy too.<br>';
//$journalid = 23;
$journalid = 31;
// Need an input here for the journalid to use.

echo 'Need a foreach loop here to cycle through all the journals.<br>';
echo 'It will then need an if to check to see if a Diary with the same name already exists.<br>';
echo 'If the Diary already exists, then it will need to check and see if the entries have already been copied from the journal to the diary.<br>';
echo 'The WHERE clause will need to be changed to je.journal > 0 instead of $journalid.<br>';

$sql = 'SELECT *
            FROM {journal_entries} je
           WHERE je.journal = '.$journalid.'
        ORDER BY je.id ASC';
echo 'The SQL for this is: <br>'.$sql.'<br><br>';
//$diaryid = 22;
$diaryid = 164;
// Need an input here for the diaryid to use.
echo '=================================================================<br><br>';
echo '=================================================================<br><br>';
*/
/*
$journalentries = $DB->get_records_sql($sql);
foreach ($journalentries as $journalentry) {
    echo $journalentry->id.'  '.$journalentry->journal.'  '.$journalentry->userid.'  '.$journalentry->modified.' '.$journalentry->text.'<br>';

    $newdiaryentry = new stdClass();
    $newdiaryentry->diary = $diaryid;
    $newdiaryentry->userid = $journalentry->userid;
    $newdiaryentry->timecreated = $journalentry->modified;
    $newdiaryentry->timemodified = $journalentry->modified;
    $newdiaryentry->text = $journalentry->text;
    $newdiaryentry->format = $journalentry->format;
    $newdiaryentry->rating = $journalentry->rating;
    $newdiaryentry->entrycomment = $journalentry->entrycomment;
    $newdiaryentry->teacher = $journalentry->teacher;
    $newdiaryentry->timemarked = $journalentry->timemarked;
    $newdiaryentry->mailed = $journalentry->mailed;
//print_object($newdiaryentry);
    // Check to see if the record already exists.
    //if (! $DB->record_exists('diary_entries', array ($newdiaryentry=null))) {
    //    $DB->insert_record('diary_entries', $newdiaryentry, false);
    //} else {
    //    echo 'Found existing record!';
    //}
}



echo 'Finally, I want to move a copy of the Journal entries into Diary entries for the selected Diary activity.<br><br>';
*/

////////////////////////////////////////////////////////////////////////////////////////

// 20211108 Get the a background default Diary text background color for our table background here.
$color3 = $diary->entrytextbgc;

// Add colored background with border.
echo '<div class="w-50 p-3" style="font-size:1em;
    font-weight:bold;background: '.$color3.';
    border:2px solid black;
    -webkit-border-radius:16px;
    -moz-border-radius:16px;border-radius:16px;">'.'<br>';

// Start page form and add lesson name selector.
echo '<form method="POST">';

// 20211105 Setup a url that takes you back to the Diary you came from.
$url1 = $CFG->wwwroot . '/mod/diary/view.php?id='.$id;
$url2 = $CFG->wwwroot . '/mod/diary/journaltodiaryxfr.php?id='.$cm->id;

echo 'This is an admin only function to transfer Journal entries to Diary entries. This is a test and development capability only, at the moment.<br><br>';

echo 'The name of this course is: '.$course->fullname.', with an ID of: '.$cm->course.'<br>';

$jsql = 'SELECT *
            FROM {journal} j
           WHERE j.course = '.$cm->course.'
        ORDER BY j.timemodified ASC';

$journals = $DB->get_records_sql($jsql);

echo 'This is a list of each Journal activity in the course.<br>';
echo '<b>    ID</b> | Course | Journal name<br>';
//echo '<b>---------------------------</b><br>';
foreach ($journals as $journal) {
    echo '    '.$journal->id.'  '.$journal->course.'  '.$journal->name.'<br>';
}

$dsql = 'SELECT *
            FROM {diary} d
           WHERE d.course = '.$cm->course.'
        ORDER BY d.timemodified ASC';

$diarys = $DB->get_records_sql($dsql);

echo '<br>This is a list of each Diary activity in the course.<br>';
echo '<b>    ID</b> | Course | Diary name<br>';
//echo '<b>---------------------------</b><br>';

foreach ($diarys as $diary) {
    echo '    '.$diary->id.'  '.$diary->course.'  '.$diary->name.'<br>';
}
    // Set up place to enter Journal ID to transfer entries from.
    echo '<br><br>'.get_string('journalid', 'diary').': <input type="text" name="journalid" id="journalid">
        <span style="color:red;" id="namemsg"></span>';

    // Set up place to enter Diary ID to transfer entries to.
    echo '<br><br>'.get_string('diaryid', 'diary').': <input type="text" name="diaryid" id="diaryid">
        <span style="color:red;" id="namemsg"></span>';

// Add a confirm and cancel button.
echo '<br><br><input class="btn btn-primary" style="border-radius: 8px"
     name="button" onClick="return clClick()" type="submit" value="'
    .get_string('transfer', 'diary').'"> <a href="'
    .$url2.'" class="btn btn-secondary"  style="border-radius: 8px">'
    .get_string('cancel', 'diary').'</a>'.'</form>';



echo '<br><a href="'.$url1
    .'" class="btn btn-primary" style="border-radius: 8px">'
    .get_string('returnto', 'diary', $diary->name)
    .'</a><br><br>';

echo '</div>';
echo $OUTPUT->footer();
