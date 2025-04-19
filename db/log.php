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
 * Definition of log events
 *
 * @package   mod_diary
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

$logs = [
    [
        'module' => 'diary',
        'action' => 'view',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'view all',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'view responses',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'add entry',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'update entry',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'update feedback',
        'mtable' => 'diary',
        'field' => 'name',
    ],
    [
        'module' => 'diary',
        'action' => 'transfer',
        'mtable' => 'diary',
        'field' => 'name',
    ],
];
