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
defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

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

    if ($oldversion < 2022012200) {
        // Define field alwaysshowdescription to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('alwaysshowdescription', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'introformat');

        // Conditionally launch add field alwaysshowdescription.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

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

        // Define field mincharacterlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('mincharacterlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'enablestats');

        // Conditionally launch add field mincharacterlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxcharacterlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('maxcharacterlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'mincharacterlimit');

        // Conditionally launch add field maxcharacterlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minmaxcharpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minmaxcharpercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'maxcharacterlimit');

        // Conditionally launch add field minmaxcharpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minwordlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minwordlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minmaxcharpercent');

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

        // Define field minmaxwordpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minmaxwordpercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'maxwordlimit');

        // Conditionally launch add field minmaxwordpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minsentencelimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minsentencelimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minmaxwordpercent');

        // Conditionally launch add field minsentencelimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxsentencelimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('maxsentencelimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minsentencelimit');

        // Conditionally launch add field maxsentencelimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minmaxsentpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minmaxsentpercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'maxsentencelimit');

        // Conditionally launch add field minmaxsentpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minparagraphlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minparagraphlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minmaxsentpercent');

        // Conditionally launch add field minparagraphlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxparagraphlimit to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('maxparagraphlimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'minparagraphlimit');

        // Conditionally launch add field maxparagraphlimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field minmaxparapercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('minmaxparapercent', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'maxparagraphlimit');

        // Conditionally launch add field minmaxparapercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field enableautorating to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('enableautorating', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'minmaxparapercent');

        // Conditionally launch add field enableautorating.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itemtype to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itemtype',
                                  XMLDB_TYPE_INTEGER,
                                  '4',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'enableautorating');

        // Conditionally launch add field itemtype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itemcount to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itemcount',
                                  XMLDB_TYPE_INTEGER,
                                  '6',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'itemtype');

        // Conditionally launch add field itemcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itempercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itempercent',
                                  XMLDB_TYPE_INTEGER,
                                  '6',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'itemcount');

        // Conditionally launch add field itempercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field showtextstats to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('showtextstats',
                                  XMLDB_TYPE_INTEGER,
                                  '2',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'itempercent');

        // Conditionally launch add field showtextstats.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field textstatitems to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('textstatitems',
                                  XMLDB_TYPE_CHAR,
                                  '255',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  null,
                                  'showtextstats');

        // Conditionally launch add field textstatitems.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorcmid to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorcmid',
                                  XMLDB_TYPE_INTEGER,
                                  '10',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'textstatitems');

        // Conditionally launch add field errorcmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorpercent to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorpercent',
                                  XMLDB_TYPE_INTEGER,
                                  '6',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'errorcmid');

        // Conditionally launch add field errorpercent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorfullmatch to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorfullmatch',
                                  XMLDB_TYPE_INTEGER,
                                  '1',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'errorpercent');

        // Conditionally launch add field errorfullmatch.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorcasesensitive to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorcasesensitive',
                                  XMLDB_TYPE_INTEGER,
                                  '1',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'errorfullmatch');

        // Conditionally launch add field errorcasesensitive.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field errorignorebreaks to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('errorignorebreaks',
                                  XMLDB_TYPE_INTEGER,
                                  '1',
                                  null,
                                  XMLDB_NOTNULL,
                                  null,
                                  '0',
                                  'errorcasesensitive');

        // Conditionally launch add field errorignorebreaks.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2022012200, 'diary');
    }
    // Three fields dropped for version 3.6.0.
    if ($oldversion < 2022090400) {

        // Define field itemtype to be dropped from diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('itemtype');
        // Conditionally launch drop field itemtype.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field itemcount to be dropped from diary.
        $field = new xmldb_field('itemcount');
        // Conditionally launch drop field itemcount.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field itempercent to be dropped from diary.
        $field = new xmldb_field('itempercent');
        // Conditionally launch drop field itempercent.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2022090400, 'diary');
    }
    // Version 3.7.0 was internal only, release.
    // New prompt table for version 3.7.1.
    if ($oldversion < 2022111400) {

        // Define table diary_prompts to be created.
        $table = new xmldb_table('diary_prompts');

        // Adding fields to table diary_prompts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('diaryid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('datestart', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('datestop', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('format', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('minchar', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('maxchar', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('minmaxcharpercent', XMLDB_TYPE_INTEGER, '6', null, null, null, '0');
        $table->add_field('minword', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('maxword', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('minmaxwordpercent', XMLDB_TYPE_INTEGER, '6', null, null, null, '0');
        $table->add_field('minsentence', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('maxsentence', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('minmaxsentencepercent', XMLDB_TYPE_INTEGER, '6', null, null, null, '0');
        $table->add_field('minparagraph', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('maxparagraph', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('minmaxparagraphpercent', XMLDB_TYPE_INTEGER, '6', null, null, null, '0');

        // Adding keys to table diary_prompts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('diaryid', XMLDB_KEY_FOREIGN, ['diaryid'], 'diary', ['id']);

        // Conditionally launch create table for diary_prompts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field promptid to be added to diary_entries.
        $table = new xmldb_table('diary_entries');
        $field = new xmldb_field('promptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'diary');

        // Conditionally launch add field promptid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2022111400, 'diary');
    }

    // New fields for mdl_diary table for version 3.7.2.
    if ($oldversion < 2023040200) {

        // Define field teacheremail to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('teacheremail', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enablestats');

        // Conditionally launch add field teacheremail.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field studentemail to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('studentemail', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'teacheremail');

        // Conditionally launch add field studentemail.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2023040200, 'diary');
    }

    // New fields for diary titles in version 3.7.7.
    if ($oldversion < 2023110900) {
        // Define field enabletitles to be added to diary.
        $table = new xmldb_table('diary');
        $field = new xmldb_field('enabletitles', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enablestats');

        // Conditionally launch add field enabletitles.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field title to be added to diary_entries.
        $table = new xmldb_table('diary_entries');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'timemodified');

        // Conditionally launch add field title.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Diary savepoint reached.
        upgrade_mod_savepoint(true, 2023110900, 'diary');
    }
    return true;
}
