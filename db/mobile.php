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
 * Mobile app definition.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_diary' => [
        'handlers' => [
            'mod_diary' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/diary/pix/icon.svg',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view',
            ],
        ],
        'lang' => [
            ['entries', 'mod_diary'],
            ['entry', 'mod_diary'],
            ['entrysuccess', 'mod_diary'],
            ['feedback', 'mod_diary'],
            ['feedbackupdated', 'mod_diary'],
            ['editingended', 'mod_diary'],
            ['editingends', 'mod_diary'],
            ['lastedited', 'moodle'],
            ['noentriesmanagers', 'mod_diary'],
            ['nograde', 'mod_diary'],
            ['notopenuntil', 'mod_diary'],
            ['notstarted', 'mod_diary'],
            ['saveallfeedback', 'mod_diary'],
            ['startoredit', 'mod_diary'],
            ['rating', 'mod_diary'],
        ],
        'css' => $CFG->wwwroot . '/mod/diary/styles.css',
    ],
];
