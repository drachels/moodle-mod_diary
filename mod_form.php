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
 * This file contains the forms to create and edit an instance of the diary module.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
use mod_diary\local\diarystats;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Diary settings form.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_diary_mod_form extends moodleform_mod {

    /** Settings for adding repeated form elements */
    const NUM_ITEMS_DEFAULT = 0;
    const NUM_ITEMS_MIN     = 0;
    const NUM_ITEMS_ADD     = 1;

    /** Number of rows in TEXTAREA elements */
    const TEXTAREA_ROWS = 3;

    /**
     * diary is plugin name without leading "mod_"
     */
    public function mod() {
print_object('1 mod function printing substr($this->plugin_name(), 4)): '.substr($this->plugin_name(), 4));
print_object('1 mod function printing substr($this->plugin_name(), 4)): '.substr($this->plugin_name(), 4));
print_object('1 mod function printing substr($this->plugin_name(), 4)): '.substr($this->plugin_name(), 4));
print_object('1 mod function printing substr($this->plugin_name(), 4)): '.substr($this->plugin_name(), 4));
        return substr($this->plugin_name(), 4);
    }

    /**
     * Plugin name is class name without trailing "mod_form"
     */
    public function plugin_name() {
print_object('2 plugin_name function printing substr(get_class($this), 0, -9): '.substr(get_class($this), 0, -9));

        return substr(get_class($this), 0, -9);
    }

    /**
     * Fetch a constant from the plugin class in "questiontype.php".
     */
    protected function plugin_constant($name) {
        $plugin = $this->plugin_name();
print_object('3 plugin_constant function printing $plugin: '.$plugin);
print_object('3 plugin_constant function printing $plugin: '.$plugin);
print_object('3 plugin_constant function printing $plugin: '.$plugin);
print_object('3 plugin_constant function printing $plugin: '.$plugin);
print_object('3 plugin_constant function printing $plugin: '.$plugin);

print_object('4 plugin_constant function printing $name: '.$name);
print_object('5 plugin_constant function printing $plugin::$name and here it is: '.$plugin.'::'.$name);
        return constant($plugin.'::'.$name);
    }

    /**
     * Define the diary activity settings form.
     *
     * @return void
     */
    public function definition() {
        global $COURSE, $PAGE;
        // Cache the plugin name.
        $plugin = 'mod_diary'; // $this->plugin_name();
        //$plugin = 'diary'; // $this->plugin_name();
        $diaryconfig = get_config('mod_diary');

        // 20210706 Add Javascript to expand/contract text input fields. NOT sure if this is needed.
        $params = array();
        $PAGE->requires->js_call_amd("$plugin/form", 'init', $params);

        // 20210706 Cache options for form elements to input text.
        $short_text_options  = array('size' => 3,  'style' => 'width: auto');
        $medium_text_options = array('size' => 5,  'style' => 'width: auto');
        $long_text_options   = array('size' => 10, 'style' => 'width: auto');

        // 20210706 Cache options for show/hide elements. NOT sure if this is needed.
        //$showhide_options = $this->get_showhide_options($plugin);
        $showhide_options = diarystats::get_showhide_options($plugin);

        // 20210706 Cache options for form elements to select a rating.
        //$grade_options = $this->get_grade_options($plugin);
        //$rating_options = $this->get_rating_options($plugin);
        $rating_options = diarystats::get_rating_options($plugin);

        $mform = &$this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('diaryname', 'diary'), array(
            'size' => '64'
        ));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('diarydescription', 'diary'));

        // Add the availability header.
        //$mform->addElement('header', 'availibilityhdr', get_string('availability'));
        $name = 'availibilityhdr';
        $label = get_string('availability');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20200915 Moved check so daysavailable is hidden unless using weekly format.
        if ($COURSE->format == 'weeks') {
            $options = array();
            $options[0] = get_string('alwaysopen', 'diary');
            for ($i = 1; $i <= 13; $i ++) {
                $options[$i] = get_string('numdays', '', $i);
            }
            for ($i = 2; $i <= 16; $i ++) {
                $days = $i * 7;
                $options[$days] = get_string('numweeks', '', $i);
            }
            $options[365] = get_string('numweeks', '', 52);
            $mform->addElement('select', 'days', get_string('daysavailable', 'diary'), $options);
            $mform->addHelpButton('days', 'daysavailable', 'diary');

            $mform->setDefault('days', '7');
        } else {
            $mform->setDefault('days', '0');
        }

        $mform->addElement('date_time_selector', 'timeopen', get_string('diaryopentime', 'diary'), array(
            'optional' => true,
            'step' => 1
        ));
        $mform->addHelpButton('timeopen', 'diaryopentime', 'diary');

        $mform->addElement('date_time_selector', 'timeclose', get_string('diaryclosetime', 'diary'), array(
            'optional' => true,
            'step' => 1
        ));
        $mform->addHelpButton('timeclose', 'diaryclosetime', 'diary');

        // 20201015 Added Edit all, enable/disable setting.
        $mform->addElement('selectyesno', 'editall', get_string('editall', 'diary'));
        $mform->addHelpButton('editall', 'editall', 'diary');

        // 20201119 Added Edit dates, enable/disable setting.
        $mform->addElement('selectyesno', 'editdates', get_string('editdates', 'diary'));
        $mform->addHelpButton('editdates', 'editdates', 'diary');

        // 20210704 Added heading for appearance options section.
        $name = 'appearancehdr';
        $label = get_string('appearance');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // Diary entry/feedback overall background colour setting.
        $attributes = 'size = "20"';
        $name = 'entrybgc';
        $label = get_string('entrybgc_title', 'diary');
        $description = get_string('entrybgc_descr', 'diary');
        $default = get_string('entrybgc_colour', 'diary');
        $mform->setType($name, PARAM_NOTAGS);
        $mform->addElement('text', $name, $label, $attributes);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->entrybgc);

        // Diary entry text background colour setting.
        $name = 'entrytextbgc';
        $label = get_string('entrytextbgc_title', 'diary');
        $description = get_string('entrytextbgc_descr', 'diary');
        $default = get_string('entrytextbgc_colour', 'diary');
        $mform->setType($name, PARAM_NOTAGS);
        $mform->addElement('text', $name, $label, $attributes);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->entrytextbgc);

        // 20210812 Added enable/disable setting for statistics.
        $name = 'enablestats';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->enablestats);

