<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
// ... (header and namespace unchanged)

class entry_deleted extends \core\event\base {
    protected function init() {
        $this->data['crud']         = 'd';
        $this->data['edulevel']     = self::LEVEL_PARTICIPATING;
        $this->data['objecttable']  = 'diary_entries';
    }

    public static function get_name() {
        return get_string('evententrydeleted', 'mod_diary');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' deleted diary entry '{$this->objectid}' "
             . "belonging to user '{$this->relateduserid}' in the Diary activity "
             . "with course module id '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/diary/view.php', ['id' => $this->contextinstanceid]);
    }

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
