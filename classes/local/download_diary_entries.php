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
 * Stores fields that define the status of the checklist output
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die();

class download_diary_entries {

    /**
     * Download diary entries.
     * @param array $array
     * @param string $filename - The filename to use.
     * @param string $delimiter - The character to use as a delimiter.
     * @return nothing
     */
    //public function download_diary_entries($array, $filename = "export.csv", $delimiter=";") {
    public function __construct($array, $filename = "export.csv", $delimiter=";") {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/csvlib.class.php');
        $data = new StdClass();
        $data->diary = $this->instance->id;
echo 'In the local/download_diary_entries.php file';
        exit;
    }
}