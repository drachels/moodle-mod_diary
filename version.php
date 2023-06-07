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
 * Defines the version of Diary.
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package   mod_diary
 * @copyright 2019 onwards AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023050800;      // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2019052000;      // Requires Moodle 3.7 version.
$plugin->cron = 60;              // Period for cron to check this module (secs).
$plugin->component = 'mod_diary';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.7.4 (Build: 2023050800)'; // User-friendly version number.
