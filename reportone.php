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
 * This page opens the current reportone instance of diary.
 *
 * @package   mod_diary
 * @copyright 2024 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot . '/rating/lib.php');

$id = required_param('id', PARAM_INT);          // Course module.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT);
$entryid = optional_param('entryid', '', PARAM_INT); // Current entry ID.
$user = required_param('user', PARAM_INT);      // User ID.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/diary:manageentries', $context);

if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('invalidid', 'diary'));
}

// Get the name for this diary activity.
$diaryname = format_string($diary->name, true, ['context' => $context]);

// Set a default sorting order for entry retrieval (kept but may not be used here).
if (!$sortoption = get_user_preferences('sortoption')) {
    set_user_preference('sortoption', 'u.lastname ASC, u.firstname ASC');
    $sortoption = get_user_preferences('sortoption');
}

// Get the single entry.
$entry = $DB->get_record('diary_entries', ['id' => $entryid, 'userid' => $user]);

// Header with additional info in the url.
$PAGE->set_url(
    '/mod/diary/reportone.php',
    [
        'id'      => $id,
        'user'    => $user,
        'entryid' => $entryid,
        'action'  => $action,
    ]
);
$PAGE->navbar->add(get_string("rate", "diary") . ' ' . get_string("entries", "diary"));
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Process incoming data if there is any (feedback/grade save).
if ($data = data_submitted()) {
    results::diary_entries_feedback_update($cm, $context, $diary, $data, [$user => $entry], [$entryid => $entry]);

    // Trigger module feedback updated event.
    $event = \mod_diary\event\feedback_updated::create(
        [
            'objectid' => $diary->id,
            'context'  => $context,
        ]
    );
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();
} else {
    // Trigger module viewed event.
    $event = \mod_diary\event\entries_viewed::create(
        [
            'objectid' => $diary->id,
            'context'  => $context,
        ]
    );
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();
}

// Get teachers and grades menu (needed for entry display).
$teachers = get_users_by_capability($context, 'mod/diary:manageentries');
if (!$teachers) {
    throw new moodle_exception(get_string('noentriesmanagers', 'diary'));
}
$grades = make_grades_menu($diary->scale);

// Prepare and render via Mustache template.
$renderer = $PAGE->get_renderer('mod_diary');

$templatecontext = $renderer->prepare_reportone_data(
    $cm,
    $course,
    $diary,
    $DB->get_record('user', ['id' => $user]), // Full user record.
    $entry,
    $teachers,
    $grades
);

echo $renderer->render_reportone($templatecontext);

echo $OUTPUT->footer();
