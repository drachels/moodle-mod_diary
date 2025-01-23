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
use mod_diary\event\entry_deleted;
use mod_diary\event\entry_tags_deleted;
use mod_diary\event\invalid_access_attempt;

require_once("../../config.php");
require_once('locallib.php'); // May not need this.
require_once('./edit_form.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT); // Action(default to current entry).
$firstkey = optional_param('firstkey', '', PARAM_INT); // Which diary_entries id to edit.
$promptid = optional_param('promptid', '', PARAM_INT); // Current entries promptid.

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

// 20241204 Added for capability check later.
$entriesmanager = has_capability('mod/diary:manageentries', $context);
$canadd = has_capability('mod/diary:addentries', $context);

if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

if (!$entry = $DB->get_record('diary_entries', ['id' => $firstkey])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}
// Header.
$PAGE->set_url('/mod/diary/view.php', ['id' => $id]);
$PAGE->navbar->add(get_string("viewentries", "diary"));
$PAGE->set_heading($course->fullname);

$taginststodel = $DB->get_records('tag_instance', ['itemid' => $firstkey]);

$count = 0;
foreach ($taginststodel as $inst) {
    $count++;
    $tagstodel = $DB->get_records('tag', ['id' => $inst->tagid, 'userid' => $entry->userid]);
    $debug[$count.' In deleteentry for each loop printing $tagstodel'] = $tagstodel;
}

$deleteurl = 'view.php?id='.$id;

// Commented out to keep from actually deleting the entry, at the moment during development.
// 20250123 This deletes the user entry.
$DB->delete_records('diary_entries', ['id' => $entry->id]);

if ($taginststodel) {
    // 20250123 This deletes one or more tags associated with this entry.
    $DB->delete_records('tag_instance', ['itemid' => $firstkey]);
    // 20250123 Added to trigger module entry_tags_deleted event.
    $params = [
        'objectid' => $course->id,
        'context' => $context,
        'other' => [
            'entry' => $entry->id,
            'tags' => $taginststodel,
        ],
    ];

    $event = entry_tags_deleted::create($params);
    $event->trigger();
} else {
    // 20250123 Added to trigger module entry_deleted event.
    $params = [
        'objectid' => $course->id,
        'context' => $context,
        'other' => $taginststodel,
    ];
    $event = entry_deleted::create($params);
    $event->trigger();
}
// 20250122 This returns toview.php after click on the javascript OK or Cancel button.
header("Location: $deleteurl");
exit();
