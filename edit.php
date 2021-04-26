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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once("../../config.php");
require_once('lib.php'); // May not need this.
require_once('./edit_form.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'currententry', PARAM_ACTION); // Action(default to current entry).
$firstkey = optional_param('firstkey', '', PARAM_INT); // Which entry to edit.

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", array(
    "id" => $cm->course
))) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

if (! $diary = $DB->get_record("diary", array(
    "id" => $cm->instance
))) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// Header.
$PAGE->set_url('/mod/diary/edit.php', array(
    'id' => $id
));
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();

$parameters = array(
    'userid' => $USER->id,
    'diary' => $diary->id,
    'action' => $action,
    'firstkey' => $firstkey
);

// Get the single record specified by firstkey.
$entry = $DB->get_record("diary_entries", array(
    "userid" => $USER->id,
    'id' => $firstkey
));

if ($action == 'currententry' && $entry) {
    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;

    // Check the timecreated of the current entry to see if now is a new calendar day .
    // 20210425 If can edit dates, just start a new entry.
    if ((strtotime('today midnight') > $entry->timecreated) || ($action == 'currententry' && $diary->editdates)) {
        $entry = '';
        $data->entryid = null;
        $data->timecreated = time();
        $data->text = '';
        $data->textformat = FORMAT_HTML;
    }
} else if ($action == 'editentry' && $entry) {
    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;
    // Think I might need to add a check for currententry && !entry to justify starting a new entry, else error.
} else if ($action == 'currententry' && ! $entry) {
    // There are no entries for this user, so start the first one.
    $data->entryid = null;
    $data->timecreated = time();
    $data->text = '';
    $data->textformat = FORMAT_HTML;
} else {
    throw new moodle_exception(get_string('generalerror', 'diary'));
}

$data->id = $cm->id;

list ($editoroptions, $attachmentoptions) = results::diary_get_editor_and_attachment_options($course,
                                                                                             $context,
                                                                                             $entry,
                                                                                             $action,
                                                                                             $firstkey);

$data = file_prepare_standard_editor($data,
                                     'text',
                                     $editoroptions,
                                     $context,
                                     'mod_diary',
                                     'entry',
                                     $data->entryid);
$data = file_prepare_standard_filemanager($data,
                                          'attachment',
                                          $attachmentoptions,
                                          $context,
                                          'mod_diary',
                                          'attachment',
                                          $data->entryid);

// 20201119 Added $diary->editdates setting.
$form = new mod_diary_entry_form(null, array(
    'current' => $data,
    'cm' => $cm,
    'diary' => $diary->editdates,
    'editoroptions' => $editoroptions,
    'attachmentoptions' => $attachmentoptions
));

// Set existing data loaded from the database for this entry.
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/diary/view.php?id=' . $cm->id);
} else if ($fromform = $form->get_data()) {
    // If data submitted, then process and store, contains text, format, and itemid.
    // Prevent CSFR.
    confirm_sesskey();
    $timenow = time();

    // This will be overwritten after we have the entryid.
    $newentry = new stdClass();
    $newentry->timecreated = $fromform->timecreated;
    $newentry->timemodified = $timenow;
    $newentry->text = $fromform->text_editor['text'];
    $newentry->format = $fromform->text_editor['format'];

    if ($fromform->entryid) {
        $newentry->id = $fromform->entryid;
        if (! $DB->update_record("diary_entries", $newentry)) {
            throw new moodle_exception(get_string('generalerrorupdate', 'diary'));
        }
    } else {
        $newentry->userid = $USER->id;
        $newentry->diary = $diary->id;
        if (! $newentry->id = $DB->insert_record("diary_entries", $newentry)) {
            throw new moodle_exception(get_string('generalerrorinsert', 'diary'));
        }
    }

    // Relink using the proper entryid.
    // We need to do this as draft area didn't have an itemid associated when creating the entry.
    $fromform = file_postupdate_standard_editor($fromform,
                                                'text',
                                                $editoroptions,
                                                $editoroptions['context'],
                                                'mod_diary',
                                                'entry',
                                                $newentry->id);
    $newentry->text = $fromform->text;
    $newentry->format = $fromform->textformat;
    $newentry->timecreated = $fromform->timecreated;

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

    redirect(new moodle_url('/mod/diary/view.php?id=' . $cm->id));
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($diary->name));

$intro = format_module_intro('diary', $diary, $cm->id);
echo $OUTPUT->box($intro);

// Otherwise fill and print the form.
$form->display();

echo $OUTPUT->footer();
