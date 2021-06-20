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
 *
 * https://docs.moodle.org/dev/lib/formslib.php_Form_Definition
 *
 * @package mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Edit user entry form for Diary
 *
 * @copyright 2019 AL Rachels <drachels@drachels.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_diary_entry_form extends moodleform
{

    /**
     * Form definition
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // 20201119 Get the, Edit entry dates, setting for this Diary activity.
        $mform->addElement('hidden', 'diary');
        $mform->setType('diary', PARAM_INT);
        $mform->setDefault('diary', $this->_customdata['diary']);

        // 20210613 Retrieve Diary info for use.
        $timeclose = $this->_customdata['editoroptions']['timeclose'];
        $editall = $this->_customdata['editoroptions']['editall'];
        $editdates = $this->_customdata['editoroptions']['editdates'];

        // 20210613 Do not just hide the date selector, skip it unless editdates is enabled. Issue #9.
        if ($editdates) {
            // 20201119 Added date selector. Can show/hide depending on the, Edit entry dates, setting.
            $mform->addElement('date_time_selector', 'timecreated', get_string('diaryentrydate', 'diary'));
            $mform->setType('timecreated', PARAM_INT);
            // 20201231 For Moodle 3.4 and higher, hide and disable calendar selector, if not enabled.
            // For Moodle 3.3 and lower, disable calendar selector, if not enabled.
            if ($CFG->branch > 33) {
                $mform->hideIf('timecreated', 'diary', 'neq', '1');
                $mform->disabledIf('timecreated', 'diary', 'neq', '1');
            } else {
                $mform->disabledIf('timecreated', 'diary', 'neq', '1');
            }
        } else {
            $mform->addElement('hidden', 'timecreated');
            $mform->setType('timecreated', PARAM_INT);
        }

        $mform->addElement('editor', 'text_editor', get_string('entry', 'mod_diary'), null, $this->_customdata['editoroptions']);
        $mform->setType('text_editor', PARAM_RAW);
        $mform->addRule('text_editor', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'firstkey');
        $mform->setType('firstkey', PARAM_INT);

        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        $this->add_action_buttons();
    }
}

