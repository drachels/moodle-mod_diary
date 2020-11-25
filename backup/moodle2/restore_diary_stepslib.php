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
use mod_diary\local\results;
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
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('diary', '/activity/diary');

        if ($userinfo) {
            $paths[] = new restore_path_element('diary_entry', '/activity/diary/entries/entry');
            $paths[] = new restore_path_element('diary_entry_rating', '/activity/diary/entries/entry/ratings/rating');
            $paths[] = new restore_path_element('diary_entry_tag', '/activity/diary/entriestags/tag');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a diary restore.
     *
     * @param object $diary
     *            The diary in object form
     * @return void
     */
    protected function process_diary($diary) {
        global $DB;

        $diary = (object) $diary;
        $oldid = $diary->id;
        $diary->course = $this->get_courseid();

        unset($diary->id);

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $diary->course = $this->get_courseid();
        $diary->assesstimestart = $this->apply_date_offset($diary->assesstimestart);
        $diary->assesstimefinish = $this->apply_date_offset($diary->assesstimefinish);
        $diary->timemodified = $this->apply_date_offset($diary->timemodified);
        $diary->timeopen = $this->apply_date_offset($diary->timeopen);
        $diary->timeclose = $this->apply_date_offset($diary->timeclose);

        if ($diary->scale < 0) { // Scale found, get mapping.
            $diary->scale = - ($this->get_mappingid('scale', abs($diary->scale)));
        }

        // Insert the data record.
        $newid = $DB->insert_record('diary', $diary);
        $this->apply_activity_instance($newid);
    }

    /**
     * Process a diaryentry restore.
     *
     * @param object $diaryentry
     *            The diaryentry in object form.
     * @return void
     */
    protected function process_diary_entry($diaryentry) {
        global $DB;

        $diaryentry = (object) $diaryentry;

        $oldid = $diaryentry->id;
        unset($diaryentry->id);

        $diaryentry->diary = $this->get_new_parentid('diary');
        $diaryentry->timemcreated = $this->apply_date_offset($diaryentry->timecreated);
        $diaryentry->timemodified = $this->apply_date_offset($diaryentry->timemodified);
        $diaryentry->timemarked = $this->apply_date_offset($diaryentry->timemarked);
        $diaryentry->userid = $this->get_mappingid('user', $diaryentry->userid);

        $newid = $DB->insert_record('diary_entries', $diaryentry);
        $this->set_mapping('diary_entry', $oldid, $newid);
    }

    /**
     * Add tags to restored entries.
     *
     * @param stdClass $data
     *            Tag
     */
    protected function process_diary_entry_tag($data) {
        $data = (object) $data;

        if (! core_tag_tag::is_enabled('mod_diary', 'diary_entries')) { // Tags disabled in server, nothing to process.
            return;
        }

        if (! $itemid = $this->get_mappingid('diary_entries', $data->itemid)) {
            // Some orphaned tag, we could not find the data record for it - ignore.
            return;
        }

        $tag = $data->rawname;
        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_diary', 'diary_entries', $itemid, $context, $tag);
    }

    /**
     * Process diary entries to provide a rating restore.
     *
     * @param object $data
     *            The data in object form.
     * @return void
     */
    protected function process_diary_entry_rating($data) {
        global $DB;

        $data = (object) $data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created).
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('diary_entry');
        if ($data->scaleid < 0) { // Scale found, get mapping.
            $data->scaleid = - ($this->get_mappingid('scale', abs($data->scaleid)));
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
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_diary', 'intro', null);
        $this->add_related_files('mod_diary_entries', 'text', null);
        $this->add_related_files('mod_diary_entries', 'entrycomment', null);
    }
}
