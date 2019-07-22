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
 * This page opens the current instance of a diary entry for editing,
 * in a particular diary in a particular course.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once('lib.php'); // May not need this.
require_once('./edit_form.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.
$action  = optional_param('action', 'currententry', PARAM_ACTION);  // Action(default to current entry).
$firstkey  = optional_param('firstkey', '', PARAM_INT);  // Which entry to edit.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

if (! $diary = $DB->get_record("diary", array("id" => $cm->instance))) {
    print_error('invalidcourse');
}

/**
print_object('xxx spacer edit 1');
print_object('xxx spacer edit 2');
print_object('xxx spacer edit 3');
print_object('This is $id, $cm, $course, $action, and $firstkey as soon as we are on the edit.php page.');
print_object($id);
print_object($cm);
print_object($course);
print_object($action);
print_object($firstkey);
**/

// Header.
$PAGE->set_url('/mod/diary/edit.php', array('id' => $id));
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();



/**
$parameters = array(
    'userid' => $USER->id,
    'diary' => $diary->id,
    'id' => $firstkey
);
**/
$parameters = array(
    'userid' => $USER->id,
    'id' => $firstkey
);

// Get the single record specified by firstkey.
// $entry = $DB->get_record("diary_entries", array("userid" => $USER->id, 'id' => $firstkey));
$entry = $DB->get_record("diary_entries", $parameters);

print_object('And this is the $entry we are going to edit.');
print_object($entry);

if ($action == 'currententry' && $entry) {
print_object('Action was currententry and there is an entry so we passed the first if action test.');
    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;

    // Check the timecreated of the current entry to see if now is a new calendar day .
    if (strtotime('today midnight') > $entry->timecreated) {
print_object('Action was currententry and there is an entry so we passed the first if action test and it is past midnight on a new calenday so starting a new entry.');

        $entry = '';
        $data->entryid = null;
        $data->timecreated = time();
        $data->text = '';
        $data->textformat = FORMAT_HTML;
    }
} else if ($action == 'editentry' && $entry) {
print_object('Action was editentry and there is an entry so we passed into the else if action test.');

    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;
// Think I might need to add a check for currententry && !entry to justify starting a new entry, else error.
} else if ($action == 'currententry' && ! $entry) {
print_object('Action was currententry BUT there was NO entry so we passed into the second else if action test and starting a new blank entry.');

    // There are no entries for this user, so start the first one.
    $data->entryid = null;
    $data->timecreated = time();
    $data->text = '';
    $data->textformat = FORMAT_HTML;
} else {
  //      print_error('There has been an error.');
}

$data->id = $cm->id;

// Need options for action and entryid, so they can be used after, Save changes, is clicked.
$editoroptions = array(
    'action'   => $action,
    'firstkey' => $firstkey,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'context' => $context,
    'subdirs' => false,
    'enable_filemanagement' => true,
    'enable_aliases' => true
);

print_object('Just created editoroptions and this is it:');
print_object($editoroptions);


// This is original journal version and I think it is BROKEN! I don't think the $data->entryid works.
// It appears to work in journal because there is only ONE entry in the journal_entries table for each user
// so when it goes to save changes, there is only ONE place to put them. In diary, there can be multiple
// entries for each user. When the page reloads after the, Save changes, button is clicked, the 
// $data->entryid is empty which tells edit.php to start a new DB entry.

$data = file_prepare_standard_editor($data, 'text', $editoroptions, $context, 'mod_diary', 'entry', $data->entryid);
//$data = file_prepare_standard_editor($data, 'text', $editoroptions, $context, 'mod_diary', 'entry', $data->id);

print_object('Getting set to add $data to the form and this is current $data:');
print_object($data);

$mform = new mod_diary_entry_form(null, array('current'=>$data, 'entryid' => $data->id, 'editoroptions' => $editoroptions));
//$mform = new mod_diary_entry_form(null, array('entryid' => $data->id, 'editoroptions' => $editoroptions));


// Set existing data loaded from the database for this entry.
$mform->set_data($data);
print_object('This is just after $mform->set_data($data) and we are printing $data.');
print_object($data);

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/diary/view.php?id=' . $cm->id);
} else if ($fromform = $mform->get_data()) {

print_object('This should be after return from edit_form, and printing $fromform.');
print_object($fromform);

    // If data submitted, then process and store, contains text, format, and itemid.

    // Prevent CSFR.
    confirm_sesskey();
    $timenow = time();

    // This will be overwriten after we have the entryid.
    $newentry = new stdClass();
    $newentry->id = $data->id;
    $newentry->timecreated = $data->timecreated;
    $newentry->timemodified = $timenow;
    $newentry->text = $fromform->text_editor['text'];
    $newentry->format = $fromform->text_editor['format'];

print_object('This is first key just before we test it for the if on the edit.php page.');
print_object($firstkey);
print_object('And this is $diary->id.');
print_object($diary->id);
print_object('And this is $data after edit and just before the if.');
print_object($data);
print_object('And this is $newentry after edit and just before the if.');
print_object($newentry);


    //if ($entry) { // I think the problem is here as next line is not being printed when saving.
    //if ($newentry->id = $firstkey;) { // I think the problem is here as next line is not being printed when saving.
    if ($data) { // I think the problem is here as next line is not being printed when saving.
print_object('In the first part of the if.');
        $newentry->id = $fromform->entryid;
        //$newentry->id = $firstkey;
        if (!$DB->update_record("diary_entries", $newentry)) {
            print_error("Could not update your diary");
        }
    } else {
print_object('In the else part of the if.');

        $newentry->userid = $USER->id;
        $newentry->diary = $diary->id;
        if (!$newentry->id = $DB->insert_record("diary_entries", $newentry)) {
            print_error("Could not insert a new diary entry");
        }
    }

    // Relink using the proper entryid.
    // We need to do this as draft area didn't have an itemid associated when creating the entry.
    $fromform = file_postupdate_standard_editor($fromform, 'text', $editoroptions,
        $editoroptions['context'], 'mod_diary', 'entry', $newentry->id);
    $newentry->text = $fromform->text;
    $newentry->format = $fromform->textformat;
    $newentry->timecreated = $data->timecreated;
    //$newentry->timemodified = $timenow;

    $DB->update_record('diary_entries', $newentry);

    if ($entry) {
        // Trigger module entry updated event.
        $event = \mod_diary\event\entry_updated::create(array(
            'objectid' => $diary->id,
            'context' => $context
        ));
    } else {
        // Trigger module entry created event.
        $event = \mod_diary\event\entry_created::create(array(
            'objectid' => $diary->id,
            'context' => $context
        ));

    }
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();

    redirect(new moodle_url("/mod/diary/view.php?id=$cm->id&action=$action&firstkey=$fromform->entryid"));
    die;
}


echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($diary->name));

$intro = format_module_intro('diary', $diary, $cm->id);
echo $OUTPUT->box($intro);

// Otherwise fill and print the form.
$mform->display();

echo $OUTPUT->footer();
