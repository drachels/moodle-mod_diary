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
 * Copy prompts from another Diary activity, or import prompts from a CSV file.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_diary\local\prompts;

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/diary/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

$id = required_param('id', PARAM_INT); // Course module ID of the destination Diary.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}
if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);
require_login($course, false, $cm);
require_capability('mod/diary:manageentries', $context);

$returnurl = new moodle_url('/mod/diary/prompt_edit.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/diary/prompt_transfer.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('prompttransfertitle', 'diary'));

// Build the list of other Diary activities in this course that actually have prompts.
$sourceoptions = [];
$othermodules = get_coursemodules_in_course('diary', $course->id);
foreach ($othermodules as $othercm) {
    if ((int)$othercm->instance === (int)$diary->id) {
        continue;
    }
    if (!has_capability('mod/diary:manageentries', context_module::instance($othercm->id))) {
        continue;
    }
    $promptcount = $DB->count_records('diary_prompts', ['diaryid' => $othercm->instance]);
    if ($promptcount < 1) {
        continue;
    }
    $sourceoptions[$othercm->instance] = format_string($othercm->name) . ' (' . $promptcount . ')';
}

// A submit posts the source as the form's hidden sourcediaryid, while the
// selector above the form uses the source GET param. The hidden value must win,
// otherwise a POST falls back to the first activity and the posted prompt ids no
// longer match the rebuilt option list, so they are silently discarded.
$sourcediaryid = optional_param('sourcediaryid', 0, PARAM_INT);
if (!isset($sourceoptions[$sourcediaryid])) {
    $sourcediaryid = optional_param('source', 0, PARAM_INT);
}
if (!isset($sourceoptions[$sourcediaryid])) {
    $sourcediaryid = (int)array_key_first($sourceoptions);
}

$buildpromptoptions = function ($diaryid) use ($DB) {
    $options = [];
    if (!$diaryid) {
        return $options;
    }
    $prompts = $DB->get_records('diary_prompts', ['diaryid' => $diaryid], 'datestart, datestop, id');
    foreach ($prompts as $prompt) {
        $label = trim(format_string($prompt->title ?? ''));
        if ($label === '') {
            $label = shorten_text(html_to_text($prompt->text), 100);
        }
        $options[$prompt->id] = userdate($prompt->datestart, get_string('strftimedatetime', 'langconfig'))
            . ': ' . $label;
    }
    return $options;
};

$promptoptions = $buildpromptoptions($sourcediaryid);

// Unlike copying, exporting this activity's own prompts is the common case, so
// the current Diary stays in the list.
$exportoptions = [];
foreach ($othermodules as $othercm) {
    if (!has_capability('mod/diary:manageentries', context_module::instance($othercm->id))) {
        continue;
    }
    $promptcount = $DB->count_records('diary_prompts', ['diaryid' => $othercm->instance]);
    if ($promptcount < 1) {
        continue;
    }
    $exportoptions[$othercm->instance] = format_string($othercm->name) . ' (' . $promptcount . ')';
}

$exportdiaryid = optional_param('exportdiaryid', 0, PARAM_INT);
if (!isset($exportoptions[$exportdiaryid])) {
    $exportdiaryid = optional_param('exportsource', 0, PARAM_INT);
}
if (!isset($exportoptions[$exportdiaryid])) {
    $exportdiaryid = isset($exportoptions[$diary->id]) ? (int)$diary->id : (int)array_key_first($exportoptions);
}

$exportpromptoptions = $buildpromptoptions($exportdiaryid);

$copyform = new \mod_diary\form\prompt_copy_form($pageurl->out(false), [
    'sourcediaryid' => $sourcediaryid,
    'promptoptions' => $promptoptions,
]);
$exportform = new \mod_diary\form\prompt_export_form($pageurl->out(false), [
    'exportdiaryid' => $exportdiaryid,
    'promptoptions' => $exportpromptoptions,
]);
$importform = new \mod_diary\form\prompt_import_form($pageurl->out(false));

if ($copyform->is_cancelled() || $exportform->is_cancelled() || $importform->is_cancelled()) {
    redirect($returnurl);
}

if ($copydata = $copyform->get_data()) {
    $sourcediaryid = (int)$copydata->sourcediaryid;
    if (!isset($sourceoptions[$sourcediaryid])) {
        redirect(
            $pageurl,
            get_string('promptcopyinvalidsource', 'diary'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $copied = prompts::copy_prompts_to_diary(
        $sourcediaryid,
        (int)$diary->id,
        $copydata->promptids,
        !empty($copydata->includerules)
    );

    redirect($returnurl, get_string('promptcopysuccess', 'diary', $copied));
}

if ($exportdata = $exportform->get_data()) {
    $exportdiaryid = (int)$exportdata->exportdiaryid;
    if (!isset($exportoptions[$exportdiaryid])) {
        redirect(
            $pageurl,
            get_string('promptexportinvalidsource', 'diary'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $exportrows = prompts::get_prompt_export_rows(
        $exportdiaryid,
        (array)$exportdata->promptids,
        $exportdata->dateformat
    );
    if (count($exportrows) < 2) {
        redirect(
            $pageurl,
            get_string('promptexportnoprompts', 'diary'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $exportname = $DB->get_field('diary', 'name', ['id' => $exportdiaryid]);
    $csvexport = new csv_export_writer();
    $csvexport->set_filename(clean_filename('diary_prompts_' . $exportname . '_' . userdate(time(), '%Y%m%d')));
    foreach ($exportrows as $exportrow) {
        $csvexport->add_data($exportrow);
    }
    $csvexport->download_file();
    die();
}

if ($importdata = $importform->get_data()) {
    $csvcontent = $importform->get_file_content('csvfile');
    if ($csvcontent === false) {
        redirect(
            $pageurl,
            get_string('promptimportnofile', 'diary'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    [$imported, $errors] = prompts::import_prompts_from_csv((int)$diary->id, $csvcontent);

    if (!empty($errors)) {
        \core\notification::warning(implode('<br />', array_map('s', $errors)));
    }
    redirect($returnurl, get_string('promptimportsuccess', 'diary', $imported));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('prompttransfertitle', 'diary'));

echo $OUTPUT->heading(get_string('promptcopyheading', 'diary'), 3);
if (empty($sourceoptions)) {
    echo $OUTPUT->notification(get_string('promptcopynosources', 'diary'), 'info');
} else {
    // Each selector carries the other section's choice so switching one does not reset the other.
    echo $OUTPUT->single_select(
        new moodle_url($pageurl, ['exportsource' => $exportdiaryid]),
        'source',
        $sourceoptions,
        $sourcediaryid,
        null
    );
    $copyform->display();
}

echo $OUTPUT->heading(get_string('promptexportheading', 'diary'), 3);
if (empty($exportoptions)) {
    echo $OUTPUT->notification(get_string('promptexportnosources', 'diary'), 'info');
} else {
    echo $OUTPUT->single_select(
        new moodle_url($pageurl, ['source' => $sourcediaryid]),
        'exportsource',
        $exportoptions,
        $exportdiaryid,
        null
    );
    echo html_writer::tag('p', get_string('promptexportcolumns', 'diary'));
    $exportform->display();
}

echo $OUTPUT->heading(get_string('promptimportheading', 'diary'), 3);
echo html_writer::tag('p', get_string('promptimportcolumns', 'diary', implode(', ', prompts::get_csv_import_columns())));
$importform->display();

echo html_writer::div(
    html_writer::link($returnurl, get_string('back')),
    'mt-3'
);

echo $OUTPUT->footer();
