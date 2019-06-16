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
 * This page opens the current instance of diary for editing, in a particular course.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once('./edit_form.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    print_error("Course Module ID was incorrect");
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error("Course is misconfigured");
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

if (! $diary = $DB->get_record("diary", array("id" => $cm->instance))) {
    print_error("Course module is incorrect");
}

// Header.
$PAGE->set_url('/mod/diary/edit.php', array('id' => $id));
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();

// My mod. Get all records for current user, instead of just one.
$entrys = $DB->get_records("diary_entries", array("userid" => $USER->id, "diary" => $diary->id));

if ($entrys) {
// Get the latest user entry.
    foreach ($entrys as $entry) {
        $data->entryid = $entry->id;
        $data->text = $entry->text;
        $data->textformat = $entry->format;
        $data->timecreated = $entry->timecreated;
    }

// If new calendar day, start a new entry.
if (strtotime('today midnight') > $entry->timecreated) {
        $entrys = '';
        $data->entryid = null;
        $data->text = '';
        $data->textformat = FORMAT_HTML;
        $data->timecreated = time();
    }
// If there are no entries for this user, start the first one.
} else {
    $data->entryid = null;
    $data->text = '';
    $data->textformat = FORMAT_HTML;
    $data->timecreated = time();
}

$data->id = $cm->id;

$editoroptions = array(
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'context' => $context,
    'subdirs' => false,
    'enable_filemanagement' => true
);

$data = file_prepare_standard_editor($data, 'text', $editoroptions, $context, 'mod_diary', 'entry', $data->entryid);

$form = new mod_diary_entry_form(null, array('entryid' => $data->entryid, 'editoroptions' => $editoroptions));

$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/diary/view.php?id=' . $cm->id);
} else if ($fromform = $form->get_data()) {
    // If data submitted, then process and store, contains text, format, and itemid.

    // Prevent CSFR.
    confirm_sesskey();
    $timenow = time();

    // This will be overwriten after we have the entryid.
    $newentry = new stdClass();
    $newentry->text = $fromform->text_editor['text'];
    $newentry->format = $fromform->text_editor['format'];
    $newentry->timecreated = $data->timecreated;
    $newentry->timemodified = $timenow;

    if ($entrys) {
        $newentry->id = $entry->id;
        if (!$DB->update_record("diary_entries", $newentry)) {
            print_error("Could not update your diary");
        }
    } else {
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

    $DB->update_record('diary_entries', $newentry);

    if ($entrys) {
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

    redirect(new moodle_url('/mod/diary/view.php?id='.$cm->id));
    die;
}


echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($diary->name));

$intro = format_module_intro('diary', $diary, $cm->id);
echo $OUTPUT->box($intro);

// Otherwise fill and print the form.
$form->display();

echo $OUTPUT->footer();
