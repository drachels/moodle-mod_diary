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
 * Administration settings definitions for the Diary module.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
defined('MOODLE_INTERNAL') || die();
use mod_diary\local\diarystats;

if ($ADMIN->fulltree) {



    // Availability settings.
    $settings->add(new admin_setting_heading('mod_diary/availibility', get_string('availability'), ''));

    $settings->add(new admin_setting_configselect('mod_diary/showrecentactivity',
        get_string('showrecentactivity', 'diary'),
        get_string('showrecentactivity', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    $settings->add(new admin_setting_configselect('mod_diary/overview',
        get_string('showoverview', 'diary'),
        get_string('showoverview', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20201015 Default edit all entries setting.
    $settings->add(new admin_setting_configselect('mod_diary/editall',
        get_string('editall', 'diary'),
        get_string('editall_help', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20201119 Default edit the date of any entry setting.
    $settings->add(new admin_setting_configselect('mod_diary/editdates',
        get_string('editdates', 'diary'),
        get_string('editdates_help', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // Appearance settings.
    $settings->add(new admin_setting_heading('mod_diary/appearance',
        get_string('appearance'), ''));

    // Date format setting.
    $settings->add(new admin_setting_configtext('mod_diary/dateformat',
        get_string('dateformat', 'diary'),
        get_string('configdateformat', 'diary'), 'M d, Y G:i', PARAM_TEXT, 15));

    // Diary entry/feedback background colour setting.
    $name = 'mod_diary/entrybgc';
    $title = get_string('entrybgc_title', 'diary');
    $description = get_string('entrybgc_descr', 'diary');
    $default = get_string('entrybgc_colour', 'diary');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Diary entry text background colour setting.
    $name = 'mod_diary/entrytextbgc';
    $title = get_string('entrytextbgc_title', 'diary');
    $description = get_string('entrytextbgc_descr', 'diary');
    $default = get_string('entrytextbgc_colour', 'diary');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

///////////////////// New stuff.

    // 20210812 Diary show/hide statistics setting.
    $name = 'mod_diary/enablestats';
    $title = get_string('enablestats_title', 'diary');
    $description = get_string('enablestats_descr', 'diary');
    $default = 1;
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20210704 Added heading for autorating options section.
    $name = 'autorating';
    $label = get_string('autorating', 'mod_diary');
    $description = get_string('autorating_help', 'mod_diary');
    $settings->add(new admin_setting_heading($name, $label, $description));
    
    // 20210708 Diary enable autorating setting.
    $name = 'mod_diary/autorating';
    $title = get_string('autorating_title', 'diary');
    $description = get_string('autorating_descr', 'diary');
    $default = 1;
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20210708 Diary itemtype setting.
    $name = 'mod_diary/itemtype';
    $title = get_string('itemtype_title', 'diary');
    $description = get_string('itemtype_descr', 'diary');
    $default = 0;
    $itemtypes = array();
    $itemtypes = diarystats::get_item_types($itemtypes);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $itemtypes));
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // 20210708 Diary expected number of items setting.
    $settings->add(new admin_setting_configtext('mod_diary/itemcount',
        get_string('itemcount', 'diary'),
        get_string('itemcount_help', 'diary'), '', PARAM_INT, 10));

    // 20210712 Diary expected number of items error percentage setting.
    $name = 'mod_diary/itempercent';
    $plugin = 'mod_diary';
    $title = get_string('itempercent', 'diary');
    $description = get_string('itempercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));

/////////////////////
    // 20210712 Added heading for min/max options section.
    $name = 'minmaxhdr';
    $label = get_string('minmaxhdr', 'mod_diary');
    $description = get_string('minmaxhdr_help', 'mod_diary');
    $settings->add(new admin_setting_heading($name, $label, $description));
    
    // 20210708 Diary minimum characters setting.
    $settings->add(new admin_setting_configtext('mod_diary/mincharacterlimit',
        get_string('mincharacterlimit', 'diary'),
        get_string('mincharacterlimit_help', 'diary'), '', PARAM_INT, 10));

    // 20210708 Diary maximum characters setting.
    $settings->add(new admin_setting_configtext('mod_diary/maxcharacterlimit',
        get_string('maxcharacterlimit', 'diary'),
        get_string('maxcharacterlimit_help', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary expected min/max error percentage setting.
    $name = 'mod_diary/minmaxcharpercent';
    $plugin = 'mod_diary';
    $title = get_string('minmaxcharpercent', 'diary');
    $description = get_string('minmaxcharpercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));

    // 20210708 Diary minimum words setting.
    $settings->add(new admin_setting_configtext('mod_diary/minwordlimit',
        get_string('minwordlimit', 'diary'),
        get_string('minwordlimit', 'diary'), '', PARAM_INT, 10));

    // 20210708 Diary maximum words setting.
    $settings->add(new admin_setting_configtext('mod_diary/maxwordlimit',
        get_string('maxwordlimit', 'diary'),
        get_string('maxwordlimit', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary expected min/max word error percentage setting.
    $name = 'mod_diary/minmaxwordpercent';
    $plugin = 'mod_diary';
    $title = get_string('minmaxwordpercent', 'diary');
    $description = get_string('minmaxwordpercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
///////////////////////////////////////////
    // 20211006 Diary minimum sentence setting.
    $settings->add(new admin_setting_configtext('mod_diary/minsentencelimit',
        get_string('minsentencelimit', 'diary'),
        get_string('minsentencelimit', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary maximum sentence setting.
    $settings->add(new admin_setting_configtext('mod_diary/maxsentencelimit',
        get_string('maxsentencelimit', 'diary'),
        get_string('maxsentencelimit', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary expected min/max sentence error percentage setting.
    $name = 'mod_diary/minmaxsentpercent';
    $plugin = 'mod_diary';
    $title = get_string('minmaxsentpercent', 'diary');
    $description = get_string('minmaxsentpercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
///////////////////////////////////////
    // 20211006 Diary minimum paragraph setting.
    $settings->add(new admin_setting_configtext('mod_diary/minparagraphlimit',
        get_string('minparagraphlimit', 'diary'),
        get_string('minparagraphlimit', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary maximum paragraph setting.
    $settings->add(new admin_setting_configtext('mod_diary/maxparagraphlimit',
        get_string('maxparagraphlimit', 'diary'),
        get_string('maxparagraphlimit', 'diary'), '', PARAM_INT, 10));

    // 20211006 Diary expected min/max paragraph error percentage setting.
    $name = 'mod_diary/minmaxparapercent';
    $plugin = 'mod_diary';
    $title = get_string('minmaxparapercent', 'diary');
    $description = get_string('minmaxparapercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
////////////////////////////////////////
/*
    // 20210712 Diary expected min/max error percentage setting.
    $name = 'mod_diary/minmaxpercent';
    $plugin = 'mod_diary';
    $title = get_string('minmaxpercent', 'diary');
    $description = get_string('minmaxpercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
*/
/////////////////////
    // 20210712 Added heading for text stats options section.
    $name = 'statshdr';
    $label = get_string('statshdr', 'mod_diary');
    $description = get_string('statshdr_help', 'mod_diary');
    $settings->add(new admin_setting_heading($name, $label, $description));

    // 20210712 Added enable/disable show statistics setting.
    $name = 'mod_diary/showtextstats';
    $plugin = 'mod_diary';
    $title = get_string('showtextstats', 'diary');
    $description = get_string('showtextstats_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_showhide_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));

// Need to finish this setting as it is currently incomplete.
// 20211005 Got this sort of working now. Got clues from activequiz plugin.
    // 20210712 Added list of statistics items setting that can be enabled/disabled.
    $name = 'mod_diary/textstatitems';
    $plugin = 'mod_diary';
    $title = get_string('textstatitems', 'diary');
    $description = get_string('textstatitems_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_textstatitems_options(true);
    //$elements = array();
    $choices = array();
    $defaults = array();

    foreach ($options as $value => $text) {
        //$elements[] = $mform->createElement('checkbox', $name."[$value]",  '', $text);
        //$choices[] = $name."[$value]";
        $choices[] = $text;
        $defaults[] = 1;
        //$choices[$qtypepluginname] = $qtype->menu_name();
        //$defaults[$qtypepluginname] = 1;
    }
    $settings->add(new admin_setting_configmulticheckbox(
        'mod_diary/textstatitems',
        get_string('textstatitems', 'diary'),
        get_string('textstatitems_help', 'diary'),
        $defaults,
        $choices));


/*
/////////////////////
    // 20210712 Added heading for common errors options section.
    $name = 'commonerrors';
    $label = get_string('commonerrors', 'mod_diary');
    $description = get_string('commonerrors_help', 'mod_diary');
    $settings->add(new admin_setting_heading($name, $label, $description));
*/
/*
    // NOT SURE THAT I CAN ADD A SETTING FOR THE GLOSSARY NAME
    // 20210712 Added selector to pick a glossary of common errors.
    $name = 'mod_diary/errorcmid';
    $plugin = 'mod_diary';
    $title = get_string('errorcmid', 'diary');
    $description = get_string('errorcmid_help', 'diary');
    $default = 0;
    //$options = array();
    //$options = diarystats::get_rating_options($plugin);

    $options = array('0' => '');
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->cms as $cmid => $cm) {
            if ($cm->modname=='glossary' && $cm->uservisible) {
                $options[$cm->id] = format_text($cm->name);
            }
        }

    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
*/
/*
    // 20210712 Diary expected common error percentage setting.
    $name = 'mod_diary/errorpercent';
    $plugin = 'mod_diary';
    $title = get_string('errorpercent', 'diary');
    $description = get_string('errorpercent_help', 'diary');
    $default = 0;
    $options = array();
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name,
        $title,
        $description,
        $default,
        $options, 10));
*/

}
