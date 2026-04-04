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

namespace mod_diary\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_diary\local\prompts;

/**
 * External function to create/update a user Diary entry from mobile.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_text extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'entryid' => new external_value(PARAM_INT, 'Diary entry id (0 for create)', VALUE_DEFAULT, 0),
            'text' => new external_value(PARAM_RAW, 'Diary entry text'),
            'format' => new external_value(PARAM_INT, 'Text format', VALUE_DEFAULT, FORMAT_MOODLE),
            'itemid' => new external_value(PARAM_INT, 'Draft item id for files', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status string'),
            'entryid' => new external_value(PARAM_INT, 'Saved entry id'),
            'text' => new external_value(PARAM_RAW, 'Saved text'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $entryid
     * @param string $text
     * @param int $format
     * @param int $itemid
     * @return array
     */
    public static function execute($cmid, $entryid = 0, $text = '', $format = FORMAT_MOODLE, $itemid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'entryid' => $entryid,
            'text' => $text,
            'format' => $format,
            'itemid' => $itemid,
        ]);

        $cm = get_coursemodule_from_id('diary', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $diary = $DB->get_record('diary', ['id' => $cm->instance], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login($course, false, $cm);
        require_capability('mod/diary:addentries', $context);

        if (!\mod_diary\local\results::diary_available($diary)) {
            throw new \moodle_exception('editingended', 'mod_diary');
        }

        $timenow = time();
        $savedentryid = (int)$params['entryid'];

        if ($savedentryid > 0) {
            $entry = $DB->get_record('diary_entries', [
                'id' => $savedentryid,
                'diary' => $diary->id,
                'userid' => $USER->id,
            ], '*', MUST_EXIST);

            $record = (object)[
                'id' => $entry->id,
                'text' => $params['text'],
                'format' => $params['format'],
                'timemodified' => $timenow,
            ];
            $DB->update_record('diary_entries', $record);

            $event = \mod_diary\event\entry_updated::create([
                'objectid' => $entry->id,
                'context' => $context,
            ]);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('diary', $diary);
            $event->trigger();
        } else {
            $newentry = (object)[
                'userid' => $USER->id,
                'diary' => $diary->id,
                'promptid' => prompts::get_current_promptid($diary),
                'timecreated' => $timenow,
                'timemodified' => $timenow,
                'title' => '',
                'text' => $params['text'],
                'entrynoticemailed' => 0,
                'format' => $params['format'],
            ];
            $savedentryid = (int)$DB->insert_record('diary_entries', $newentry);

            $event = \mod_diary\event\entry_created::create([
                'objectid' => $savedentryid,
                'context' => $context,
            ]);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('diary', $diary);
            $event->trigger();
        }

        return [
            'status' => 'ok',
            'entryid' => $savedentryid,
            'text' => $params['text'],
        ];
    }
}