////////// 20210704 Added heading for autorating options section.
        $name = 'autorating';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20210709 Added enable/disable setting for autorating.
        $name = 'enableautorating';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->autorating);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added setting for what type of item to count.
        $name = 'itemtype';
        $label = get_string($name, $plugin);
        $options = $this->get_itemtype_options($plugin);
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->itemtype);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);


        // 20210709 Added item count setting.
        $name = 'itemcount';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->itemcount);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210711 Added a selector to set error percent of each penalty in auto-rating section.
        $name = 'itempercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $rating_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->itempercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210711 Added heading for minimum/maximum options section.
        $name = 'minmaxhdr';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20210709 Added minimum character count setting.
        $name = 'mincharlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->mincharlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 1);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added maximum character count setting.
        $name = 'maxcharlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxcharlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 1);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added minimum word count setting.
        $name = 'minwordlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->minwordlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'itemtype', 'eq', $this->plugin_constant('ITEM_TYPE_NONE'));
        //$mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 2);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added maximum word count setting.
        $name = 'maxwordlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $medium_text_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxwordlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'itemtype', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 2);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210711 Added a selector to set error percent of each minimum or maximum penalty.
        $name = 'minmaxpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $rating_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->minmaxpercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210711 Added heading for statistics options section.
        $name = 'statshdr';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20210709 Added enable/disable show statistics setting.
        $name = 'showtextstats';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $showhide_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, diarystats::get_showhide_options($plugin, 0));
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

