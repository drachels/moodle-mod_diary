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
 * Event for deletion of a diary entry along with its associated tags.
 *
 * @package   mod_diary
 * @copyright 2025 drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\event;

/**
 * The mod_diary entry tags deleted class.
 *
 * @package   mod_diary
 * @since     Moodle 3.1
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry_tags_deleted extends \core\event\base {
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
        return get_string('evententrytagsdeleted', 'mod_diary');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $tagcount = isset($this->other['tagcount']) ? $this->other['tagcount'] : 0;
        $tagsdesc = $tagcount > 0 ? " including $tagcount tag(s)" : '';

        return "The user with id '{$this->userid}' deleted diary entry '{$this->objectid}' "
             . "belonging to user '{$this->relateduserid}'$tagsdesc in the Diary activity "
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
        // Optional: check 'other' has expected keys if you want stricter validation.
    }
}
