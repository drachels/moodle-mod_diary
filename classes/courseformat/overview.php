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

namespace mod_diary\courseformat;

use cm_info;
use core\url;
use core\output\action_link;
use core\output\local\properties\text_align;
use core_courseformat\local\overview\overviewitem;
use core\output\local\properties\button;
use mod_diary\local\results; // Assuming this has your diary_count_entries() or similar.

/**
 * Diary overview integration for Moodle 5.0+ Activities overview page
 *
 * @package   mod_diary
 * @copyright 2025 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * Constructor.
     *
     * @param cm_info $cm the course module instance.
     */
    public function __construct(cm_info $cm) {
        parent::__construct($cm);
    }

    /**
     * Provide custom overview items (e.g. entry counts).
     *
     * @return array of overviewitem
     */
    public function get_custom_overview(): array {
        global $DB;

        $items = [];

        // Total entries count.
        $totalentries = $DB->count_records('diary_entries', ['diary' => $this->cm->instance]);

        $items[] = new overviewitem(
            get_string('totalentries', 'diary'), // You may need to add this string.
            $totalentries,
            null, // No content/link here, just value.
            text_align::CENTER
        );

        // Entries needing grading (assuming rating is null/empty for ungraded).
        // Adjust query if your "graded" logic is different (e.g. timegraded IS NULL).
        $ungraded = $DB->count_records_select(
            'diary_entries',
            'diary = ? AND (rating IS NULL OR rating = 0)',
            [$this->cm->instance]
        );

        $items[] = new overviewitem(
            get_string('ungradedentries', 'diary'), // Add this string too.
            $ungraded,
            null,
            text_align::CENTER
        );

        // Optional: link the ungraded count to the report page for quick access.
        if ($ungraded > 0 && has_capability('mod/diary:manageentries', $this->cm->context)) {
            $url = new url('/mod/diary/report.php', ['id' => $this->cm->id, 'action' => 'currententry']);
            $items[] = new overviewitem(
                get_string('gradeentrieslink', 'diary'),
                new action_link($url, get_string('viewungraded', 'diary')),
                null,
                text_align::CENTER
            );
        }

        return $items;
    }

    /**
     * Provide dates overview (open/close times if set).
     *
     * @return ?overviewitem
     */
    public function get_dates_overview(): ?overviewitem {
        $dates = [];

        if (!empty($this->cm->customdata->timeopen)) {
            $dates[] = [
                'label' => get_string('diaryopentime', 'diary'),
                'value' => userdate($this->cm->customdata->timeopen),
            ];
        }

        if (!empty($this->cm->customdata->timeclose)) {
            $dates[] = [
                'label' => get_string('diaryclosetime', 'diary'),
                'value' => userdate($this->cm->customdata->timeclose),
            ];
        }

        if (empty($dates)) {
            return null;
        }

        return new overviewitem(
            get_string('availability', 'diary'),
            $dates, // Array of label/value pairs.
            null,
            text_align::LEFT
        );
    }

    /**
     * Override to add custom actions (your existing View button).
     *
     * @return ?overviewitem
     */
    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        $url = new url('/mod/diary/view.php', ['id' => $this->cm->id]);

        $text = get_string('view');
        $bodyoutline = button::BODY_OUTLINE;

        // Your existing button class handling (looks fine).
        if (is_object($bodyoutline) && method_exists($bodyoutline, 'classes')) {
            $buttonclass = $bodyoutline->classes();
        } else {
            $buttonclass = (string) $bodyoutline;
        }

        $content = new action_link($url, $text, null, ['class' => $buttonclass]);

        return new overviewitem(get_string('actions'), $content, null, text_align::CENTER);
    }
}
