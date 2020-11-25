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

if ($ADMIN->fulltree) {

    // Availability settings.
    $settings->add(new admin_setting_heading('mod_diary/availibility', get_string('availability'), ''));

    $settings->add(new admin_setting_configselect('diary/showrecentactivity',
        get_string('showrecentactivity', 'diary'),
        get_string('showrecentactivity', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    $settings->add(new admin_setting_configselect('diary/overview',
        get_string('showoverview', 'diary'),
        get_string('showoverview', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20201015 Default edit all entries setting.
    $settings->add(new admin_setting_configselect('diary/editall',
        get_string('editall', 'diary'),
        get_string('editall_help', 'diary'), 1, array(
        '0' => get_string('no'),
        '1' => get_string('yes')
    )));

    // 20201119 Default edit the date of any entry setting.
    $settings->add(new admin_setting_configselect('diary/editdates',
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
}