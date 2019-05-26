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
 * This page opens the current version instance of diary.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

// Only when master becomes a stable the version value will be changed for
// the current date. Otherwise we just increase the last NN by one.

$plugin->component = 'mod_diary';
$plugin->version  = 2019052600;
$plugin->requires = 2015111600;  // Moodle 3.0
$plugin->release = '0.8 (Build: 2019052600)';
$plugin->maturity = MATURITY_BETA;
$plugin->cron     = 60;
