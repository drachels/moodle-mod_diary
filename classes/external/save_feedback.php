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
use mod_diary\local\results;

/**
 * External function to save teacher feedback for a Diary entry.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_feedback extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'entryid' => new external_value(PARAM_INT, 'Diary entry id'),
            'userid' => new external_value(PARAM_INT, 'Entry owner user id'),
            'grade' => new external_value(PARAM_INT, 'Grade value (-1 means no grade)'),
            'feedback' => new external_value(PARAM_RAW, 'Feedback text', VALUE_DEFAULT, ''),
            'itemid' => new external_value(PARAM_INT, 'Draft item id', VALUE_DEFAULT, 0),
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
            'changed' => new external_value(PARAM_INT, '1 if changed, else 0'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $entryid
     * @param int $userid
     * @param int $grade
     * @param string $feedback
     * @param int $itemid
     * @return array
     */
    public static function execute($cmid, $entryid, $userid, $grade, $feedback = '', $itemid = 0) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'entryid' => $entryid,
            'userid' => $userid,
            'grade' => $grade,
            'feedback' => $feedback,
            'itemid' => $itemid,
        ]);

        $cm = get_coursemodule_from_id('diary', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $diary = $DB->get_record('diary', ['id' => $cm->instance], '*', MUST_EXIST);
        $diary->cmidnumber = $cm->idnumber;

        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login($course, false, $cm);
        require_capability('mod/diary:manageentries', $context);

        $entry = $DB->get_record('diary_entries', [
            'id' => $params['entryid'],
            'diary' => $diary->id,
            'userid' => $params['userid'],
        ], '*', MUST_EXIST);

        $newrating = ($params['grade'] < 0) ? null : (int)$params['grade'];
        $newcomment = clean_text((string)$params['feedback'], FORMAT_HTML);

        $ratingchanged = ((string)$entry->rating !== (string)$newrating);
        $commentchanged = ((string)$entry->entrycomment !== (string)$newcomment);
        $changed = ($ratingchanged || $commentchanged);

        if (!$changed) {
            return ['status' => 'ok', 'changed' => 0];
        }

        $timenow = time();
        $transaction = $DB->start_delegated_transaction();

        $update = (object)[
            'id' => $entry->id,
            'rating' => $newrating,
            'entrycomment' => $newcomment,
            'teacher' => $USER->id,
            'timemarked' => $timenow,
            'mailed' => 0,
        ];
        $DB->update_record('diary_entries', $update);

        if ((int)$diary->assessed !== 0) {
            $ratingoptions = new \stdClass();
            $ratingoptions->contextid = $context->id;
            $ratingoptions->component = 'mod_diary';
            $ratingoptions->ratingarea = 'entry';
            $ratingoptions->itemid = $entry->id;
            $ratingoptions->aggregate = $diary->assessed;
            $ratingoptions->scaleid = $diary->scale;
            $ratingoptions->userid = $entry->userid;
            $ratingoptions->timecreated = $entry->timecreated;
            $ratingoptions->timemodified = $timenow;
            $ratingoptions->returnurl = $CFG->wwwroot . '/mod/diary/report.php?id=' . $cm->id;
            $ratingoptions->assesstimestart = $diary->assesstimestart;
            $ratingoptions->assesstimefinish = $diary->assesstimefinish;

            if ($newrating === null) {
                $DB->delete_records('rating', [
                    'contextid' => $context->id,
                    'component' => 'mod_diary',
                    'ratingarea' => 'entry',
                    'itemid' => $entry->id,
                    'userid' => $entry->userid,
                ]);
            } else {
                $ratingoptions->rating = $newrating;
                if ($rec = results::check_rating_entry($ratingoptions)) {
                    $ratingoptions->id = $rec->id;
                    $DB->update_record('rating', $ratingoptions, false);
                } else {
                    $DB->insert_record('rating', $ratingoptions, false);
                }
            }
        }

        diary_update_grades($diary, $entry->userid);

        $event = \mod_diary\event\feedback_updated::create([
            'objectid' => $diary->id,
            'context' => $context,
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('diary', $diary);
        $event->trigger();

        $transaction->allow_commit();

        return ['status' => 'ok', 'changed' => 1];
    }
}
