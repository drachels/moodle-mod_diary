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

use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\output\pix_icon;
use core\url;
use core_courseformat\local\overview\overviewitem;
use mod_diary\manager;

/**
 * diary overview integration (for Moodle 5.1+)
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * @var manager the diary manager.
     */
    private manager $manager;
    /**
     * @var \core\output\renderer_helper $rendererhelper the renderer helper
     */
    private \core\output\renderer_helper $rendererhelper;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        \core\output\renderer_helper $rendererhelper
    ) {
        parent::__construct($cm);
        $this->rendererhelper = $rendererhelper;
        $this->manager = manager::create_from_coursemodule($cm);
    }

        /**
     * Retrieves the number of entries for the diary.
     *
     * @return overviewitem|null An overview item.
     */
    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'studentswhowrote' => $this->get_extra_responses_overview(),
            'write' => $this->get_extra_status_for_user(),
        ];
    }

    /**
     * Retrieves an overview of entries for the diary.
     *
     * @return overviewitem|null An overview item c, or null if the user lacks the required capability.
     */
    private function get_extra_responses_overview(): ?overviewitem {
        global $USER;

        if (!has_capability('mod/diary:manageentries', $this->manager->get_coursemodule()->context)) {
            return null;
        }
        if (is_callable([$this, 'get_groups_for_filtering'])) {
            $groupids = array_keys($this->get_groups_for_filtering());
        } else {
            $groupids = [];
        }

        $submissions = $this->manager->count_all_users_answered($groupids);
        $total = $this->manager->count_all_users($groupids);

        if (defined('button::SECONDARY_OUTLINE')) {
            $secondaryoutline = button::SECONDARY_OUTLINE;
            $buttonclass = $secondaryoutline->classes();
        } else {
            $buttonclass = "btn btn-outline-secondary";
        }

        $content = new action_link(
            new url('/mod/diary/report.php', ['id' => $this->cm->id]),
            get_string('count_of_total', 'core', ['count' => $submissions, 'total' => $total]),
            null,
            ['class' => $buttonclass]
        );

        return new overviewitem(
            get_string('entries', 'diary'),
            $submissions,
            $content,
            text_align::CENTER
        );
    }

    /**
     * Get the diary status overview item.
     *
     * @return overviewitem|null An overview item or null for teachers.
     */
    private function get_extra_status_for_user(): ?overviewitem {
        if (
            has_capability('mod/diary:manageentries', $this->cm->context) ||
            has_capability('mod/diary:rate', $this->cm->context)
        ) {
            return null;
        }

        $status = $this->manager->has_answered();
        $statustext = get_string('notstarted', 'diary');
        if ($status) {
            $statustext = get_string('started', 'diary');
        }
        $diarystatuscontent = "-";
        if ($status) {
            $diarystatuscontent = new pix_icon(
                'i/checkedcircle',
                $statustext,
                'core',
                ['class' => 'text-success']
            );
        }
        return new overviewitem(
            get_string('entry', 'diary'),
            $status,
            $diarystatuscontent,
            text_align::CENTER
        );
    }
}