//////======================This setting is NOT currently working.===================================
        // 20210709 Added list of statistics items setting that can be enabled/disabled.
        $name = 'textstatitems';
        $label = get_string($name, $plugin);
        $options = diarystats::get_textstatitems_options(true);
        // $options = self::get_textstatitems_options(true);
        $elements = array();
        foreach ($options as $value => $text) {
            $elements[] = $mform->createElement('checkbox', $name."[$value]",  '', $text);
        }
        $mform->addGroup($elements, $name, $label, html_writer::empty_tag('br'), false);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'showtextstats', 'eq', $this->plugin_constant('SHOW_NONE'));
        $mform->disabledIf($name, 'showtextstats', 'eq', 0);
        $mform->disabledIf($name.'[commonerrors]', 'errorcmid', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // only use defaults on new record
        //$defaults = 'words,wordspersentence,uniquewords,longwords';
        //$defaults = $this->get_my_default_value($name, $defaults);
        //$defaults = explode(',', $defaults);
        //$defaults = array_filter($defaults);

        foreach ($options as $value => $text) {
            $mform->setType($name."[$value]", PARAM_INT);
            //$mform->setDefault($name."[$value]", in_array($value, $defaults));
        }

        // 20210703 Added the common errors header.
        $name = 'commonerrors';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20210703 Added selector to pick a glossary of common errors.
        $name = 'errorcmid';
        $label = get_string($name, $plugin);
        $options = $this->get_errorcmid_options($PAGE->course->id);
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType('errorcmid', PARAM_INT);
        //$mform->disabledIf('errorcmid', 'enableautorating', 'eq', 0);
        //$mform->disabledIf('errorcmid', 'itemtype', 'eq', $this->plugin_constant('ITEM_TYPE_FILES'));
        //$mform->disabledIf('errorcmid', 'itemtype', 'eq', self::plugin_constant('ITEM_TYPE_FILES'));
        $mform->disabledIf('errorcmid', 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210703 Added a selector to set error percent of each penalty.
        $name = 'errorpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $rating_options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_my_default_value($name, 5));
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'errorcmid', 'eq', 0);
        //$mform->disabledIf($name, 'enableautorating', 'eq', 0);
        //$mform->disabledIf($name, 'itemtype', 'eq', $this->plugin_constant('ITEM_TYPE_FILES'));
        //$mform->disabledIf($name, 'itemtype', 'eq', self::plugin_constant('Files'));
        $mform->disabledIf($name, 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // Add the rest of the common settings.
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Get array of glossary options
     *
     * @return array(glossaryid => name)
     */
    protected function get_errorcmid_options($courseid=0) {
        $options = array('0' => '');
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->cms as $cmid => $cm) {
            if ($cm->modname=='glossary' && $cm->uservisible) {
                $options[$cm->id] = format_text($cm->name);
            }
        }
        return $options;
    }

    /**
     * Returns default value for an item.
     *
     * @param string $name Item name
     * @param string|mixed|null $default Default value (optional, default = null)
     * @return string|mixed|null Default value for field with this $name
     */
    protected function get_my_default_value($name, $default) {
        if (method_exists($this, 'get_default_value')) {
            // Moodle >= 3.10
//print_object('In the function get_my_defaults using get_default_value with Moodle >= 3.10 and printing $this, $name and $default');
//print_object($this);
//print_object($name);
//print_object($default);
            return $this->get_default_value($name, $default);
        } else {
            // Moodle <= 3.9
//print_object('In the function get_my_defaults using get_user_preferences with Moodle <= 3.9 and printing $this, $name and $default');
//print_object($this);
//print_object($name);
//print_object($default);
            return get_user_preferences($this->plugin_name().'_'.$name, $default);
        }
    }

    /**
     * Get array of rating options.
     *
     * @param string $plugin name
     * @return array(rating => description)
     */
    // moved to diarystats.php
/*
    protected function get_rating_options($plugin) {
        $options = array();
        for ($i=0; $i<=100; $i++) {
            $options[$i] = get_string('percentofentryrating', $plugin, $i);
        }
        return $options;
    }
*/
    /**
     * Get array of countable item types.
     *
     * @param string $plugin name
     * @return array(type => description)
     */
    protected function get_itemtype_options($plugin) {
/*
        return array($this->plugin_constant('ITEM_TYPE_NONE') => get_string('none'),
                     $this->plugin_constant('ITEM_TYPE_CHARS') => get_string('chars', $plugin),
                     $this->plugin_constant('ITEM_TYPE_WORDS') => get_string('words', $plugin),
                     $this->plugin_constant('ITEM_TYPE_SENTENCES') => get_string('sentences', $plugin),
                     $this->plugin_constant('ITEM_TYPE_PARAGRAPHS') => get_string('paragraphs', $plugin),
                     $this->plugin_constant('ITEM_TYPE_FILES') => get_string('files', $plugin));
    }
*/

        $options['0'] = get_string('none');
        $options['1'] = get_string('chars', $plugin);
        $options['2'] = get_string('words', $plugin);
        $options['3'] = get_string('sentences', $plugin);
        $options['4'] = get_string('paragraphs', $plugin);
        $options['5'] = get_string('files', $plugin);
        return $options;
    }

    /**
     * Get array of show/hide options
     *
     * @param string $plugin name
     * @return array(type => description)
     */

    protected function get_showhide_options($plugin) {
        return array($this->plugin_constant('SHOW_NONE') => get_string('no'),
                     $this->plugin_constant('SHOW_STUDENTS_ONLY') => get_string('showtostudentsonly', $plugin),
                     $this->plugin_constant('SHOW_TEACHERS_ONLY') => get_string('showtoteachersonly', $plugin),
                     $this->plugin_constant('SHOW_TEACHERS_AND_STUDENTS') => get_string('showtoteachersandstudents', $plugin));
    }
/*
        $options['0'] = get_string('shownone', $plugin);
        $options['1'] = get_string('showstudentsonly', $plugin);
        $options['2'] = get_string('showteachersonly', $plugin);
        $options['3'] = get_string('showteacherandstudents', $plugin);
        return $options;
    }
*/

    /**
     * Get array of countable item types.
     * Duplicated in diarystats.php about line 846.
     * @return array(type => description)
     */
    /*
    protected function get_textstatitems_options($returntext=true) {
        $options = array('words',
                         'sentences', 'paragraphs',
                         'uniquewords', 'longwords',
                         'charspersentence', 'wordspersentence',
                         'longwordspersentence', 'sentencesperparagraph',
                         'lexicaldensity', 'fogindex',
                         'commonerrors', 'files');
        if ($returntext) {
            $plugin = 'mod_diary'; // $this->plugin_name();
            $options = array_flip($options);
            foreach (array_keys($options) as $option) {
                $options[$option] = get_string($option, $plugin);
            }
        }
        return $options;
    }
    */
}
