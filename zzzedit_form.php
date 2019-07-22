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

print_object('xxx spacer edit_form');
print_object('xxx spacer edit_form');
print_object('xxx spacer edit_form============================================');

echo 'This is $this->_customdata when entering edit_form page.';
print_object($this->_customdata);

        $data = $this->_customdata['entryid'];


        $mform = $this->_form;

        $currententry      = $this->_customdata['current'];
echo 'This is $currententry';
print_object($currententry);


        //$diary             = $this->_customdata['diary'];
        //$cm                = $this->_customdata['cm'];
        //$definitionoptions = $this->_customdata['definitionoptions'];
        //$attachmentoptions = $this->_customdata['attachmentoptions'];

        //$context  = context_module::instance($cm->id);
        // Prepare format_string/text options
        //$fmtoptions = array(
        //    'context' => $context);

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        //$mform->addElement('editor', 'text_editor', get_string('entry', 'mod_diary'), null, $this->_customdata['editoroptions']);
        $mform->addElement('editor', 'text_editor', get_string('entry', 'mod_diary'), null, $this->_customdata['current']);
        $mform->setType('text_editor', PARAM_RAW);
        $mform->addRule('text_editor', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHANUMEXT);
        //$mform->setConstant('action', 'templates');

        $mform->addElement('hidden', 'firstkey');
        $mform->setType('firstkey', PARAM_INT);

        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        $mform->addElement('hidden', 'timecreated');
        $mform->setType('timecreated', PARAM_INT);

// This breaks the action and makes the first part of edit fall through to the error.
//        $mform->addElement('hidden', 'action');
//        $mform->setType('action', PARAM_TEXT);


        // Maybe use this later. It adds file attachment stuff.
        // $mform->addElement('file', 'attachment', get_string('attachment', 'forum'));
        // Maybe use this later. It adds a tags list.
        // $mform->addElement('tags', 'interests', get_string('interestslist'), array('itemtype' => 'user', 'component' => 'core'));

        $this->add_action_buttons();

        $this->set_data($currententry);
echo 'This is edit_form page $currententry after set data';
print_object($currententry);
    }
}

