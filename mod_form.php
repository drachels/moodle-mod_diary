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
require_once($CFG->dirroot . '/rating/lib.php');

/**
 * Diary settings form.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_diary_mod_form extends moodleform_mod {
    /** Threshold above which Count of ratings + Point warning applies. */
    /** @var int */
    const COUNT_RATINGS_POINT_WARNING_THRESHOLD = 10;

    /** Settings for adding repeated form elements. */
    /** @var int */
    const NUM_ITEMS_DEFAULT = 0;
    /** @var int */
    const NUM_ITEMS_MIN = 0;
    /** @var int */
    const NUM_ITEMS_ADD = 1;

    /** Number of rows in TEXTAREA elements. */
    /** @var int */
    const TEXTAREA_ROWS = 3;

    /**
     * diary is plugin name without leading "mod_".
     */
    public function mod() {
        return substr($this->plugin_name(), 4);
    }

    /**
     * Plugin name is class name without trailing "mod_form"
     */
    public function plugin_name() {
        return substr(get_class($this), 0, -9);
    }

    /**
     * Fetch a constant from the plugin class.
     *
     * @param string $name
     */
    protected function plugin_constant($name) {
        $plugin = $this->plugin_name();
        return constant($plugin . '::' . $name);
    }

    /**
     * Define the diary activity settings form.
     *
     * @return void
     */
    public function definition() {
        global $COURSE, $PAGE, $CFG;
        // Cache the plugin name.
        $plugin = 'mod_diary';
        $diaryconfig = get_config('mod_diary');

        // 20210706 Add Javascript to expand/contract text input fields. NOT sure if this is needed.
        $params = [];
        $PAGE->requires->js_call_amd("$plugin/form", 'init', $params);

        // 20210706 Cache options for form elements to input text.
        $shorttextoptions = ['size' => 3, 'style' => 'width: auto'];
        $mediumtextoptions = ['size' => 5, 'style' => 'width: auto'];
        $longtextoptions = ['size' => 10, 'style' => 'width: auto'];

        // 20210706 Cache options for show/hide elements.
        // 20220115 NOT in use yet.
        $showhideoptions = diarystats::get_showhide_options($plugin);

        // 20210706 Cache options for form elements to select a rating.
        $ratingoptions = diarystats::get_rating_options($plugin);

        $mform = &$this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('diaryname', 'diary'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('diarydescription', 'diary'));

        // 20210706 Add the availability header.
        $name = 'availibilityhdr';
        $label = get_string('availability');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20200915 Moved check so daysavailable is hidden unless using weekly format.
        if ($COURSE->format == 'weeks') {
            $options = [];
            $options[0] = get_string('alwaysopen', 'diary');
            for ($i = 1; $i <= 13; $i++) {
                $options[$i] = get_string('numdays', '', $i);
            }
            for ($i = 2; $i <= 16; $i++) {
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

        $mform->addElement(
            'date_time_selector',
            'timeopen',
            get_string('diaryopentime', 'diary'),
            [
                'optional' => true,
                'step' => 1,
            ]
        );
        $mform->addHelpButton('timeopen', 'diaryopentime', 'diary');

        $mform->addElement(
            'date_time_selector',
            'timeclose',
            get_string('diaryclosetime', 'diary'),
            [
                'optional' => true,
                'step' => 1,
            ]
        );
        $mform->addHelpButton('timeclose', 'diaryclosetime', 'diary');

        // 20201015 Added Edit all, enable/disable setting. 20230925 Modified to use site default.
        $name = 'editall';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->editall);

        // 20241113 Added Delete entry, enable/disable setting.
        $name = 'deleteentry';
        $label = get_string('deleteentries', $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->deleteentries);

        // 20201119 Added Edit dates, enable/disable setting. 20230925 Modified to use site default.
        $name = 'editdates';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->editdates);

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

        // 20231109 Added enable/disable setting for titles.
        $name = 'enabletitles';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->enabletitles);

        // 20250301 Added heading for submission options section.
        $name = 'submissionsettingshdr';
        $label = get_string('submissionsettings', 'diary');
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20250301 Added enable/disable setting for email upon submitting an entry.
        $name = 'submissionemail';
        $label = get_string($name, $plugin);
        $mform->addElement('selectyesno', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);

        // 20230204 Added enable/disable setting for teacheremail.
        $options = [];
        $options['0'] = 'Delay';
        $options['1'] = 'Now';

        // 20230204 Added enable/disable setting for teacheremail.
        $name = 'teacheremail';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->teacheremail);
        $mform->disabledIf($name, 'submissionemail', 'eq', 0);

        // 20230204 Added enable/disable setting for studentemail.
        $name = 'studentemail';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->studentemail);
        $mform->disabledIf($name, 'submissionemail', 'eq', 0);

        // 20210704 Added heading for autorating options section.
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

        // 20210711 Added heading for minimum/maximum options section.
        $name = 'minmaxhdr';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        $mform->setExpanded($name, true);

        // 20210709 Added minimum character count setting.
        $name = 'mincharacterlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->mincharacterlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added maximum character count setting.
        $name = 'maxcharacterlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxcharacterlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added a selector to set error percent of each minimum or maximum character penalty.
        $name = 'minmaxcharpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->minmaxcharpercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added minimum word count setting.
        $name = 'minwordlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->minwordlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210709 Added maximum word count setting.
        $name = 'maxwordlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxwordlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added a selector to set error percent of each minimum or maximum word penalty.
        $name = 'minmaxwordpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->minmaxwordpercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added minimum sentence count setting.
        $name = 'minsentencelimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->minsentencelimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added maximum sentence count setting.
        $name = 'maxsentencelimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxsentencelimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added a selector to set error percent of each minimum or maximum sentence penalty.
        $name = 'minmaxsentpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->minmaxsentpercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added minimum paragraph count setting.
        $name = 'minparagraphlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->minparagraphlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added maximum paragraph count setting.
        $name = 'maxparagraphlimit';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, $mediumtextoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $diaryconfig->maxparagraphlimit);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20211006 Added a selector to set error percent of each minimum or maximum paragraph penalty.
        $name = 'minmaxparapercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $diaryconfig->minmaxparapercent);
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'enableautorating', 'eq', 0);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

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
        $mform->disabledIf('errorcmid', 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20210703 Added a selector to set error percent of each penalty.
        $name = 'errorpercent';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $ratingoptions);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_my_default_value($name, 5));
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'errorcmid', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // 20230508 Added a selector to enable/disable removing matches that are substrings of longer matches.
        $name = 'errorfullmatch';
        $label = get_string($name, $plugin);
        $mform->addElement('select', $name, $label, $this->diary_get_fullmatch_options($plugin));
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setDefault($name, $this->get_my_default_value($name, 1));
        $mform->setType($name, PARAM_INT);
        $mform->disabledIf($name, 'errorcmid', 'eq', 0);
        $mform->disabledIf($name, 'itemtype', 'eq', 5);
        $mform->disabledIf($name, 'enablestats', 'eq', 0);

        // Add the rest of the common settings.
        $this->standard_grading_coursemodule_elements();

        // Warn about Count of ratings with Point maximum grade >10.
        $name = 'countofratingswarning';
        $warningdata = (object) [
            'threshold' => self::COUNT_RATINGS_POINT_WARNING_THRESHOLD,
            'pointdefault' => $CFG->gradepointdefault ?? '',
            'pointmax' => $CFG->gradepointmax ?? '',
        ];
        $warning = '<div class="alert alert-warning">'
            . get_string('countofratingspointwarning', 'diary', $warningdata)
            . '</div>';
        $mform->addElement('static', $name, '', $warning);
        $mform->hideIf($name, 'assessed', 'neq', RATING_AGGREGATE_COUNT);
        $mform->hideIf($name, 'scale[modgrade_type]', 'neq', 'point');
        $mform->hideIf($name, 'scale[modgrade_point]', 'in', '|0|1|2|3|4|5|6|7|8|9|10');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $assessed = $data['assessed'] ?? 0;
        $gradetype = $data['scale']['modgrade_type'] ?? '';
        $gradepoint = $data['scale']['modgrade_point'] ?? '';

        if (
            (int)$assessed === RATING_AGGREGATE_COUNT
            && $gradetype === 'point'
            && $gradepoint !== ''
            && is_numeric($gradepoint)
            && (float)$gradepoint > self::COUNT_RATINGS_POINT_WARNING_THRESHOLD
        ) {
            $errors['scale[modgrade_point]'] = get_string(
                'countofratingspointvalidation',
                'diary',
                (object)['threshold' => self::COUNT_RATINGS_POINT_WARNING_THRESHOLD]
            );
        }

        return $errors;
    }

    /**
     * Get array of glossary options.
     *
     * @param array $courseid
     * @return array $options
     */
    protected function get_errorcmid_options($courseid = 0) {
        $options = ['0' => ''];
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->cms as $cmid => $cm) {
            if ($cm->modname == 'glossary' && $cm->uservisible) {
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
            // Moodle >= 3.10.
            return $this->get_default_value($name, $default);
        } else {
            // Moodle <= 3.9.
            return get_user_preferences($this->plugin_name() . '_' . $name, $default);
        }
    }

    /**
     * Get array of countable item types.
     *
     * @param string $plugin name
     * @return [type => description]
     */
    protected function get_itemtype_options($plugin) {
        $options['0'] = get_string('none');
        $options['1'] = get_string('chars', $plugin);
        $options['2'] = get_string('words', $plugin);
        $options['3'] = get_string('sentences', $plugin);
        $options['4'] = get_string('paragraphs', $plugin);
        return $options;
    }

    /**
     * Get array of full match options.
     *
     * @param string $plugin name
     * @return [value => description]
     */
    protected function diary_get_fullmatch_options($plugin) {
        return [0 => get_string('phrasefullmatchno', $plugin), 1 => get_string('phrasefullmatchyes', $plugin)];
    }

    /**
     * Get array of case sensitivity options.
     *
     * @param string $plugin name
     * @return [value => description]
     */
    protected function get_casesensitive_options($plugin) {
        return [0 => get_string('phrasecasesensitiveno', $plugin), 1 => get_string('phrasecasesensitiveyes', $plugin)];
    }

    /**
     * Get array of options for ignoring breaks
     *
     * @param string $plugin name
     * @return [value => description]
     */
    protected function get_ignorebreaks_options($plugin) {
        return [0 => get_string('phraseignorebreaksno', $plugin), 1 => get_string('phraseignorebreaksyes', $plugin)];
    }
}
