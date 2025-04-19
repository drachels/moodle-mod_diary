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

    $name = new lang_string('alwaysshowdescription', 'mod_diary');
    $description = new lang_string('alwaysshowdescription_help', 'mod_diary');
    $setting = new admin_setting_configcheckbox('mod_diary/alwaysshowdescription',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $settings->add(new admin_setting_configselect('mod_diary/showrecentactivity',
        get_string('showrecentactivity', 'diary'),
        get_string('showrecentactivity', 'diary'), 1, [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

    $settings->add(new admin_setting_configselect('mod_diary/overview',
        get_string('showoverview', 'diary'),
        get_string('showoverview', 'diary'), 1, [
             '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

    // 20201015 Default edit all entries setting.
    $settings->add(new admin_setting_configselect('mod_diary/editall',
        get_string('editall', 'diary'),
        get_string('editall_help', 'diary'), 1, [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

    // 20201119 Default edit the date of any entry setting.
    $settings->add(new admin_setting_configselect('mod_diary/editdates',
        get_string('editdates', 'diary'),
        get_string('editdates_help', 'diary'), 1, [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

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

    // 20210812 Diary show/hide statistics setting.
    $name = 'mod_diary/enablestats';
    $title = get_string('enablestats_title', 'diary');
    $description = get_string('enablestats_descr', 'diary');
    $default = 1;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default,
        [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

    // 20231109 Diary show/hide titles setting.
    $name = 'mod_diary/enabletitles';
    $title = get_string('enabletitles_title', 'diary');
    $description = get_string('enabletitles_descr', 'diary');
    $default = 0;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default,
        [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

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
    $settings->add(new admin_setting_configselect($name, $title, $description, $default,
        [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ]
    ));

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
    $options = [];
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
    $options = [];
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options, 10));

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
    $options = [];
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options, 10));

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
    $title = get_string('minmaxparagraphpercent', 'diary');
    $description = get_string('minmaxparapercent_help', 'diary');
    $default = 0;
    $options = [];
    $options = diarystats::get_rating_options($plugin);
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options, 10));

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
    $options = [];
    $options = diarystats::get_showhide_options($plugin);
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options, 10));

    $settings->add(new admin_setting_configselect('mod_diary/teacheremail', get_string('teacheremail', 'diary'),
        get_string('teacheremail', 'diary'), 0, ['0' => get_string('no'), '1' => get_string('yes')]));

    $settings->add(new admin_setting_configselect('mod_diary/studentemail', get_string('studentemail', 'diary'),
        get_string('studentemail', 'diary'), 0, ['0' => get_string('no'), '1' => get_string('yes')]));
}
