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
 * Form used to copy prompts from another Diary activity.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Copy prompts from another Diary activity in the same course.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_copy_form extends \moodleform {

    /**
     * Define the copy form.
     */
    protected function definition() {
        $mform = $this->_form;
        $sourcediaryid = $this->_customdata['sourcediaryid'] ?? 0;
        $promptoptions = $this->_customdata['promptoptions'] ?? [];

        $mform->addElement('hidden', 'id', optional_param('id', 0, PARAM_INT));
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'sourcediaryid', $sourcediaryid);
        $mform->setType('sourcediaryid', PARAM_INT);

        $mform->addElement(
            'select',
            'promptids',
            get_string('promptcopyselectprompts', 'diary'),
            $promptoptions,
            ['multiple' => 'multiple', 'size' => min(12, max(4, count($promptoptions)))]
        );
        $mform->addHelpButton('promptids', 'promptcopyselectprompts', 'diary');
        $mform->setType('promptids', PARAM_INT);
        $mform->setDefault('promptids', array_keys($promptoptions));

        $mform->addElement('advcheckbox', 'includerules', get_string('promptcopyincluderules', 'diary'));
        $mform->setDefault('includerules', 1);

        $this->add_action_buttons(true, get_string('promptcopybutton', 'diary'));
    }

    /**
     * Validate the prompt selection.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['promptids'])) {
            $errors['promptids'] = get_string('promptcopyselectpromptserror', 'diary');
        }

        return $errors;
    }
}
