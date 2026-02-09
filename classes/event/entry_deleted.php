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
 * The mod_diary entry deleted event.
 *
 * @package   mod_diary
 * @copyright 2024 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The mod_diary entry deleted class.
 *
 * @package   mod_diary
 * @since     Moodle 2.7
 * @copyright 2024 drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry_deleted extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud']         = 'd';
        $this->data['edulevel']     = self::LEVEL_PARTICIPATING;
        $this->data['objecttable']  = 'diary_entries';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('evententrydeleted', 'mod_diary');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' deleted diary entry '{$this->objectid}' "
             . "belonging to user '{$this->relateduserid}' in the Diary activity "
             . "with course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/diary/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' must be set to the deleted entry ID.');
        }
    }
}
