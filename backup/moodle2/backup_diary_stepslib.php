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
 * @package mod_diary
 * @subpackage backup-moodle2
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_diary_activity_task.
 */

/**
 * Define the complete diary structure for backup, with file and id annotations.
 */
class backup_diary_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the diary activity.
     * @return void
     */
    protected function define_structure() {

        $diary = new backup_nested_element('diary', array('id'), array(
            'name', 'intro', 'introformat', 'days', 'scale', 'timemodified', 'assessed', 'assesstimestart', 'assesstimefinish'));

        $entries = new backup_nested_element('entries');

        $entry = new backup_nested_element('entry', array('id'), array(
            'userid', 'timecreated', 'timemodified', 'text', 'format', 'rating',
            'entrycomment', 'teacher', 'timemarked', 'mailed'));

        $diary->add_child($entries);
        $entries->add_child($entry);

        // Sources.
        $diary->set_source_table('diary', array('id' => backup::VAR_ACTIVITYID));

        if ($this->get_setting_value('userinfo')) {
            $entry->set_source_table('diary_entries', array('diary' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('user', 'teacher');

        // Define file annotations
        $diary->annotate_files('mod_diary', 'intro', null); // This file areas haven't itemid.
        $entry->annotate_files('mod_diary_entries', 'text', null); // This file areas haven't itemid.
        $entry->annotate_files('mod_diary_entries', 'entrycomment', null); // This file areas haven't itemid.

        return $this->prepare_activity_structure($diary);
    }
}
