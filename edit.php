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
 * This page opens the current instance of a diary entry for editing.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 *            Thanks to Stephen Wallace regarding instant notifications to teachers.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;
use mod_diary\local\diarystats;
use mod_diary\local\prompts;
use mod_diary\event\invalid_access_attempt;

require_once("../../config.php");
require_once('locallib.php'); // May not need this.
require_once('./edit_form.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT); // Action(default to current entry).
$firstkey = optional_param('firstkey', '', PARAM_INT); // Which diary_entries id to edit.
$promptid = optional_param('promptid', '', PARAM_INT); // The current one.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (!$course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// 20260116 Get the date and time configuration from settings for later use.
$dateformat = get_config('mod_diary', 'dateformat');

// 20221107 The $diary->intro gets overwritten by the current prompt and Notes, so keep a copy for later down in this file.
$tempintro = $diary->intro;

// 20240413 Check the promptid delivered in the URL. DO NOT get current if editing entry already has one.
// 20240507 Added for testing and it appears to work for existing entry without a prompt.
if (!($promptid > 0) && ($diary->timeopen < time())) {
    // Need to call a prompt function that returns the current promptid, if there is one that is current.
    $promptid = prompts::get_current_promptid($diary);
}

// 20210817 Add min/max info to the description so user can see them while editing an entry.
// 20240414 This also adds the prompt text to the $diary->intro.
diarystats::get_minmaxes($diary, $action, $promptid);

// 20210613 Added check to prevent direct access to create new entry when activity is closed.
if (($diary->timeclose) && (time() > $diary->timeclose)) {
    // Trigger invalid_access_attempt with redirect to the view page.
    $params = [
        'objectid' => $id,
        'context' => $context,
        'other' => [
            'file' => 'edit.php',
        ],
    ];
    $event = invalid_access_attempt::create($params);
    $event->trigger();
    redirect('view.php?id='.$id, get_string('invalidaccessexp', 'diary'));
}

// Header.
$PAGE->set_url('/mod/diary/edit.php', ['id' => $id]);
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();

$parameters = [
    'userid' => $USER->id,
    'diary' => $diary->id,
    'action' => $action,
    'firstkey' => $firstkey,
];

// Get the single record specified by firstkey.
$entry = $DB->get_record('diary_entries',
    [
        'userid' => $USER->id,
        'id' => $firstkey,
    ]
);

// 20230306 Added code that lists the tags on the edit_form page.
$data->tags = core_tag_tag::get_item_tags_array('mod_diary', 'diary_entries', $firstkey);

if ($action == 'currententry' && $entry) {
    $data->entryid = $entry->id;
    // 20240426 Trying to add the promptid here.
    // 20240426 This lcation works for old promptid's but not for entries with NO promptid assigned. i.e. Does not work for 0.
    $data->promptid = $entry->promptid;
    $data->timecreated = $entry->timecreated;
    $data->title = $entry->title;
    $data->text = $entry->text;
    $data->textformat = $entry->format;

    // Check the timecreated of the current entry to see if now is a new calendar day .
    // 20210425 If can edit dates, just start a new entry.
    if ((strtotime('today midnight') > $entry->timecreated) || ($action == 'currententry' && $diary->editdates)) {
        $entry = '';
        $data->entryid = null;
        $data->timecreated = time();
        $data->title = '';
        $data->text = '';
        $data->textformat = FORMAT_HTML;
    }
} else if ($action == 'editentry' && $entry) {
    $data->entryid = $entry->id;
    // 20240426 Trying to add old promptid here.
    $data->promptid = $entry->promptid;
    //$data->promptid = $promptid;
    $data->timecreated = $entry->timecreated;
    $data->title = $entry->title;
    $data->text = $entry->text;
    $data->textformat = $entry->format;
    // Think I might need to add a check for currententry && !entry to justify starting a new entry, else error.
} else if ($action == 'currententry' && !$entry) {
    // There are no entries for this user, so start the first one.
    $data->entryid = null;
    // 20250112 Testing promptid for new entry with a current prompt.
    $data->promptid = prompts::get_current_promptid($diary);
    $data->timecreated = time();
    $data->title = '';
    $data->text = '';
    $data->textformat = FORMAT_HTML;
} else {
    throw new moodle_exception(get_string('generalerror', 'diary'));
}

$data->id = $cm->id;
list ($editoroptions, $attachmentoptions) = results::diary_get_editor_and_attachment_options($course,
                                                                                             $context,
                                                                                             $diary,
                                                                                             $entry,
                                                                                             $action,
                                                                                             $firstkey);

$data = file_prepare_standard_editor($data,
                                     'text',
                                     $editoroptions,
                                     $context,
                                     'mod_diary',
                                     'entry',
                                     $data->entryid);
$data = file_prepare_standard_filemanager($data,
                                          'attachment',
                                          $attachmentoptions,
                                          $context,
                                          'mod_diary',
                                          'attachment',
                                          $data->entryid);

// 20201119 Added $diary->editdates setting.
$form = new mod_diary_entry_form(null,
    [
        'current' => $data,
        'cm' => $cm,
        'diary' => $diary->editdates,
        'editoroptions' => $editoroptions,
        'attachmentoptions' => $attachmentoptions,
    ]
);

// Set existing data loaded from the database for this entry.
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/diary/view.php?id=' . $cm->id);
} else if ($fromform = $form->get_data()) {
    // If data submitted, then process and store, contains text, format, and itemid.
    // Prevent CSFR.
    confirm_sesskey();
    $timenow = time();

    // This will be overwritten after we have the entryid.
    $newentry = new stdClass();
/*
    // 20260205 Grok - Decide promptid: preserve original on edit, use current on new entry
    if (!empty($fromform->entryid)) {
        // Editing → keep the original promptid (came via hidden field from DB)
        $newentry->promptid = $fromform->promptid;
    } else {
        // New entry → use the currently active prompt
        $newentry->promptid = prompts::get_current_promptid($diary);
    }
*/
    // 20260205 Grok recommended safety check.
    if (!empty($fromform->entryid)) {
        $original_promptid = $DB->get_field('diary_entries', 'promptid', ['id' => $fromform->entryid], MUST_EXIST);
        if ($fromform->promptid != $original_promptid) {
            // Log suspicious activity or just silently enforce original
            debugging("Prompt ID mismatch on edit - possible tampering? Using original.", DEBUG_DEVELOPER);
        }
        $newentry->promptid = $original_promptid;  // or $fromform->promptid if you allow changes someday
    } else {
        $newentry->promptid = prompts::get_current_promptid($diary);
    }
    $newentry->timecreated = $fromform->timecreated;
    $newentry->timemodified = $timenow;
    $newentry->title = $fromform->title;
    $newentry->text = $fromform->text_editor['text'];
    // 20250228 Added new field and setting status as no notice sent to the teacher, yet.
    $newentry->entrynoticemailed = 0;
    $newentry->format = $fromform->text_editor['format'];

    if (! $diary->editdates) {
        // If editdates is NOT enabled do attempted cheat testing here.
        // 20210619 Before we update, see if there is an entry in database with the same entryid.
        $entry = $DB->get_record("diary_entries",
            [
                "userid" => $USER->id,
                'id' => $fromform->entryid,
            ]
        );
    }

    // 20210619 If user tries to change timecreated, prevent it.
    // Need to move new code to up to just after getting $entry, to make a nested if.
    // Currently not taking effect on the overall user grade unless the teacher rates it.
    if ($fromform->entryid) {
        $newentry->id = $fromform->entryid;
        // 20240426 When I save the entry, this is undefined! 20260205 Grok says to remove this line.
        //$newentry->promptid = $fromform->promptid;

        if (($entry) && (!($entry->timecreated == $newentry->timecreated))) {
            // 20210620 New code to prevent attempts to change timecreated.
            $newentry->entrycomment = get_string('invalidtimechange', 'diary');
            $newentry->entrycomment .= get_string('invalidtimechangeoriginal', 'diary', ['one' => userdate($entry->timecreated)]);
            $newentry->entrycomment .= get_string('invalidtimechangenewtime', 'diary', ['one' => userdate($newentry->timecreated)]);
            // Probably do not want to just arbitraily set a rating.
            // Should leave it up to the teacher, otherwise will need to ascertain rating settings for the activity.
            // phpcs:ignore
            // $newentry->rating = 1;
            $newentry->teacher = 2;
            $newentry->timemodified = time();
            $newentry->timemarked = time();
            $newentry->timecreated = $entry->timecreated;
            $fromform->timecreated = $entry->timecreated;
            $newentry->entrycomment .= get_string('invalidtimeresettime', 'diary', ['one' => userdate($newentry->timecreated)]);
            $DB->update_record("diary_entries", $newentry);

            // Trigger module entry updated event.
            $event = \mod_diary\event\invalid_entry_attempt::create(
                [
                    'objectid' => $diary->id,
                    'context' => $context,
                ]
            );
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('diary', $diary);
            $event->trigger();
            redirect(new moodle_url('/mod/diary/view.php?id=' . $cm->id));
            die();
        }

        if (! $DB->update_record("diary_entries", $newentry)) {
            throw new moodle_exception(get_string('generalerrorupdate', 'diary'));
        }
    } else {
        $newentry->userid = $USER->id;
        $newentry->diary = $diary->id;
        // 20260205 Grok says remove the whole if block.
        //if (!$action == 'editentry') {
        //    // 20250112 Added to get correct promptid.
        //    $newentry->promptid = prompts::get_current_promptid($diary);
        //}
        if (! $newentry->id = $DB->insert_record('diary_entries', $newentry)) {
            throw new moodle_exception(get_string('generalerrorinsert', 'diary'));
        }
    }

    // Relink using the proper entryid.
    // We need to do this as draft area didn't have an itemid associated when creating the entry.
    $fromform = file_postupdate_standard_editor($fromform,
                                                'text',
                                                $editoroptions,
                                                $editoroptions['context'],
                                                'mod_diary',
                                                'entry',
                                                $newentry->id);
    //$newentry->promptid = $promptid; // 20260205 Grok says to remove.
    $newentry->title = $fromform->title;
    $newentry->text = $fromform->text;
    $newentry->format = $fromform->textformat;
    $newentry->timecreated = $fromform->timecreated;
    // ...$newentry->timemodified = $fromform->timemodified;
    $newentry->tags = $fromform->tags;

    $DB->update_record('diary_entries', $newentry);

    // Do some other processing here,
    // If this is a new page (entry) you need to insert it in the DB and obtain id.
    core_tag_tag::set_item_tags(
        'mod_diary',
        'diary_entries',
        $newentry->id,
        $context,
        $newentry->tags
    );

    // Fetch the full, fresh record from the DB to ensure the snapshot is complete.
    $finalrecord = $DB->get_record('diary_entries', ['id' => $newentry->id], '*', MUST_EXIST);

    // Process event as created or updated.
    if (!empty($fromform->entryid)) {
        $event = \mod_diary\event\entry_updated::create([
            'objectid' => $finalrecord->id,
            'context' => $context,
        ]);
    } else {
        $event = \mod_diary\event\entry_created::create([
            'objectid' => $finalrecord->id,
            'context' => $context,
        ]);
    }

    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    
    // Use the $finalrecord here so all fields (rating, teacher, etc.) are present.
    $event->add_record_snapshot('diary_entries', $finalrecord);
    $event->trigger();

    // Add confirmation of record being saved.
    echo $OUTPUT->notification(get_string('entrysuccess', 'diary'), 'notifysuccess');

    // Start new code to send teachers email note when diary entry is made.
    // 20231105 Modified code so non-editing teachers get an email, too.
    $role1 = $DB->get_record('role', ['shortname' => 'editingteacher']);
    $role2 = $DB->get_record('role', ['shortname' => 'teacher']);
    $contextcourse = context_course::instance($course->id);

    $teachers1 = get_role_users($role1->id, $contextcourse);
    $teachers2 = get_role_users($role2->id, $contextcourse);
    $teachers = array_merge($teachers1, $teachers2);
    $admin = get_admin();

    // BEFORE we do any email creation, we need to see if we even need to do it!
    // The foreach $teachers needs to be before the email wording creation.
    // This move will allow me to use the diarymail and diarymailhtml greetings strings.

    // Now send an email for each teacher in the course.
    // First check to see if the actual data has changed by comparing before and after text fields.
    // I think I might need to do some more debugging on the $data->text as I am receiving an email
    // even when the user opens for edit, then saves without making any changes.
    if ($data->text !== $newentry->text) {
        // If data has changed, then send the email(s).
        // 20230402 Since I added the two new fields to mdl_diary table, the following, if, check needs to be changed.
        // 20250302 Modified to check if $diary->submissionemail is enabled.
        if ((get_config('mod_diary', 'teacheremail')) && ($diary->teacheremail || $diary->studentemail)
            && ($diary->submissionemail)) {
            foreach ($teachers as $teacher) {
                // 20250303 Check teacher email preference toggle,Email now or Email later after the normal edit delay.
                if (get_user_preferences('diary_emailpreference_'.$diary->id, null, $teacher->id) == 1) {
                    $diaryinfo = new stdClass();
                    $diaryinfo->diary = format_string($diary->name, true);
                    // 20260114 Added the entry created time.
                    $diaryinfo->timecreated = date("l, F j, Y H:i:s", $newentry->timecreated);
                    if ($newentry->timemodified) {
                        $diaryinfo->timemodified = date("l, F j, Y H:i:s", $newentry->timemodified);
                    } else {
                        $diaryinfo->timemodified = date("l, F j, Y H:i:s", $newentry->timecreated);
                    }
                    // ...$diaryinfo->url = "$CFG->wwwroot/mod/diary/reportsingle.php?id=$cm->id&user=$USER->id&action=currententry";
                    // ...$diaryinfo->url = "$CFG->wwwroot/mod/diary/reportone.php?id=$cm->id&user=$USER->id&action=currententry";
                    $diaryinfo->url = "$CFG->wwwroot/mod/diary/reportone.php?id=$cm->id&user=$USER->id&action=currententry&entryid=$newentry->id";
                    $modnamesngl = get_string( 'modulename', 'diary' );
                    $modnamepl = get_string( 'modulenameplural', 'diary' );

                    // 20250303 Note that when this is done, $message will contain plain text and HTML versions of the message.
                    $message = new \core\message\message();
                    $message->courseid = $course->id; // ID of this course.
                    $message->modulename = $modnamesngl; // Name of this plugin.
                    $message->component = 'mod_diary'; // Diary plugin's name.
                    $message->name = 'diary_entry_notification'; // The notification name from message.php.
                    $message->userfrom = $USER; // The message is 'from' a specific user and it is set here.
                    $message->userto = $teacher->id;
                    // Needs the whole line changed to a string.
                    $message->subject = fullname($USER)." has posted a diary entry in course '$course->shortname' using the edit.php file.";
                    $message->fullmessage = 'Hi, \n';
                    $message->fullmessage .= "$course->shortname -> $modnamepl -> ".format_string($diary->name, true)."\n";
                    $message->fullmessage .= "---------------------------------------------------------------------\n";
                    $message->fullmessage .= fullname($USER).' '.get_string("diarymailuser", "diary", $diaryinfo)."\n";
                    $message->fullmessage .= "---------------------------------------------------------------------\n";
                    $message->fullmessageformat = FORMAT_MARKDOWN;
                    // Hardcoded text needs to be converted to strings, like the two already done.
                    $message->fullmessagehtml = "<p><font face=\"sans-serif\">" .
                            get_string("messagegreeting", "diary") . "$teacher->firstname $teacher->lastname,</p>" .
                            "<p>".fullname($USER).'&nbsp;' . get_string("diarymailhtmluser", "diary", $diaryinfo) . "</p>" .
                            "<p>The ".$SITE->shortname." Team</p>" .
                            "<br /><hr /><font face=\"sans-serif\">" .
                            "<p>".get_string("additionallinks", "diary") . "</p>" .
                            "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->" .
                            "<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$course->id\">Diaries</a> ->" .
                            "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$cm->id\">" . format_string($diary->name, true) .
                            "</a></font>" .
                            "</font><hr />";
                    $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message.
                    $message->contexturl = (new \moodle_url('/course/'))->out(false); // A relevant URL for the notification.
                    $message->contexturlname = 'Course list'; // Link title explaining where users get to for the contexturl.
                    // Extra content for specific processor.
                    // 20260116 Added date and time using format defined by mod_diary configuration settings.
                    $content = [
                        '*' => [
                            'header' => '<p>The ' . $SITE->fullname . ' Team ' . date("$dateformat") . '</p>',
                            'footer' => '<p>The ' . $SITE->fullname . ' Team ' . date("$dateformat") . '</p>',
                        ],
                    ];
                    $message->set_additional_content('email', $content);
                    /*
                    // You probably don't need attachments but if you do, here is how to add one
                    $usercontext = context_user::instance($teacher->id);
                    $file = new stdClass();
                    $file->contextid = $usercontext->id;
                    $file->component = 'user';
                    $file->filearea = 'private';
                    $file->itemid = 0;
                    $file->filepath = '/';
                    $file->filename = '1.txt';
                    $file->source = 'test';

                    $fs = get_file_storage();
                    $file = $fs->create_file_from_string($file, 'file1 content');
                    $message->attachment = $file;
                    */
                    // Actually send the message.
                    // 2025042901 Student13 just submitted an entry and got two debug messages from the next line of code.
                    $messageid = message_send($message);
                }
            }
        }
    }
    // End new code.
    redirect(new moodle_url('/mod/diary/view.php?id=' . $cm->id));
    die();
}

echo $OUTPUT->header();
if (($diary->intro) && ($CFG->branch < 400)) {
    echo $OUTPUT->heading(format_string($diary->name));
    $intro = $tempintro.'<br>'.format_module_intro('diary', $diary, $cm->id);
} else {
    $intro = format_module_intro('diary', $diary, $cm->id);
}
echo $OUTPUT->box($intro);
// Otherwise fill and print the form.
$form->display();
echo $OUTPUT->footer();
