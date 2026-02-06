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
 * This page deletes a diary entry (and its tags if any) when requested.
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

$id = required_param('id', PARAM_INT);          // Course Module ID.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT);
$firstkey = optional_param('firstkey', '', PARAM_INT); // Diary entry ID to delete.
$promptid = optional_param('promptid', '', PARAM_INT);

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (!$course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/diary:addentries', $context);

// Additional capability checks.
$entriesmanager = has_capability('mod/diary:manageentries', $context);
$canadd = has_capability('mod/diary:addentries', $context);

if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

if (!$entry = $DB->get_record('diary_entries', ['id' => $firstkey])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// Ensure the entry belongs to the current user (or manager can delete any).
if ($entry->userid != $USER->id && !$entriesmanager) {
    throw new moodle_exception('notyourentry', 'diary');
}

$PAGE->set_url('/mod/diary/deleteentry.php', ['id' => $id, 'firstkey' => $firstkey]);
$PAGE->set_heading($course->fullname);

// Prepare tag info for logging (clean array of tag details).
$taginststodel = $DB->get_records('tag_instance', ['itemid' => $entry->id, 'component' => 'mod_diary']);
$tagsinfo = [];

if ($taginststodel) {
    foreach ($taginststodel as $inst) {
        if ($tag = $DB->get_record('tag', ['id' => $inst->tagid], 'id, name, rawname, isstandard')) {
            $tagsinfo[] = (array) $tag;
        }
    }
}

// Delete the diary entry itself.
$DB->delete_records('diary_entries', ['id' => $entry->id]);

// Handle tags using core Tag API (preferred way).
require_once($CFG->dirroot . '/tag/lib.php');

if (!empty($tagsinfo)) {
    // Remove all tag instances for this diary entry.
    core_tag_tag::remove_all_item_tags(
        'mod_diary',       // component
        'diary_entries',   // itemtype (must match what you use when adding tags!)
        $entry->id,        // itemid
        0                  // tiuserid = 0 for standard item tags (not per-user-view)
    );

    // Optional: Clean up truly orphaned personal tags (not standard/official).
    foreach ($tagsinfo as $tagdata) {
        $tagid = $tagdata['id'];
        if (!$tagid) {
            continue;
        }

        $stillused = $DB->record_exists('tag_instance', ['tagid' => $tagid]);
        $isstandard = !empty($tagdata['isstandard']);

        if (!$stillused && !$isstandard) {
            $DB->delete_records('tag', ['id' => $tagid]);
            // Optional debug: debugging("Deleted unused Diary tag ID $tagid ({$tagdata['name']})", DEBUG_DEVELOPER);
        }
    }
}

// Prepare event parameters (used for both events).
$params = [
    'context'       => $context,
    'objectid'      => $entry->id,              // The deleted entry ID.
    'relateduserid' => $entry->userid,          // The student who created the entry.
    'other'         => [
        'entryid'   => $entry->id,
        'tagcount'  => count($tagsinfo),
        'tags'      => $tagsinfo,               // Array goes here — Moodle JSON-encodes it.
    ],
];

// Trigger the appropriate event.
if (!empty($tagsinfo)) {
    $event = entry_tags_deleted::create($params);
} else {
    $event = entry_deleted::create($params);
}
$event->trigger();

// Redirect back to the view page.
$deleteurl = new moodle_url('/mod/diary/view.php', ['id' => $id]);
redirect($deleteurl);