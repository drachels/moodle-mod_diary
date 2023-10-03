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
$action = optional_param('action', 'currententry', PARAM_ACTION); // Action(default to current entry).
$firstkey = optional_param('firstkey', '', PARAM_INT); // Which diary_entries id to edit.

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('mod/diary:addentries', $context);

if (! $diary = $DB->get_record("diary", ["id" => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// 20221107 The $diary->intro gets overwritten by the current prompt and Notes, so keep a copy for later down in this file.
$tempintro = $diary->intro;

// Need to call a prompt function that returns the current promptid, if there is one that is current.
$promptid = prompts::get_current_promptid($diary);

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

// 20210817 Add min/max info to the description so user can see them while editing an entry.
diarystats::get_minmaxes($diary);

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
$entry = $DB->get_record("diary_entries",
    [
        "userid" => $USER->id,
        'id' => $firstkey,
    ]
);
// 20230306 Added code that lists the tags on the edit_form page.
$data->tags = core_tag_tag::get_item_tags_array('mod_diary', 'diary_entries', $firstkey);

if ($action == 'currententry' && $entry) {
    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;

    // Check the timecreated of the current entry to see if now is a new calendar day .
    // 20210425 If can edit dates, just start a new entry.
    if ((strtotime('today midnight') > $entry->timecreated) || ($action == 'currententry' && $diary->editdates)) {
        $entry = '';
        $data->entryid = null;
        $data->timecreated = time();
        $data->text = '';
        $data->textformat = FORMAT_HTML;
    }
} else if ($action == 'editentry' && $entry) {
    $data->entryid = $entry->id;
    $data->timecreated = $entry->timecreated;
    $data->text = $entry->text;
    $data->textformat = $entry->format;
    // Think I might need to add a check for currententry && !entry to justify starting a new entry, else error.
} else if ($action == 'currententry' && ! $entry) {
    // There are no entries for this user, so start the first one.
    $data->entryid = null;
    $data->timecreated = time();
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
    $newentry->timecreated = $fromform->timecreated;
    $newentry->timemodified = $timenow;
    $newentry->text = $fromform->text_editor['text'];
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
    // TODO: Need to move new code to up to just after getting $entry, to make a nested if.
    // Currently not taking effect on the overall user grade unless the teacher rates it.
    if ($fromform->entryid) {
        $newentry->id = $fromform->entryid;
        if (($entry) && (!($entry->timecreated == $newentry->timecreated))) {
            // 20210620 New code to prevent attempts to change timecreated.
            $newentry->entrycomment = get_string('invalidtimechange', 'diary');
            $newentry->entrycomment .= get_string('invalidtimechangeoriginal', 'diary', ['one' => userdate($entry->timecreated)]);
            $newentry->entrycomment .= get_string('invalidtimechangenewtime', 'diary', ['one' => userdate($newentry->timecreated)]);
            // Probably do not want to just arbitraily set a rating.
            // Should leave it up to the teacher, otherwise will need to ascertain rating settings for the activity.
            // @codingStandardsIgnoreLine
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
        if (! $newentry->id = $DB->insert_record("diary_entries", $newentry)) {
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
    $newentry->promptid = $promptid;
    $newentry->text = $fromform->text;
    $newentry->format = $fromform->textformat;
    $newentry->timecreated = $fromform->timecreated;
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

    // Try adding autosave cleanup here.
    // will need to search the mdl_editor_atto_autosave table
    // will need to find a match with contextid and user id.
    if ($entry) {
        // Trigger module entry updated event.
        $event = \mod_diary\event\entry_updated::create(
            [
                'objectid' => $diary->id,
                'context' => $context,
            ]
        );
    } else {
        // Trigger module entry created event.
        $event = \mod_diary\event\entry_created::create(
            [
                'objectid' => $diary->id,
                'context' => $context,
            ]
        );
    }
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();

    // Add confirmation of record being saved.
    echo $OUTPUT->notification(get_string('entrysuccess', 'diary'), 'notifysuccess');
    // Start new code to send teachers note when diary is done.
    $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    $contextcourse = context_course::instance($course->id);

    $teachers = get_role_users($role->id, $contextcourse);
    $admin = get_admin();
    // BEFORE we do any email creation, we need to see if we even need to do it!
    // The foreeach $teachers needs to be before the email wording creation.
    // This move will allow me to use the diarymail and diarymailhtml greetings strings.

    // Now send an email for each teacher in the course.
    // First check to see if the actual data has changed by comparing before and after text fields.
    // I think I might need to do some more debugging on the $data->text as I am receiving an email
    // even when the user opens for edit, then saves without making any changes.
    if ($data->text !== $newentry->text) {
        // If data has changed, then send the email(s).
        // 20230402 Since I added the two new fields to mdl_diary table, the following, if, check needs to be changed.
        if ((get_config('mod_diary', 'teacheremail')) && ($diary->teacheremail || $diary->studentemail)) {
            foreach ($teachers as $teacher) {
                if (get_user_preferences('diary_emailpreference_'.$diary->id, null, $teacher->id) == 1) {
                    // Code for plain text Email.
                    $diaryinfo = new stdClass();
                    $diaryinfo->diary = format_string($diary->name, true);
                    $diaryinfo->url = "$CFG->wwwroot/mod/diary/reportsingle.php?id=$cm->id&user=$USER->id&action=currententry";
                    $modnamepl = get_string( 'modulenameplural', 'diary' );
                    $msubject = get_string( 'mailsubject', 'diary' );
                    $postsubject = fullname($USER)." has posted a diary entry in '$course->shortname'";
                    $posttext = "Hi, \n";
                    $posttext .= "$course->shortname -> $modnamepl -> ".format_string($diary->name, true)."\n";
                    $posttext .= "---------------------------------------------------------------------\n";
                    $posttext .= fullname($USER).' '.get_string("diarymailuser", "diary", $diaryinfo)."\n";
                    $posttext .= "---------------------------------------------------------------------\n";

                    // If user wants HTML format, use this code.
                    if ($USER->mailformat == 1) {  // HTML.
                        $posthtml = "<p><font face=\"sans-serif\">".
                            "Hi $teacher->firstname $teacher->lastname,<br>".

                            "<p>".fullname($USER).'&nbsp;'.get_string("diarymailhtmluser", "diary", $diaryinfo)."</p>".
                            "<p>The ".$SITE->shortname." Team</p>".
                            "<br /><hr /><font face=\"sans-serif\">".
                            "<p>".get_string("additionallinks", "diary")."</p>".
                            "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                            "<a href=\"$CFG->wwwroot/mod/diary/index.php?id=$course->id\">diarys</a> ->".
                            "<a href=\"$CFG->wwwroot/mod/diary/view.php?id=$cm->id\">".format_string($diary->name, true).
                            "</a></font></p>".
                            "</font><hr />";
                    } else {
                        $posthtml = "";
                    }
                    $testemail = email_to_user($teacher, $admin, $postsubject, $posttext, $posthtml);

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
