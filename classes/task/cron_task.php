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

namespace mod_diary\task;
defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine
use context_module;
use stdClass;

/**
 * A schedule task for diary cron.
 *
 * @package   mod_diary
 * @copyright 2021 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_diary');
    }

    /**
     * Run diary cron.
     */
    public function execute() {
        global $CFG, $USER, $DB;

        $cutofftime = time();
        $cutofftime2 = time() - $CFG->maxeditingtime;

        $this->log_start("Processing Diary information.");

        if ($entries = self::diary_get_unmailed_graded($cutofftime)) {
            // 20210611 Added Moodle branch check.
            if ($CFG->branch < 311) {
                // Get an array of the fields used for site user names.
                $usernamefields = get_all_user_name_fields();
            } else {
                $usernamefields = \core_user\fields::for_name()->get_required_fields();
            }
            // 20220110 Deleted duplicate and moved here out of the if.
            $requireduserfields = 'id,
                                   auth,
                                   mnethostid,
                                   username,
                                   email,
                                   mailformat,
                                   maildisplay,
                                   lang,
                                   deleted,
                                   suspended,
                                   '.implode(', ', $usernamefields);
            // To save some db queries.
            $users = [];
            $courses = [];

            foreach ($entries as $entry) {
                // 20230401 Added working email preference.
                $emailpreference = get_user_preferences('diary_emailpreference_'.$entry->diary, null, $entry->teacher);
                if ((($entry->timemarked < $cutofftime2) && ($emailpreference == 2)) ||
                    (($entry->timemarked < $cutofftime) && ($emailpreference == 1))) {

                    $this->log_start("Processing diary entry $entry->id.\n");

                    if (!empty($users[$entry->userid])) {
                        $user = $users[$entry->userid];
                    } else {
                        if (!$user = $DB->get_record("user", ["id" => $entry->userid], $requireduserfields)) {
                            $this->log_finish("Could not find user $entry->userid.\n");
                            continue;
                        }
                        $users[$entry->userid] = $user;
                    }

                    $USER->lang = $user->lang;

                    if (!empty($courses[$entry->course])) {
                        $course = $courses[$entry->course];
                    } else {
                        if (!$course = $DB->get_record('course', ['id' => $entry->course], 'id, shortname')) {
                            $this->log_finish("Could not find course $entry->course.\n");
                            continue;
                        }
                        $courses[$entry->course] = $course;
                    }

                    if (!empty($users[$entry->teacher])) {
                        $teacher = $users[$entry->teacher];
                    } else {
                        if (!$teacher = $DB->get_record("user", ["id" => $entry->teacher], $requireduserfields)) {
                            $this->log_finish("Could not find teacher $entry->teacher.\n");
                            continue;
                        }
                        $users[$entry->teacher] = $teacher;
                    }

                    // All cached.
                    $coursediarys = get_fast_modinfo($course)->get_instances_of('diary');
                    if (empty($coursediarys) || empty($coursediarys[$entry->diary])) {
                        $this->log_finish("Could not find course module for diary id $entry->diary.\n");
                        continue;
                    }
                    $mod = $coursediarys[$entry->diary];

                    // This is already cached internally.
                    $context = context_module::instance($mod->id);
                    $canadd = has_capability('mod/diary:addentries', $context, $user);
                    $entriesmanager = has_capability('mod/diary:manageentries', $context, $user);

                    if (!$canadd && $entriesmanager) {
                        continue; // Not an active participant. Cannot add entries, but can manage them.
                    }

                    $diaryinfo = new stdClass();
                    // 20200829 Added users first and last name to message.
                    $diaryinfo->user = $user->firstname . ' ' . $user->lastname;
                    $diaryinfo->teacher = fullname($teacher);
                    $diaryinfo->diary = format_string($entry->name, true);
                    $diaryinfo->url = "$CFG->wwwroot/mod/diary/view.php?id=$mod->id";
                    $modnamepl = get_string('modulenameplural', 'diary');
                    $msubject = get_string('mailsubject', 'diary');

                    $postsubject = "$course->shortname: $msubject: " . format_string($entry->name, true);
                    $posttext = "$course->shortname -> $modnamepl -> " . format_string($entry->name, true) . "\n";
                    $posttext .= "---------------------------------------------------------------------\n";
                    $posttext .= get_string("diarymail", "diary", $diaryinfo) . "\n";
                    $posttext .= "---------------------------------------------------------------------\n";
                    if ($user->mailformat == 1) { // HTML.
                        $posthtml = "<hr /><font face=\"sans-serif\">";
                        $posthtml .= "<p>".get_string("diarymailhtml", "diary", $diaryinfo)."</p>";
                        $posthtml .= "</font><hr />";
                        $posthtml .= "<p>".get_string("additionallinks", "diary")."</p>";
                        $posthtml .= "<p><font face=\"sans-serif\">"
                            ."<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->"
                            ."<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$course->id\">diarys</a> ->"
                            ."<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$mod->id\">"
                            .format_string($entry->name, true)
                            ."</a></font></p>";
                    } else {
                        $posthtml = "";
                    }
                    // Send feedback email, otherwise, log that it could not be sent.
                    if (!email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                        $this->log_finish("Error: Diary cron: Could not send out mail for id
                            $entry->id to user $user->id ($user->email).\n");
                    }
                    // Add log entry that a feedback email was sent to a specified user regarding their particular entry.
                    if (!$DB->set_field("diary_entries", "mailed", "1", ["id" => $entry->id])) {
                        $this->log_finish("Could not update the mailed field for id $entry->id.\n");
                    } else {
                        // 20230331 Changed log to be only for branch 37 and above.
                        $this->log("Feedback by teacher $entry->teacher
                                    was emailed to user id $user->id
                                    regarding diary entry id $entry->id.
                                    \n");
                    }
                }
            }
        }

        // 20230331 Changed log to be only for branch 37 and above.
        $this->log_finish("Processing Diary cron is completed.");
        return true;
    }

    /**
     * Return entries that have not been emailed.
     *
     * @param int $cutofftime
     * @return object
     */
    protected function diary_get_unmailed_graded($cutofftime) {
        global $DB;

        $sql = "SELECT de.*, d.course, d.name
                  FROM {diary_entries} de
                  JOIN {diary} d ON de.diary = d.id
                 WHERE de.mailed = '0'
                   AND de.timemarked < ?
                   AND de.timemarked > 0";

        return $DB->get_records_sql($sql, [$cutofftime]);
    }
}
