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

        $timenow = time();
        $dateformat = get_config('mod_diary', 'dateformat') ?: "l, F j, Y H:i:s";
        $edittimeago = $timenow - $CFG->maxeditingtime;

        $this->log_start("Starting Diary Processing.");

        // 1. Fetch entries that need mailing.
        $entries = self::diary_get_unmailed_submissionemail_notification($edittimeago);

        if (!$entries) {
            $this->log("No pending entries to mail. Time is " .
                date("l, F j, Y H:i:s", $timenow) . " and the edittimeago is: " .
                date("l, F j, Y H:i:s", $edittimeago) . ".");

            return true;
        }

        // 2. Prepare User Fields (Branch check).
        $userfields = ($CFG->branch < 311)
            ? get_all_user_name_fields()
            : \core_user\fields::for_name()->get_required_fields();
        $requiredfields = 'id,auth,mnethostid,username,email,lang,deleted,suspended,' . implode(',', $userfields);
        $users = []; // Cache for performance.

        foreach ($entries as $entry) {
            try {
                $this->log(" - Processing entry: " . ($entry->id) .
                    " belonging to ID: " . ($entry->userid) .
                    " and the mailed status is: " . ($entry->mailed));

                // Skip if already mailed (safety check).
                if ($entry->mailed != 0 || $entry->timemodified >= $edittimeago) {
                    continue;
                }

                // 3. Robust User Fetching.
                if (!isset($users[$entry->userid])) {
                    $student = $DB->get_record("user", ["id" => $entry->userid], $requiredfields);
                    if (!$student) {
                        throw new \moodle_exception('invaliduserid', 'mod_diary', '', $entry->userid);
                    }
                    $users[$entry->userid] = $student;
                }
                $student = $users[$entry->userid];

                // 4. 20260204 Rewritten based on Grok recommendation - Context & Teacher Retrieval.
                // Using the module context is more precise than course context.
                $context = \context_module::instance($entry->cmid);
                $teachers = get_enrolled_users($context, 'mod/diary:rate');

                // 20260204 Changed on  Grok recommendation.
                if (!$course = $DB->get_record("course", ["id" => $entry->course])) {
                    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
                }

                if (!$teachers) {
                    $this->log("No teachers found for diary entry {$entry->id}. Skipping email.");
                    // Mark as mailed anyway so it doesn't stay stuck in the queue?
                    // Or leave it? Usually, we mark it so cron doesn't check it forever.
                    $DB->set_field('diary_entries', 'mailed', 1, ['id' => $entry->id]);
                    continue;
                }

                foreach ($teachers as $teacher) {
                    // Log who we are checking.
                    $this->log(" - Checking teacher: " . fullname($teacher) . " (ID: {$teacher->id})");
                    // Check if the Diary instance has email notifications enabled.
                    if (empty($entry->submissionemail)) {
                        $this->log("   - SKIPPED: This diary instance has submission emails disabled." . $entry->submissionemail);
                        continue; // Proceed with next diary.
                    }
                    if (!empty($entry->teacheremail)) {
                        $this->log("   - SKIPPED: This diary instance has teacher emails disabled." . $entry->teacheremail);
                        continue; // Proceed with next teacher.
                    }

                    // Message Construction.
                    $diaryinfo = new \stdClass();
                    $diaryinfo->diary = format_string($entry->diaryname); // Assumes diaryname is in SQL.
                    $diaryinfo->url = (new \moodle_url('/mod/diary/reportone.php', [
                        'id' => $entry->cmid,
                        'user' => $student->id,
                        'action' => 'currententry',
                        'entryid' => $entry->id,
                    ]))->out(false);

                    // 20260203 Added the entry created/modified time.
                    $diaryinfo->timecreated = date("l, F j, Y H:i:s", $entry->timecreated);
                    if ($entry->timemodified) {
                        $diaryinfo->timemodified = date("l, F j, Y H:i:s", $entry->timemodified);
                    } else {
                        $diaryinfo->timemodified = date("l, F j, Y H:i:s", $entry->timecreated);
                    }

                    $modnamesngl = get_string('modulename', 'diary');
                    $modnamepl = get_string('modulenameplural', 'diary');

                    $message = new \core\message\message();
                    $message->courseid          = $course->id; // ID of this course.
                    $message->modulename = $modnamesngl; // Name of this plugin.
                    $message->component         = 'mod_diary';
                    $message->name              = 'diary_entry_notification';
                    $message->userfrom          = $student;
                    $message->userto            = $teacher;
                    $message->subject           = fullname($student) .
                        " submitted a diary entry in course '$course->shortname' using the edit.php file.";
                    $message->fullmessage       = "Hi,\n\n" . fullname($student) .
                        " has submitted a new entry. \nView: {$diaryinfo->url}";
                    $message->fullmessage      .= "$course->shortname -> $modnamepl -> " .
                        format_string($diaryinfo->diary, true) . "\n";

                    $message->fullmessageformat = FORMAT_MARKDOWN;
                    $message->fullmessagehtml = "<p><font face=\"sans-serif\">" .
                        get_string("messagegreeting", "diary") . "$teacher->firstname $teacher->lastname,</p>" .
                        "<p>" . fullname($student) . '&nbsp;' . get_string("diarymailhtmluser", "diary", $diaryinfo) . "</p>" .
                        "<p>The " . $SITE->shortname . " Team</p>" .
                        "<br /><hr /><font face=\"sans-serif\">" .
                        "<p>" . get_string("additionallinks", "diary") . "</p>" .
                        "<a href=\"$CFG->wwwroot/course/view.php?id={$course->id}\">{$course->shortname}</a> → " .
                        "<a href=\"$CFG->wwwroot/mod/diary/index.php?id={$course->id}\">Diaries</a> → " .
                        "<a href=\"$CFG->wwwroot/mod/diary/view.php?id={$entry->cmid}\">" . format_string($entry->diaryname, true) .
                        "</a></font>" .
                        "</font><hr />";
                    $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message.
                    $message->contexturl = (new \moodle_url('/course/'))->out(false); // A relevant URL for the notification.
                    $message->contexturlname = 'Course list'; // Link title explaining where users get to for the contexturl.

                    // Extra content for specific processor.
                    // 20260116 Added date and time using format defined by mod_diary configuration settings.
                    $content = [
                        '*' => [
                            'header' => '<p>The ' . $SITE->fullname . ' Team, ' . date("$dateformat") . '.</p>',
                            'footer' => '<p>The ' . $SITE->fullname . ' Team, ' . date("$dateformat") . '.</p>',
                        ],
                    ];
                    $message->set_additional_content('email', $content);

                    // 3. Log before sending
                    $this->log("   - PREPARING: Sending email to " . $teacher->email);

                    try {
                        $messageid = message_send($message);
                        $this->log("   - SUCCESS: Message sent with ID: " . $messageid);
                    } catch (\Exception $e) {
                        $this->log("   - ERROR: message_send failed: " . $e->getMessage());
                    }
                }

                // 6. Update Database
                $DB->set_field('diary_entries', 'mailed', 1, ['id' => $entry->id]);
            } catch (\Exception $e) {
                // If one entry fails, log it and keep going!
                $this->log("Error processing entry {$entry->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->log_finish("Diary processing completed.");
        return true;
    }

    /**
     * Return entries with submissionemail notices that have not been emailed.
     *
     * @param int $edittimeago
     * @return object
     */
    protected function diary_get_unmailed_submissionemail_notification($edittimeago) {
        global $DB;

        $sql = "SELECT de.*,
                       d.course,
                       d.id AS diary,
                       d.name AS diaryname,
                       d.submissionemail,
                       d.teacheremail,
                       d.studentemail,
                       cm.id AS cmid
                  FROM {diary_entries} de
                  JOIN {diary} d ON de.diary = d.id
                  JOIN {modules} m ON m.name = 'diary'
                  JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = d.id
                 WHERE de.mailed = 0
                   AND d.submissionemail = 1
                  AND d.teacheremail = 0
                   AND de.timemodified < :edittimeago";

        return $DB->get_records_sql($sql, ['edittimeago' => $edittimeago]);
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
                       d.teacheremail,
                       de.*
                  FROM {diary} d
                  JOIN {diary_entries} de ON d.id = de.diary
                  JOIN {user} u ON de.userid = u.id
                 WHERE d.submissionemail = 1
                   AND d.teacheremail = 0
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
