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
defined('MOODLE_INTERNAL') || die(); // phpcs:ignore
use context_module;
use stdClass;

/**
 * A scheduled task for diary cron.
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
        global $CFG, $DB, $SITE, $USER;

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
                    $entriesmanager = has_capability('mod/diary:rate', $context, $user);

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
                    $posttext = "$course->shortname -> $modnamepl -> " . format_string($entry->name, true) . " and this is from cron_task.php file.\n";
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

        // 20250804 Start checking for new submission entries.
        $this->log("Feedback by the system. 
                                    Finished for Feedback.
                                    Starting for Submissions.
                                    \n");

        if ($entries = self::diary_get_ungraded_new_entry($cutofftime)) {
            // 20250323 Temporary log entry for development Entry count needing to be sent.
            $this->log_start("Processing Diary submission information. Count of entries is ".count($entries)."\n");

            // 20250323 Removed Moodle branch check.

            $usernamefields = \core_user\fields::for_name()->get_required_fields();
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
                    if (!empty($entry->userid)) {
                        $user = $entry->userid;
                    } else {
                        if (!$user = $DB->get_record("user", ["id" => $entry->userid], $requireduserfields)) {
                            $this->log_finish("Could not find user $entry->userid.\n");
                            continue;
                        }
                        $users[$entry->userid] = $user;
                    }
                    // At this point we have the new entries. Now need to get the diary pointed to by $entry->diary.
                    // Once we have the diary id, we need to get the course.

                    if (!empty($entry->course)) {
                        $course = $entry->course;
                        $courseinfo = $DB->get_record('course', ['id' => $course], 'id, shortname');

                    } else {
                        if (!$course = $DB->get_record('course', ['id' => $entry->course], 'id, shortname')) {
                            $this->log_finish("Could not find course $entry->course.\n");
                            continue;
                        }
                        $courses[$entry->course] = $course;
                    }

                    if ($enrolled = self::diary_get_course_enrolled_users($cutofftime)) {

                        foreach ($enrolled as $enrollee) {
                            // 20250730 If the teacher email preference is 1 they want notification NOW.
                            if (!empty($enrollee->user_enrolled)) {
                                $teacher = $enrollee->user_enrolled;
                                $emailpreference = get_user_preferences('diary_emailpreference_'.$entry->diary, $teacher);

                                $teacherinfo = $DB->get_record("user", ["id" => $teacher], $requireduserfields);

                                $userinfo = $DB->get_record("user", ["id" => $entry->userid], $requireduserfields);

                                if ((($entry->timemodified < $cutofftime2) && ($emailpreference == 2)) ||
                                    (($entry->timemodified < $cutofftime) && ($emailpreference == 1))) {

                                    // 20250730 Started trying to write the email message to be sent.
                                    // 20250803 Completedd message format Now to make it send it.
                                    $diaryinfo = new stdClass();
                                    $diaryinfo->name = format_string($entry->name, true);
                                    $diaryinfo->url = "$CFG->wwwroot/mod/diary/view.php?id=$entry->diary";
                                    $modnamepl = get_string('modulenameplural', 'diary');
                                    // 20250323 Note that when this is done, $message will contain plain text and HTML version of the message.
                                    $message = new \core\message\message();
                                    $message->component = 'mod_diary'; // Diary plugin's name.
                                    $message->name = 'diary_entry_notification'; // The notification name from message.php

                                    // Need to get first and last name of the entry author.
                                    $message->userfrom = $userinfo->firstname . ' ' . $userinfo->lastname; // The message is 'from' a specific user and it is set here.
                                    $message->userto = $teacherinfo->firstname . ' ' . $teacherinfo->lastname; // The message is 'to' a specific teacher and it is set here.
//var_dump($diaryinfo);
                                    // Needs the whole line changed to a string.
                                    $message->subject = $userinfo->firstname . ' ' . $userinfo->lastname." has posted a diary entry in course '$courseinfo->shortname'";
                                    $message->fullmessage = 'Hi '. $teacherinfo->firstname . ' ' . $teacherinfo->lastname . ', \n';
                                    $message->fullmessage .= "$courseinfo->shortname -> $modnamepl -> ".format_string($diaryinfo->name, true)."\n";
                                    $message->fullmessage .= "---------------------------------------------------------------------\n";
                                    $message->fullmessage .= $userinfo->firstname . ' ' . $userinfo->lastname . ' ' .get_string("diarymailuser", "diary", $diaryinfo)."\n";
                                    $message->fullmessage .= "---------------------------------------------------------------------\n";
                                    $message->fullmessageformat = FORMAT_MARKDOWN;
                                    // Hardcoded text needs to be converted to strings, like the two already done.
                                    $message->fullmessagehtml = "<p><font face=\"sans-serif\">".
                                    "Hi there $teacherinfo->firstname $teacherinfo->lastname,<br>".
                                    "<p>Site user ".$userinfo->firstname . ' ' . $userinfo->lastname.'&nbsp;'.get_string("diarymailhtmluser", "diary", $diaryinfo)."</p>".
                                    "<p>The ".$SITE->shortname." Team</p>".
                                    "<br /><hr /><font face=\"sans-serif\">".
                                    "<p>".get_string("additionallinks", "diary")."</p>".
                                    "<a href=\"$CFG->wwwroot/course/view.php?id=$courseinfo->id\">$courseinfo->shortname</a> ->".
                                    "<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$courseinfo->id\">diarys</a> ->".
//                                    "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$cm->id\">".format_string($diaryinfo->name, true).
                                    "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$entry->diary\">".format_string($diaryinfo->name, true).
                                    "</a></font></p>".
                                    "</font><hr />";
                                    //$message->smallmessage = 'small message';
                                    $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
                                    $message->contexturl = (new \moodle_url('/course/'))->out(false); // A relevant URL for the notification
                                    $message->contexturlname = 'Course list'; // Link title explaining where users get to for the contexturl
                                    // Extra content for specific processor
                                    $content = [
                                    '*' => [
                                    'header' => '<p>The '.$SITE->fullname.' Team</p>',
                                    'footer' => '<p>The '.$SITE->fullname.' Team</p>',
                                    ],
                                ];
                                $message->set_additional_content('email', $content);

                                  // Actually send the message
                                $messageid = message_send($message);

                                $this->log("Notification of diary entry $entry->id, submitted by student $entry->userid, was emailed to teacher id $teacher.\n");
                                }
                            }
                        }
                    }
            }

        //$string['needsgrading'] = ' This entry has not been given feedback or rated yet.';
        //$string['needsregrade'] = 'This entry has changed since feedback or a rating was given.';
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

    /**
     * Return entries that have not been emailed.
     *
     * @param int $cutofftime
     * @return object
     */
    protected function diary_get_ungraded_new_entry($cutofftime) {
        global $DB;

        $sql = "SELECT d.id,
                       d.course,
                       d.name,
                       d.submissionemail,
                       de.*
                  FROM {diary} d
                  JOIN {diary_entries} de ON d.id = de.diary
                  JOIN {user} as u ON de.userid = u.id
                 WHERE d.submissionemail = 1
                   AND de.entrynoticemailed = 0
                   AND de.timemodified > 0";
        return $DB->get_records_sql($sql, [$cutofftime]);
    }
    /**
     * Return course enrolled users that teachers.
     *
     * @param int $cutofftime
     * @return object
     */
    protected function diary_get_course_enrolled_users($cutofftime) {
        global $DB;
        // 20250730 Tried adding u.id but still getting error due to duplicate entries being found.
        // In the future, need to see if that can be fixed.
        // 20250716 This sql seems to work and is returning the teachers enrolled in the course.
        // Created ad-hoc query and I am getting the teachers for each of the diary entries.
        // 20260114 This sql needs to be mofified to get the Diary Entries FIRST, that will fix
        // errors generated by, Starting for Submissions, Processing Diary submission information.
        // Count of entries is xx, errors listed in the log entries.
        // Todays test shows Count of entries = 41, but the process is taking 452 reads and 0 writes!
        $sql = "SELECT u.id,
                      ue.userid AS User_Enrolled,
                       r.shortname AS Role,
                      de.id AS Entry_ID,
                       d.course AS diary_course,
                       d.id AS diary_id,
                       c.id AS course_id,
                       d.name AS diary_name,
                       d.submissionemail,
                       de.entrynoticemailed
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} en ON ue.enrolid = en.id
                  JOIN {role_assignments} ra ON u.id = ra.userid
                  JOIN {role} r ON ra.roleid = r.id
                   AND (r.shortname = 'editingteacher'
                    OR r.shortname = 'teacher')
                  JOIN {context} cx ON cx.id = ra.contextid
                   AND cx.contextlevel = 50
                  JOIN {course} c ON c.id = cx.instanceid
                   AND en.courseid = c.id
                  JOIN {diary} d ON c.id = d.course
                  JOIN {diary_entries} de ON d.id = de.diary
                 WHERE d.submissionemail = 1
                   AND de.entrynoticemailed = 0
                   AND de.timemodified > 0
                   AND (r.shortname ='editingteacher'
                    OR r.shortname ='teacher')";

        return $DB->get_records_sql($sql, [$cutofftime]);
    }
}
