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
 * This page opens the edit form instance of diary, in a particular course.
 * https://docs.moodle.org/dev/lib/formslib.php_Form_Definition
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class mod_diary_entry_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $data = $this->_customdata['current'];

        $action = $this->_customdata['editoroptions']['action'];

        $firstkey = $this->_customdata['editoroptions']['firstkey'];

        $temp = $this->_customdata['editoroptions'];

        $mform = $this->_form;

        $mform->addElement('editor', 'text_editor', get_string('entry', 'mod_diary'),
                null, $this->_customdata['editoroptions']);
        $mform->setType('text_editor', PARAM_RAW);
        $mform->addRule('text_editor', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'firstkey');
        $mform->setType('firstkey', PARAM_INT);

        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        $mform->addElement('hidden', 'timecreated');
        $mform->setType('timecreated', PARAM_INT);

        $this->add_action_buttons();
    }
}

