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
 * Internal library of functions for module Diary.
 *
 * All the diary specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod_diary
 * @copyright 2019 onwards AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Standard base class for mod_diary.
 *
 * @package   mod_diary
 * @copyright 2019 onwards AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_diary {

    /** @var int callback arg - the instance of the current diary activity */
    public $instance;
    /** @var int callback arg - the instance of the current diary module */
    public $cm;
    /** @var int callback arg - the instance of the current diary course */
    public $course;

    /** @var int callback arg - the id of current round of entries */
    protected $currententry;
    /** @var int callback arg - the id of previous round of entries */
    protected $preventry;
    /** @var int callback arg - the id of next round of entries */
    protected $nextentry;

    /**
     * Constructor for the base diary class.
     *
     * Note: For $coursemodule you can supply a stdclass if you like, but it
     * will be more efficient to supply a cm_info object.
     *
     * @param mixed $cmid
     * @param mixed $entryid
     */
    public function __construct($cmid, $entryid = -1) {
        global $DB;
        $this->cm        = get_coursemodule_from_id('diary', $cmid, 0, false, MUST_EXIST);
        $this->course    = $DB->get_record('course', array('id' => $this->cm->course), '*', MUST_EXIST);
        $this->instance  = $DB->get_record('diary', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $this->set_currententry($entriesid);
    }



    /**
     * Open a new round and close the old one.
     */
    public function add_new_round() {
        global $USER, $CFG, $DB;

        // Close the latest round.
        $entries = $DB->get_records('diary_entries', array('diary' => $this->instance->id), 'id DESC', '*', 0, 1);
        $old = array_pop($entries);
        $old->endtime = time();
        $DB->update_record('diary_entries', $old);

        // Open a new round.
        $new = new StdClass();
        $new->diary = $this->instance->id;
        $new->starttime = time();
        $new->endtime = 0;
        $context = context_module::instance($this->cm->id);
        $rid = $DB->insert_record('diary_entries', $new);

        if ($CFG->version > 2014051200) { // Moodle 2.7+.
            $params = array(
                'objectid' => $this->cm->id,
                'context' => $context,
            );

            $event = \mod_diary\event\add_round::create($params);
            $event->trigger();
        } else {
            add_to_log($this->course->id, 'diary', 'add round',
                "view.php?id={$this->cm->id}&round=$rid", $rid, $this->cm->id);
        }
    }

    /**
     * Set current entry to show.
     * @param int $entryid
     */
    public function set_currententry($entryid = -1) {
        global $DB;

        $entries = $DB->get_records('diary_entries', array('diary' => $this->instance->id), 'id ASC');
        if (empty($entries)) {
            // Create the first round.
            $round = new StdClass();
            $round->starttime = time();
            $round->endtime = 0;
            $round->diary = $this->instance->id;
            //$round->id = $DB->insert_record('diary_entries', $round);
            $entries[] = $round;
        }

        if ($entryid != -1 && array_key_exists($entryid, $entries)) {
            $this->currententry = $entries[$entryid];

            $ids = array_keys($entries);
            // Search previous round.
            $currentkey = array_search($entryid, $ids);
            if (array_key_exists($currentkey - 1, $ids)) {
                $this->preventry = $entries[$ids[$currentkey - 1]];
            } else {
                $this->preventry = null;
            }
            // Search next round.
            if (array_key_exists($currentkey + 1, $ids)) {
                $this->nextentry = $entries[$ids[$currentkey + 1]];
            } else {
                $this->nextentry = null;
            }
        } else {
            // Use the last round.
            $this->currententry = array_pop($entries);
            $this->preventry = array_pop($entries);
            $this->nextentry = null;
        }
        return $entryid;
    }

    /**
     * Return current round.
     *
     * @return object
     */
    public function get_currententry() {
        return $this->currententry;
    }

    /**
     * Return previous round.
     *
     * @return object
     */
    public function get_preventry() {
        return $this->preventry;
    }

    /**
     * Return next round.
     *
     * @return object
     */
    public function get_nextentry() {
        return $this->nextentry;
    }

    /**
     * Return entries according to $currententry.
     *
     * Sort order is priority descending, votecount descending,
     * and time descending from most recent to oldest.
     * @return all entries with vote count in current round.
     */
    public function get_entries() {
        global $DB;
        if ($this->currententry->endtime == 0) {
            $this->currententry->endtime = 0xFFFFFFFF;  // Hack.
        }
        $params = array($this->instance->id, $this->currententry->starttime, $this->currententry->endtime);
        return $DB->get_records_sql('SELECT q.*, count(v.voter) as votecount
                                     FROM {diary_entries} q
                                         LEFT JOIN {diary_votes} v
                                         ON v.question = q.id
                                     WHERE q.diary = ?
                                        AND q.time >= ?
                                        AND q.time <= ?
                                     GROUP BY q.id
                                     ORDER BY tpriority DESC, votecount DESC, q.time DESC', $params);
    }

    /**
     * Remove selected question and any votes that it might have.
     *
     * @return object
     */
    public function remove_question() {
        global $DB;

        $data = new StdClass();
        $data->diary = $this->instance->id;
        $context = context_module::instance($this->cm->id);
        // Trigger remove_round event.
        $event = \mod_diary\event\remove_question::create(array(
            'objectid' => $data->diary,
            'context' => $context
        ));
        $event->trigger();
        if (isset($_GET['q'])) {
            $questionid = $_GET['q'];
            $dbquestion = $DB->get_record('diary_entries', array('id' => $questionid));
            $DB->delete_records('diary_entries', array('id' => $dbquestion->id));
            // Get an array of all votes on the question that was just deleted, then delete them.
            $dbvote = $DB->get_records('diary_votes', array('question' => $questionid));
            $DB->delete_records('diary_votes', array('question' => $dbquestion->id));
        }
        return $this->currententry;
    }

    /**
     * If the currently being viewed round is empty, delete it.
     * Otherwise, remove any entries in the round currently being viewed,
     * remove any votes for each question being removed,
     * then remove the currently being viewed round.
     * @return nothing
     */
    public function remove_round() {
        global $DB;

        $data = new StdClass();
        $data->diary = $this->instance->id;
        $context = context_module::instance($this->cm->id);
        // Trigger remove_round event.
        $event = \mod_diary\event\remove_round::create(array(
            'objectid' => $data->diary,
            'context' => $context
        ));
        $event->trigger();

        $entryid = $_GET['round'];
        if ($this->currententry->endtime == 0) {
            $this->currententry->endtime = 0xFFFFFFFF;  // Hack.
        }
        $params = array($this->instance->id, $this->currententry->starttime, $this->currententry->endtime);
        $entries = $DB->get_records_sql('SELECT q.*, count(v.voter) as votecount
                                     FROM {diary_entries} q
                                         LEFT JOIN {diary_votes} v
                                         ON v.question = q.id
                                     WHERE q.diary = ?
                                         AND q.time >= ?
                                         AND q.time <= ?
                                     GROUP BY q.id
                                     ORDER BY votecount DESC, q.time DESC', $params);

        if ($entries) {
            foreach ($entries as $q) {
                $questionid = $q->id; // Get id of first question on the page to delete.
                $dbquestion = $DB->get_record('diary_entries', array('id' => $questionid));
                $DB->delete_records('diary_entries', array('id' => $dbquestion->id));
                // Get an array of all votes on the question that was just deleted, then delete them.
                $dbvote = $DB->get_records('diary_votes', array('question' => $questionid));
                $DB->delete_records('diary_votes', array('question' => $dbquestion->id));
            }
            // Now that all entries and votes are gone, remove the round.
            $dbround = $DB->get_record('diary_entries', array('id' => $entryid));
            $DB->delete_records('diary_entries', array('id' => $dbround->id));
        } else {
            // This round is empty so delete without having to remove entries and votes.
            $dbround = $DB->get_record('diary_entries', array('id' => $entryid));
            $DB->delete_records('diary_entries', array('id' => $dbround->id));
        }
        // Now we need to see if we need a new round or have one we can still use.
        $entries = $DB->get_records('diary_entries', array('diary' => $this->instance->id), 'id DESC');

        foreach ($entries as $rnd) {
            if ($rnd->endtime == 0) {
                // Deleted a closed round so just return.
                return;
            } else {
                // Deleted our open round so create a new round.
                $round = new StdClass();
                $round->starttime = time();
                $round->endtime = 0;
                $round->diary = $this->instance->id;
                $round->id = $DB->insert_record('diary_entries', $round);
                $entries[] = $round;
                return;
            }
        }
        return $this->currententry;
    }

    /**
     * Download entries.
     * @param array $array
     * @param string $filename - The filename to use.
     * @param string $delimiter - The character to use as a delimiter.
     * @return nothing
     */
    public function download_entries($array, $filename = "export.csv", $delimiter=";") {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/csvlib.class.php');
        $data = new StdClass();
        $data->diary = $this->instance->id;
        $context = context_module::instance($this->cm->id);
        // Trigger download_entries event.
        $event = \mod_diary\event\download_entries::create(array(
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
                        get_string('id', 'diary'),
                        get_string('diary', 'diary'),
                        get_string('content', 'diary'),
                        get_string('userid', 'diary'),
                        get_string('time', 'diary'),
                        get_string('anonymous', 'diary'),
                        get_string('approvedyes', 'diary'));
        // Add the headings to our data array.
        $csv->add_data($fields);

        $sql = "SELECT hq.id id,
                CASE
                    WHEN u.firstname = 'Guest user'
                    THEN CONCAT(u.lastname, 'Anonymous')
                    ELSE u.firstname
                END AS 'firstname',
                        u.lastname AS 'lastname', hq.diary diary, hq.content content, hq.userid userid,
                FROM_UNIXTIME(hq.time) AS TIME, hq.anonymous anonymous, hq.approved approved
                FROM {diary_entries} hq
                JOIN {user} u ON u.id = hq.userid
                WHERE hq.userid > 0 ";
        $sql .= ($whichhqs);
        $sql .= " ORDER BY hq.diary, u.id";

        // Add the list of users and HotQuestions to our data array.
        if ($hqs = $DB->get_records_sql($sql, $fields)) {
            foreach ($hqs as $q) {
                $output = array($q->firstname, $q->lastname, $q->id, $q->diary,
                    $q->content, $q->userid, $q->time, $q->anonymous, $q->approved);
                $csv->add_data($output);
            }
        }
        // Download the completed array.
        $csv->download_file();
        exit;
    }

    /**
     * Toggle approval go/stop of current question in current round.
     *
     * @param var $question
     * @return nothing
     */
    public function approve_question($question) {
        global $CFG, $DB, $USER;
        $context = context_module::instance($this->cm->id);
        $question = $DB->get_record('diary_entries', array('id' => $question));

        if ($question->approved) {
            // If currently approved, toggle to disapproved.
            $question->approved = '0';
            $DB->update_record('diary_entries', $question);
        } else {
            // If currently disapproved, toggle to approved.
            $question->approved = '1';
            $DB->update_record('diary_entries', $question);
        }
        return;
    }

    /**
     * Set teacher priority of current question in current round.
     *
     * @param int $u the priority up or down flag.
     * @param int $question the question id
     */
    public function tpriority_change($u, $question) {
        global $CFG, $DB, $USER;

        $context = context_module::instance($this->cm->id);
        $question = $DB->get_record('diary_entries', array('id' => $question));

        if ($u) {
            // If priority flag is 1, increase priority by 1.
            $question->tpriority = ++$question->tpriority;
            $DB->update_record('diary_entries', $question);
        } else {
            // If priority flag is 0, decrease priority by 1.
            $question->tpriority = --$question->tpriority;
            $DB->update_record('diary_entries', $question);
        }
    }

}

/**
 * Return the toolbar.
 *
 * @param bool $shownew whether show "New round" button
 * return alist of links
 */
function xxtoolbar($course, $user, $entry) {
    global $USER, $DB, $THEME, $CFG;

    $output = '';
    $toolbuttons = array();
    $entryp = new stdClass();
    $entryc = ''; // Date currently looking at.
    $entryn = ''; // Date next after entryc.
    $entryp = ''; // Date previous to entryc.

    //if ($entry->get_preventry() != null) {

        if (! empty($entry->timecreated)) {
            $entryc = $entry->timecreated;
            $output .= $entryc;
            $output .= ' this is the entry time created';
        }
   // }

        // Print entry toolbuttons entryp/entryn.
        // When here first there is a entryp but not a entryn.
        // When go to entryp, then there is a entryn.

// entryp - Need an sql where the diary_entries id and timecreated are lower than current id and the diary and user are the same as current diary and user.
// entryn - Need an sql where the diary_entries id and timecreated are higher than current id and the diary and user are the same as current diary and user.

//print_object('theme: '.$CFG->theme);

        // Print prev/next round toolbuttons.




    return $output;
}




    /**
     * Returns availability status.
     * Added 10/2/16.
     * @param var $diary
     */
function hq_available($diary) {
    $timeopen = $diary->timeopen;
    $timeclose = $diary->timeclose;
    return (($timeopen == 0 || time() >= $timeopen) && ($timeclose == 0 || time() < $timeclose));
}


