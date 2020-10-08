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
use \mod_diary\local\results;
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

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a diary restore.
     *
     * @param object $diary The diary in object form
     * @return void
     */
    protected function process_diary($diary) {

        global $DB;

        $diary = (Object)$diary;
        $oldid = $diary->id;
        $diary->course = $this->get_courseid();

        unset($diary->id);

        $diary->course = $this->get_courseid();
        $diary->assesstimestart = $this->apply_date_offset($diary->assesstimestart);
        $diary->assesstimefinish = $this->apply_date_offset($diary->assesstimefinish);
        $diary->timemodified = $this->apply_date_offset($diary->timemodified);
        $diary->timeopen = $this->apply_date_offset($diary->timeopen);
        $diary->timeclose = $this->apply_date_offset($diary->timeclose);

        if ($diary->scale < 0) { // Scale found, get mapping.
            $diary->scale = -($this->get_mappingid('scale', abs($diary->scale)));
        }

        $newid = $DB->insert_record('diary', $diary);
        $this->apply_activity_instance($newid);
    }

    /**
     * Process a diary_entry restore.
     * @param object $diary_entry The diary_entry in object form.
     * @return void
     */
    protected function process_diary_entry($diary_entry) {

        global $DB;

        $diary_entry = (Object)$diary_entry;

        $oldid = $diary_entry->id;
        unset($diary_entry->id);

        $diary_entry->diary = $this->get_new_parentid('diary');
        $diary_entry->timemcreated = $this->apply_date_offset($diary_entry->timecreated);
        $diary_entry->timemodified = $this->apply_date_offset($diary_entry->timemodified);
        $diary_entry->timemarked = $this->apply_date_offset($diary_entry->timemarked);
        $diary_entry->userid = $this->get_mappingid('user', $diary_entry->userid);

        $newid = $DB->insert_record('diary_entries', $diary_entry);
        $this->set_mapping('diary_entry', $oldid, $newid);

        $diary_entry->contextid = $this->task->get_contextid();
        $diary_entry->itemid    = $this->get_new_parentid('diary_entry');

        $diary = $DB->get_record('diary', array ('id' => $diary_entry->diary));

        if ($diary->assessed != RATING_AGGREGATE_NONE) {
            // 20201008 Added this to restore each rating table entry.
            $ratingoptions = new stdClass;
            $ratingoptions->contextid = $diary_entry->contextid;
            $ratingoptions->component = 'mod_diary';
            $ratingoptions->ratingarea = 'entry';
            $ratingoptions->itemid = $diary_entry->itemid;
            $ratingoptions->aggregate = $diary->assessed; // The aggregation method.
            $ratingoptions->scaleid = $diary->scale;
            $ratingoptions->rating = $diary_entry->rating;
            $ratingoptions->userid = $diary_entry->userid;
            $ratingoptions->timecreated = $diary_entry->timecreated;
            $ratingoptions->timemodified = $diary_entry->timemodified;

            $ratingoptions->assesstimestart = $diary->assesstimestart;
            $ratingoptions->assesstimefinish = $diary->assesstimefinish;
            // 20201008 Check if there is already a rating, and if so, just update it.
            if ($rec = results::check_rating_entry($ratingoptions)) {
                $ratingoptions->id = $rec->id;
                $DB->update_record('rating', $ratingoptions, false);
            } else {
                $DB->insert_record('rating', $ratingoptions, false);
            }
        }
    }

    /**
     * Process diary entries to provide a rating restore.
     * @param object $data The data in object form.
     * @return void
     */
/*
    protected function process_diary_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid    = $this->get_new_parentid('diary_entries');
        if ($data->scaleid < 0) { // Scale found, get mapping.
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
*/

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
