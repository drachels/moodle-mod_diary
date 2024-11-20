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
 * This page opens the current instance of a diary entry for editing.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 *            Thanks to Stephen Wallace regarding instant notifications to teachers.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;
use mod_diary\local\diarystats;
use mod_diary\local\prompts;
use mod_diary\event\invalid_access_attempt;

require_once("../../config.php");
require_once('locallib.php'); // May not need this.
require_once('./edit_form.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT); // Action(default to current entry).
$firstkey = optional_param('firstkey', '', PARAM_INT); // Which diary_entries id to edit.
$promptid = optional_param('promptid', '', PARAM_INT); // Current entries promptid.

//print_object('Spacer in edit.php at line 39.');
//print_object('Spacer in edit.php at line 40.');
//print_object('Spacer in edit.php at line 41.');
//print_object('Spacer in edit.php at line 42.');
//print_object('Spacer in edit.php at line 43.');
//print_object('Checkpoint 1 is okay.');
//die; //20240819 To here works.
$debug = [];
//$debug['CP1 In the deleteentry.php file printing $id:'] = $id;
//$debug['CP1 printing $action:'] = $action;
//$debug['CP1 printing $firstkey:'] = $firstkey;
//$debug['CP1 printing $promptid:'] = $promptid;

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

//$debug['To checkpoint 2 is okay.'] = 'Nothing to report';
//die; //20240819 To here works.

if (! $diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// 20221107 The $diary->intro gets overwritten by the current prompt and Notes, so keep a copy for later down in this file.
$tempintro = $diary->intro;

// 20240413 Check for existing promptid. DO NOT get current if editing entry already has one.
// Need to add - DO NOT get current if editing entry does not have a prompt id and it does not meet the time criteria.
if ((!$promptid) && ($diary->timeopen < time())) {
    // 20240507 Added for testing and it appears to work for existing entry without a prompt.
    if ($promptid > 0) {
        // Need to call a prompt function that returns the current promptid, if there is one that is current.
        $promptid = prompts::get_current_promptid($diary);
    }
}

// 20210817 Add min/max info to the description so user can see them while editing an entry.
// 20240414 This also adds the prompt text to the $diary->intro.
diarystats::get_minmaxes($diary, $action, $promptid);

//$debug['To checkpoint 3 is okay.'] = 'Just got diarystats::get_minmaxes';
//die; //20240819 To here works.

// 20210613 Added check to prevent direct access to create new entry when activity is closed.
if (($diary->timeclose) && (time() > $diary->timeclose)) {
    // Trigger invalid_access_attempt with redirect to the view page.
    $params = [
        'objectid' => $id,
        'context' => $context,
        'other' => [
            'file' => 'entrydelete.php',
        ],
    ];
    $event = invalid_access_attempt::create($params);
    $event->trigger();
    redirect('view.php?id='.$id, get_string('invalidaccessexp', 'diary'));
}

// Header.
$PAGE->set_url('/mod/diary/entrydelete.php', ['id' => $id]);
$PAGE->navbar->add(get_string('deleteentry', 'diary'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();

$parameters = [
    'userid' => $USER->id,
    'diary' => $diary->id,
    'action' => $action,
    'firstkey' => $firstkey,
];

//$debug['To checkpoint 4 is okay.'] = 'Just set four $PAGE items, initialized $data, and set parameters for userid, diary, action, and firstkey.';
//print_object($debug);
//die; //20240819 To here works.

// Get the single record specified by firstkey.
$entry = $DB->get_record('diary_entries',
    [
        'userid' => $USER->id,
        'id' => $firstkey,
    ]
);

// This shows up upon save.
//$debug['CP8-137 just got $entry from the mdl_diary_entries table: '] = $entry;

// 20230306 Added code that lists the tags on the edit_form page.
//$data->tags = core_tag_tag::get_item_tags_array('mod_diary', 'diary_entries', $firstkey);

if ($action == 'deleteentry' && $entry) {
    $data->entryid = $entry->id;
    // 20240426 Trying to add the promptid here.
    // 20240426 This lcation works for old promptid's but not for entries with NO promptid assigned. i.e. Does not work for 0.
    $data->promptid = $entry->promptid;
    $data->timecreated = $entry->timecreated;
    $data->title = $entry->title;
    $data->text = $entry->text;
    $data->textformat = $entry->format;
    $data->entrybgc = $diary->entrybgc;
    $data->entrytextbgc = $diary->entrytextbgc;
    $data->tags = core_tag_tag::get_item_tags_array('mod_diary', 'diary_entries', $firstkey);

    $debug['CP11-153 delreq detected ($action == deleteentry && $entry): '] = $data;

} else {
    print_object($debug);
    die;
    throw new moodle_exception(get_string('generalerror', 'diary'));
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////
// I think everything needed for a delete entry has be retrieved at this point.
// Might want to see about printing out the $data via use of a mustache template.
/////////////////////////////////////////////////////////////////////////////////////////////////////////

//$debug['In deleteentry.php at line 166.'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
//print_object($editoroptions);
//print_object($attachmentoptions);

//print_object($debug);
//die;

///////////////////////////////////////Output the entry to delete here!///////////////////////////////////////


echo $OUTPUT->header();
if (($diary->intro) && ($CFG->branch < 400)) {
    echo $OUTPUT->heading(format_string($diary->name));
    $intro = $tempintro.'<br>'.format_module_intro('diary', $diary, $cm->id);
} else {
    $intro = format_module_intro('diary', $diary, $cm->id);
}
echo $OUTPUT->box($intro);
//print_object($data);

//echo $OUTPUT->box('<b>Entry ID to delete: </b>'.$data->entryid);
// The current user should NOT be able to delete a prompt. Only a teacher should be able to do that from the prompt_edit.php file.
//echo $OUTPUT->box('<b>Entry Prompt IDs to delete: </b>'.$data->promptid);
// The time created needs to be formatted for easy reading.
//echo $OUTPUT->box('<b>Entry Time Created to delete: </b>'.$data->timecreated);


//echo '<b>Date this entry was created: </b>'.(date("Y-m-d", $data->timecreated));






//echo (date("Y-m-d", $data->timecreated));
$color3 = $data->entrybgc;

$color4 = $data->entrytextbgc;

/*
// Create a table for the entry to delete, with area for teacher feedback.
echo '<table id="entry-'.$data->entryid.'" class="entry">';
// 20241114 Add a title for the entry, only if there is one.
if ($data->title) {
    echo '<tr>';
    echo '<td style="width:35px;"><h6>'.get_string('diarytitle', 'diary').':</h6></td>';
    echo '<td><h6>'.$data->title.'</h6></td>';
    echo '<td></td>';
    echo '</tr>';
}

// Add an entry label followed by the date of the entry.
echo '<tr>';
echo '<td style="width:35px;">'.get_string('entry', 'diary').':</td>';
echo '<td>ID '.$data->entryid.',  '.userdate($data->timecreated);
// 20230810 Changed based on pull request #29. Also had to add, use moodle_url at the head of the file.
//$url = new moodle_url('reportsingle.php', ['id' => $id, 'user' => $user->id, 'action' => 'allentries']);
//echo '  <a href="'.$url->out(false).'">'.get_string('reportsingle', 'diary')
//    .'</a>'
echo '</td><td></td>';
echo '</tr>';



echo '</table>';
*/


echo $OUTPUT->box_start();

echo '<table id="entry-'.$data->entryid.'" class="entry" style="width100%;">';

//echo '<div class="entry" style="background: '.$color4.';">';

//$data->entrytextbgc
echo $OUTPUT->box('<h2>'.$data->title.'</h2><h5>Entry: ID'.$data->entryid.', '.userdate($data->timecreated).'</h5>');
//echo $OUTPUT->box('<div class="entry" style="background: '.$color4.';">'.format_text($data->text).'</div>');
                //echo '<div class="entry" style="background: '.$color4.';">'.format_text($data->text).'</div>';



    

echo '<tr><td><div class="entry" style="width: 100%; background: '.$color3.';">';
echo '<td><div class="entry" style="text-align: left; font-size: 1em; padding: 5px; background: '.$color4.'; border-style: double; border-radius: 16px;">';
// 20241114 This adds the actual entry text division close tag for each entry listed on the page.
echo results::diary_format_entry_text($entry, $course, $cm).'</div></td></div></td></tr>';
echo '</table>';


// I do not think that the student even needs to know about the textformat setting, or even know that he is deleting the one for the ccurrent entry.
//echo $OUTPUT->box('<b>Entry Text Format to delete: </b>'.$data->textformat);

// IMPORTANT! Will also need code to show the tags and delete them too!
// Trying to output the tags like this, creates an error, Array to string conversion, due to having multiple tags for the entry.
//echo $OUTPUT->box('<b>Tags to delete: </b>');
// 20241004 Added tags to the entry, if there are any.
echo $OUTPUT->tag_list(
    core_tag_tag::get_item_tags(
        'mod_diary',
        'diary_entries',
        $entry->id
    ),
    null,
    'diary-tags'
);



echo $OUTPUT->box_end();




/////////////////////////////////////Need to print if this entry is available for deletion!///////////////////////////////////////////
// Probably need to add check for $diary->editdates
if (results::diary_available($diary)) {
    print_object('this entry can be deleted');


} else {
    // No one can reach this if the entry is not available for editing.
    print_object('this entry CANNOT be deleted');


}

// Otherwise fill and print the form.
echo '<br><b>This is the place to add the actual Delete and Cancel buttons.</b>';
//$this->add_action_buttons();

//if (lessons::is_editable_by_me($USER->id, $id, $lessonpo)) {
//if (results::is_deleteable_by_me($USER->id, $id, $lessonpo)) {

$debug['testing items before going to results.php'] = 'getting ready';
$debug['$USER->id'] = $USER->id;
$debug['$id'] = $id;
$debug['$entry'] = $entry;
$debug['$course'] = $course;
//print_object($debug);
//die;
$deleteurl = $CFG->wwwroot.'/mod/diary/view.php?id='.$id;
if (results::is_deleteable_by_me($USER->id, $id, $entry, $course)) {
    // 20200613 Added a, Delete this entry, button.
    echo ' <a onclick="return confirm(\''.get_string('deleteentryconfirm', 'diary').$entry->id.
        '\')" href="'.$deleteurl.'" class="btn btn-danger" style="border-radius: 8px">'
        .get_string('deleteentry', 'diary').' - '. $entry->id.'</a>'.'</form>';
    // 20240924 Added a cancel button with round corners.
    echo '<a href="'.$CFG->wwwroot . '/mod/diary/view.php?id='.$cm->id
        .'"class="btn btn-primary" style="border-radius: 8px">'
        .get_string('cancel')
        .'</a>';
//} else {
//    // 20200613 Added a, Delete this entry, button.
//    echo ' <a onclick="return confirm(\''.get_string('deleteentryconfirm', 'diary').$entry->id.
//        '\')" href="'.$deleteurl.'" class="btn btn-danger" style="border-radius: 8px">'
//        .get_string('deleteentry', 'diary').' - '. $entry->id.'</a>'.'</form>';
//    // 20240924 Added a cancel button with round corners.
//    echo '<a href="'.$CFG->wwwroot . '/mod/diary/view.php?id='.$cm->id
//        .'"class="btn btn-primary" style="border-radius: 8px">'
//        .get_string('cancel')
//        .'</a>';
}
echo '</form>';
echo $OUTPUT->footer();
