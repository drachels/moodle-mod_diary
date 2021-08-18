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

    if ($oldversion < 2021081200) {

        // Define field entrybgc to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('entrybgc', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, '#93FC84', 'editdates');

        // Conditionally launch add field entrybgc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field entrytextbgc to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('entrytextbgc', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, '#EEFC84', 'entrybgc');

        // Conditionally launch add field entrytextbgc.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field enablestats to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('enablestats', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'entrytextbgc');

        // Conditionally launch add field enablestats.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field mincharlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('mincharlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'enablestats');

        // Conditionally launch add field mincharlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxcharlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('maxcharlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'mincharlimit');

        // Conditionally launch add field maxcharlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minwordlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minwordlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'maxcharlimit');

        // Conditionally launch add field minwordlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxwordlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('maxwordlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minwordlimit');

        // Conditionally launch add field maxwordlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minmaxpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minmaxpercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'maxwordlimit');

        // Conditionally launch add field minmaxpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field enableautorating to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('enableautorating', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'minmaxpercent');

        // Conditionally launch add field enableautorating.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itemtype to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itemtype', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'enableautorating');

        // Conditionally launch add field itemtype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itemcount to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itemcount', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'itemtype');

        // Conditionally launch add field itemcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itempercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itempercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'itemcount');

        // Conditionally launch add field itempercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field showtextstats to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('showtextstats', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'itempercent');

        // Conditionally launch add field showtextstats.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field textstatitems to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('textstatitems', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'showtextstats');

        // Conditionally launch add field textstatitems.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorcmid to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorcmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'textstatitems');

        // Conditionally launch add field errorcmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorpercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'errorcmid');

        // Conditionally launch add field errorpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2021081200, 'diary');
    }

    return true;
}
