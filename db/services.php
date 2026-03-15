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
 * List of services for mod_diary.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_diary_view_diary' => [
        'classname' => 'mod_diary\\external\\view_diary',
        'methodname' => 'execute',
        'description' => 'Trigger the course module viewed event for Diary.',
        'type' => 'write',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_diary_set_text' => [
        'classname' => 'mod_diary\\external\\set_text',
        'methodname' => 'execute',
        'description' => 'Create or update the current user diary entry text.',
        'type' => 'write',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_diary_save_feedback' => [
        'classname' => 'mod_diary\\external\\save_feedback',
        'methodname' => 'execute',
        'description' => 'Save teacher feedback and rating for a diary entry.',
        'type' => 'write',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
