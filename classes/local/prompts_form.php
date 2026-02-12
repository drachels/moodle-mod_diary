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
 * Prompt form definition.
 *
 * @package    mod_diary
 * @copyright 2026 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\local;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class prompt_form
 */
class prompts_form extends moodleform {
    /**
     * Define the Diary Prompts input form called from prompt_edit.php.
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // 20201119 Get the, Edit entry dates, setting for this Diary activity.
        $mform->addElement('hidden', 'diaryid');
        $mform->setType('diaryid', PARAM_INT);

        // 20210613 Retrieve customdata info for use.
        // 20240806 Changed the way variable data is transfered.
        $mform->addElement('hidden', 'promptid');
        $mform->setType('promptid', PARAM_INT);
        $mform->addElement('hidden', 'timeclose');
        $mform->setType('timeclose', PARAM_INT);
        $mform->addElement('hidden', 'editall');
        $mform->setType('editall', PARAM_INT);
        $mform->addElement('hidden', 'editdates');
        $mform->setType('editdates', PARAM_INT);

        $plugin = 'mod_diary';
        $ratingoptions = diarystats::get_rating_options($plugin);
        // 20220920 Cache options for form elements to input text.
        $shorttextoptions = ['size' => 3, 'style' => 'width: auto'];
        $mediumtextoptions = ['size' => 5, 'style' => 'width: auto'];
        $longtextoptions = ['size' => 10, 'style' => 'width: auto'];
        // 20240911 Options for the text editor textarea.
        $textedoptions = ['wrap' => 'virtual', 'rows' => 15, 'style' => 'width: auto'];

        // 20260111 Added for passing options to the listed variables for future use.
        $maxbytes = $this->_customdata['maxbytes'];
        $maxfiles = $this->_customdata['maxfiles'];
        $subdirs  = $this->_customdata['subdirs'];
        $texttrust = $this->_customdata['texttrust'];
        $enablefilemanagement = $this->_customdata['enablefilemanagement'];
        $context = $this->_customdata['editoroptions']['context']; // Access context correctly.

        $mform->addElement('date_time_selector', 'datestart', get_string('datestart', 'mod_diary', 'promptid'));
        $mform->setType('datestart', PARAM_INT);
        $mform->addElement('date_time_selector', 'datestop', get_string('datestop', 'mod_diary', 'promptid'));
        $mform->setType('stopdate', PARAM_INT);

        // Text editor settings.
        $name = 'prompt';
        $label = get_string($name, $plugin);
        // 2026110 Change the editor to use cleaned options.
        $mform->addElement(
            'editor',
            'text_editor',
            get_string('prompt', 'mod_diary')
        ); // Use a plain string label here.
        $mform->setType('text_editor', PARAM_RAW);
        $mform->addRule('text_editor', null, 'required', null, 'client');

        // Diary prompt background colour setting.
        $name = 'promptbgc';
        $label = get_string('tablecolumnpromptsbgc', $plugin);
        $mform->addElement('text', $name, $label, ['id' => 'diary_color_picker']);
        $mform->setType($name, PARAM_TEXT);
        $mform->setDefault($name, '#ffffff');
        $mform->addHelpButton($name, 'tablecolumnpromptsbgc', $plugin);
        $mform->addElement('html', "
            <script>
                 var cp = document.getElementById('diary_color_picker');
                 if (cp) {
                     // Simple map for legacy color words
                     var colors = {
                         'red': '#ff0000', 'blue': '#0000ff', 'green': '#008000',
                         'yellow': '#ffff00', 'pink': '#ffc0cb', 'white': '#ffffff',
                         'orange': '#ffa500', 'purple': '#800080'
                     };

                     // If the current value is a word in our map, swap it for the Hex
                     var currentVal = cp.value.toLowerCase();
                     if (colors[currentVal]) {
                         cp.value = colors[currentVal];
                     }

                     cp.type = 'color';
                     cp.style.width = '60px';
                     cp.style.height = '35px';
                     cp.style.cursor = 'pointer';
                 }
             </script>
        ");

        // 20220923 Added minimum character count setting.
        $name = 'minchar';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $shorttextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added maximum character count setting.
        $name = 'maxchar';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added a selector to set error percent of each minimum or maximum character penalty.
        $name = 'minmaxcharpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);

        // 20220923 Added minimum word count setting.
        $name = 'minword';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $shorttextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added maximum word count setting.
        $name = 'maxword';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added a selector to set error percent of each minimum or maximum word penalty.
        $name = 'minmaxwordpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);

        // 20220923 Added minimum sentence count setting.
        $name = 'minsentence';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $shorttextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added maximum sentence count setting.
        $name = 'maxsentence';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added a selector to set error percent of each minimum or maximum sentence penalty.
        $name = 'minmaxsentencepercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);

        // 20220923 Added minimum paragraph count setting.
        $name = 'minparagraph';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $shorttextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added maximum paragraph count setting.
        $name = 'maxparagraph';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);

        // 20220923 Added a selector to set error percent of each minimum or maximum paragraph penalty.
        $name = 'minmaxparagraphpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'firstkey');
        $mform->setType('firstkey', PARAM_INT);
        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);
        // 20260111 Prompt works on .org but not here on .com, which had this missing.
        $mform->addElement('hidden', 'promptid');
        $mform->setType('promptid', PARAM_INT);
        $this->add_action_buttons();
    }
}
