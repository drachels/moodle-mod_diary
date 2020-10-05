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
 * Define all the restore steps that will be used by the restore_diary_activity_task
 *
 * @package   mod_diary
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete diary structure for restore, with file and id annotations.
 *
 * @package   mod_diary
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_diary_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('diary', '/activity/diary');

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('diary_entry', '/activity/diary/entries/entry');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a diary restore.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_diary($data) {

        global $DB;

        $data = (Object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        unset($data->id);

        $data->course = $this->get_courseid();
        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        if ($data->scale < 0) { // Scale found, get mapping.
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        $newid = $DB->insert_record('diary', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Process a diary entry restore.
     * @param object $data The data in object form.
     * @return void
     */
    protected function process_diary_entry($data) {

        global $DB;

        $data = (Object)$data;

        $oldid = $data->id;
        unset($data->id);

        $data->diary = $this->get_new_parentid('diary');
        $data->timemcreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timemarked = $this->apply_date_offset($data->timemarked);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->teacher = $this->get_mappingid('user', $data->teacher);

        $newid = $DB->insert_record('diary_entries', $data);
        $this->set_mapping('diary_entry', $oldid, $newid);
    }

    /**
     * Process diary entries to provide a rating restore.
     * @param object $data The data in object form.
     * @return void
     */
    protected function process_data_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid    = $this->get_new_parentid('data_record');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);

        // We need to check that component and ratingarea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_diary';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'entry';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }

    /**
     * Once the database tables have been fully restored, restore the files
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_diary', 'intro', null);
        $this->add_related_files('mod_diary_entries', 'text', null);
        $this->add_related_files('mod_diary_entries', 'entrycomment', null);
    }
}
