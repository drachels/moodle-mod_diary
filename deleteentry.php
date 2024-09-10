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
$debug['CP1 In the deleteentry.php file printing $id:'] = $id;
$debug['CP1 printing $action:'] = $action;
$debug['CP1 printing $firstkey:'] = $firstkey;
$debug['CP1 printing $promptid:'] = $promptid;

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

$debug['To checkpoint 2 is okay.'] = 'Nothing to report';
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

$debug['To checkpoint 3 is okay.'] = 'Just got diarystats::get_minmaxes';
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

$debug['To checkpoint 4 is okay.'] = 'Just set four $PAGE items, initialized $data, and set parameters for userid, diary, action, and firstkey.';
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
$debug['CP8-137 just got $entry from the mdl_diary_entries table: '] = $entry;

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
    $data->tags = core_tag_tag::get_item_tags_array('mod_diary', 'diary_entries', $firstkey);

    $debug['CP11-187 jest detected request to delete an entry ($action == deleteentry && $entry): '] = $data;



 

} else {
    print_object($debug);
    die;
    throw new moodle_exception(get_string('generalerror', 'diary'));
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////
// I think everything needed for a delete entry has be retrieved at this point.
// Might want to see about printing out the $data via use of a mustache template.
/////////////////////////////////////////////////////////////////////////////////////////////////////////

$debug['In deleteentry.php at line 214.'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
//print_object($editoroptions);
//print_object($attachmentoptions);

print_object($debug);
//die;

/////////////////////////////////////////////////////////////////////////////////////////////////////////


echo $OUTPUT->header();
if (($diary->intro) && ($CFG->branch < 400)) {
    echo $OUTPUT->heading(format_string($diary->name));
    $intro = $tempintro.'<br>'.format_module_intro('diary', $diary, $cm->id);
} else {
    $intro = format_module_intro('diary', $diary, $cm->id);
}
echo $OUTPUT->box($intro);
//print_object($data);
echo $OUTPUT->box('<b>Entry ID to delete: </b>'.$data->entryid);
// The current should NOT be able to delete a prompt. Only a teacher should be able to do that from the prompt_edit.php file.
//echo $OUTPUT->box('<b>Entry Prompt IDs to delete: </b>'.$data->promptid);
// The time created needs to be formatted for easy reading.
//echo $OUTPUT->box('<b>Entry Time Created to delete: </b>'.$data->timecreated);
echo '<b>Date this entry was created: </b>'.(date("Y-m-d", $data->timecreated));

//echo (date("Y-m-d", $data->timecreated));

echo $OUTPUT->box('<b>Entry Text to delete: </b>'.$data->text);
// I do not think that the student even needs to know about the textformat setting, or even know that he is deleting the one for the ccurrent entry.
//echo $OUTPUT->box('<b>Entry Text Format to delete: </b>'.$data->textformat);

// IMPORTANT! Will also need code to show the tags and delete them too!
// Trying to output the tags like this, creates an error, Array to string conversion, due to having multiple tags for the entry.
//echo $OUTPUT->box($data->tags);

// Otherwise fill and print the form.
echo '<b>This is the place to add the actual Delete and Cancel buttons.</b>';

echo $OUTPUT->footer();
