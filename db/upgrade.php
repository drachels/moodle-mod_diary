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
 * Upgrade code for install
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/diary/lib.php');

/**
 * Upgrade this diary instance - this function could be skipped but it will be needed later.
 *
 * @param int $oldversion
 *            The old version of the diary module
 * @return bool
 */
function xmldb_diary_upgrade($oldversion = 0) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020090200) {

        // Define field timeopen to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('timeopen', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field timeopen.
        if (! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timeclose to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('timeclose', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeopen');

        // Conditionally launch add field timeclose.
        if (! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2020090200, 'diary');
    }
    if ($oldversion < 2020101500) {

        // Define field editall to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('editall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'timeclose');

        // Conditionally launch add field editall.
        if (! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2020101500, 'diary');
    }
    if ($oldversion < 2020111900) {

        // Define field editdates to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('editdates', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'editall');

        // Conditionally launch add field editdates.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2020111900, 'diary');
    }
    return true;
}
