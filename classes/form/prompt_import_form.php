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
 * Form used to import prompts from a CSV file.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import prompts into the current Diary activity from a CSV file.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_import_form extends \moodleform {

    /**
     * Define the import form.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', optional_param('id', 0, PARAM_INT));
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'filepicker',
            'csvfile',
            get_string('promptimportfile', 'diary'),
            null,
            ['accepted_types' => ['.csv', '.txt']]
        );
        $mform->addRule('csvfile', null, 'required');
        $mform->addHelpButton('csvfile', 'promptimportfile', 'diary');

        $this->add_action_buttons(true, get_string('promptimportbutton', 'diary'));
    }
}
