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
        $context = context_module::instance($this->cm->id);
        // Trigger download_diary_entries event.
        $event = \mod_diary\event\download_diary_entries::create(array(
            'objectid' => $data->diary,
            'context' => $context
        ));
        $event->trigger();

        // Construct sql query and filename based on admin or teacher.
        // Add filename details based on course and HQ activity name.
        $csv = new csv_export_writer();
        $strdiary = get_string('diary', 'diary');
        if (is_siteadmin($USER->id)) {
            $whichhqs = ('AND hq.diary > 0');
            $csv->filename = clean_filename(get_string('exportfilenamep1', 'diary'));
        } else {
            $whichhqs = ('AND hq.diary = ');
            $whichhqs .= ($this->instance->id);
            $csv->filename = clean_filename(($this->course->shortname).'_');
            $csv->filename .= clean_filename(($this->instance->name));
        }
        $csv->filename .= clean_filename(get_string('exportfilenamep2', 'diary').gmdate("Ymd_Hi").'GMT.csv');

        $fields = array();

        $fields = array(get_string('firstname'),
                        get_string('lastname'),
                        get_string('userid', 'diary'),
                        get_string('diary', 'diary'),
                        get_string('question', 'diary'),
                        get_string('time', 'diary'),
                        get_string('anonymous', 'diary'),
                        get_string('teacherpriority', 'diary'),
                        get_string('heat', 'diary'),
                        get_string('approvedyes', 'diary'),
                        get_string('content', 'diary'));
        // Add the headings to our data array.
        $csv->add_data($fields);
        if ($CFG->dbtype == 'pgsql') {
            $sql = "SELECT hq.id AS question,
                    CASE
                        WHEN u.firstname = 'Guest user'
                        THEN u.lastname || 'Anonymous'
                        ELSE u.firstname
                    END AS firstname,
                        u.lastname AS lastname,
                        hq.diary AS diary,
                        hq.content AS content,
                        hq.userid AS userid,
                        to_char(to_timestamp(hq.time), 'YYYY-MM-DD HH24:MI:SS') AS time,
                        hq.anonymous AS anonymous,
                        hq.tpriority AS tpriority,
                        COUNT(hv.voter) AS heat,
                        hq.approved AS approved
                    FROM {diary_questions} hq
                    LEFT JOIN {diary_votes} hv ON hv.question=hq.id
                    JOIN {user} u ON u.id = hq.userid
                    WHERE hq.userid > 0 ";
        } else {
            $sql = "SELECT hq.id AS question,
                    CASE
                        WHEN u.firstname = 'Guest user'
                        THEN CONCAT(u.lastname, 'Anonymous')
                        ELSE u.firstname
                    END AS 'firstname',
                        u.lastname AS 'lastname',
                        hq.diary AS diary,
                        hq.content AS content,
                        hq.userid AS userid,
                        FROM_UNIXTIME(hq.time) AS TIME,
                        hq.anonymous AS anonymous,
                        hq.tpriority AS tpriority,
                        COUNT(hv.voter) AS heat,
                        hq.approved AS approved
                    FROM {diary_questions} hq
                    LEFT JOIN {diary_votes} hv ON hv.question=hq.id
                    JOIN {user} u ON u.id = hq.userid
                    WHERE hq.userid > 0 ";
        }

        $sql .= ($whichhqs);
        $sql .= "     GROUP BY u.lastname, u.firstname, hq.diary, hq.id
                      ORDER BY hq.diary ASC, hq.id ASC, tpriority DESC, heat";

        // Add the list of users and diarys to our data array.
        if ($hqs = $DB->get_records_sql($sql, $fields)) {
            foreach ($hqs as $q) {
                $output = array($q->firstname, $q->lastname, $q->userid, $q->diary, $q->question,
                    $q->time, $q->anonymous, $q->tpriority, $q->heat, $q->approved, $q->content);
                $csv->add_data($output);
            }
        }
        // Download the completed array.
        $csv->download_file();
        exit;
    }
}