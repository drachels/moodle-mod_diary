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
 * This page opens the current instance of a diary's prompts for editing.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;
use mod_diary\local\diarystats;
use mod_diary\local\prompts;
use mod_diary\event\invalid_access_attempt;
use mod_diary\event\prompt_edited;

require_once("../../config.php");
require_once('lib.php'); // May not need this.
require_once('./mod_form.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course Module ID.
$cm = get_coursemodule_from_id('diary', $id);
$action = optional_param('action', '', PARAM_ACTION); // Action(promt).
$promptid = optional_param('promptid', '', PARAM_INT); // Prompt ID.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}
if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);
require_login($course, false, $cm);
require_capability('mod/diary:addinstance', $context);

if (! $diary = $DB->get_record("diary", ["id" => $cm->instance])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

// Header.
$PAGE->set_url('/mod/diary/prompt_edit.php', ['id' => $id]);
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($diary->name));
$PAGE->set_heading($course->fullname);

$data = new stdClass();

// 20221002 Added sort for ticket Diary_926.
$prompts = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'datestart, datestop');

if (!empty($action)) {
    switch ($action) {
        case 'delete':
            if (has_capability('mod/diary:manageentries', $context)) {
                $promptid = required_param('promptid',  PARAM_INT);  // Prompt ID to delete.
                // Before allowing the prompt to be removed, need to make sure it is NOT being used anywhere!
                if (!prompts::prompt_in_use($cm, $promptid)) {
                    prompts::prompt_remove($cm);
                    // Need redirect back to where we came from, with a success message.
                    redirect('prompt_edit.php?id='.$id, get_string('promptremovesuccess', 'diary', $promptid));
                } else {
                    // Need redirect back to where we came from, with a failure message.
                    redirect('prompt_edit.php?id='.$id, get_string('promptremovefailure', 'diary', $promptid));
                }
            }
        break;
        case 'edit':
            if (has_capability('mod/diary:manageentries', $context)) {
                $promptid = required_param('promptid',  PARAM_INT); // Prompt ID to edit.
                $action = optional_param('action', 'edit', PARAM_ACTION); // Action(promt).
                $data = $DB->get_record('diary_prompts', ['id' => $promptid]);
                $prompts = $DB->get_records('diary_prompts', ['id' => $promptid], $sort = 'id ASC');

                // Trigger prompt edited event.
                $event = \mod_diary\event\prompt_edited::create(
                    [
                        'objectid' => $cm->id,
                        'context' => $context,
                        'other' => [
                            'promptid' => $promptid,
                            'diaryid' => $diary->id,
                        ],
                    ]
                );
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('course', $course);
                $event->add_record_snapshot('diary', $diary);
                $event->trigger();
            }
        break;
        case 'create':
            if (has_capability('mod/diary:manageentries', $context)) {
                $action = optional_param('action', 'create', PARAM_ACTION); // Action(promt).
                $temp = $DB->insert_record('diary_prompts', ['diaryid' => $diary->id]);
                // Pretty sure I do not need the next line of code. Need to verify.
                $data = $DB->get_record('diary_prompts', ['id' => $temp]);
                $prompts = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'id ASC');
                foreach ($prompts as $prompt => $temp) {
                    break;
                }
                // Trigger prompt created event.
                $event = \mod_diary\event\prompt_created::create(
                    [
                        'objectid' => $cm->id,
                        'context' => $context,
                        'other' => [
                            'promptid' => $data->id,
                            'diaryid' => $diary->id,
                        ],
                    ]
                );
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('course', $course);
                $event->add_record_snapshot('diary', $diary);
                $event->trigger();
            }
        break;
        default:

    }
}

// Set up a general table to hold the list of prompts.
$table = new html_table();
$table->cellpadding = 5;
$table->class = 'generaltable';

// Add column headings to the table list of prompts.
$table->head = [
    get_string('tablecolumnstatus', 'diary'),
    get_string('tablecolumnprompts', 'diary'),
    get_string('tablecolumnpromptsbgc', 'diary'),
    get_string('tablecolumnstart', 'diary'),
    get_string('tablecolumnstop', 'diary'),
    get_string('tablecolumncharacters', 'diary'),
    get_string('tablecolumnwords', 'diary'),
    get_string('tablecolumnsentences', 'diary'),
    get_string('tablecolumnparagraphs', 'diary'),
    get_string('tablecolumnedit', 'diary'),
];

$output = '';
$line = [];
$counter = 0;

// If there are any prompts for this diary, create a list of them.
if ($prompts) {
    foreach ($prompts as $prompt) {
        $status = '';
        if ($prompt->datestop < time()) {
            $status = 'Past';
        } else if (($prompt->datestart < time()) && $prompt->datestop > time()) {
            $status = 'Current';
        } else if ($prompt->datestart > time()) {
            $status = 'Future';
        }
        $data->entryid = $prompt->id;
        $data->diaryid = $prompt->diaryid;
        $data->datestart = $prompt->datestart;
        $data->datestop = $prompt->datestop;
        $data->text = $prompt->text;
        $data->format = FORMAT_HTML;
        $data->promptbgc = $prompt->promptbgc;
        $data->minchar = $prompt->minchar;
        $data->maxchar = $prompt->maxchar;
        $data->minmaxcharpercent = $prompt->minmaxcharpercent;
        $data->minword = $prompt->minword;
        $data->maxword = $prompt->maxword;
        $data->minmaxwordpercent = $prompt->minmaxwordpercent;
        $data->minsentence = $prompt->minsentence;
        $data->maxsentence = $prompt->maxsentence;
        $data->minmaxsentencepercent = $prompt->minmaxsentencepercent;
        $data->minparagraph = $prompt->minparagraph;
        $data->maxparagraph = $prompt->maxparagraph;
        $data->minmaxparagraphpercent = $prompt->minmaxparagraphpercent;

        // If user can edit, create a delete link to the current prompt.
        // 20230810 Changed based on pull request #29.
        $url = new moodle_url('prompt_edit.php', ['id' => $id, 'action' => 'delete', 'promptid' => $prompt->id]);
        $jlink1 = '&nbsp;<a onclick="return confirm(\''
                  .get_string('deleteexconfirm', 'diary')
                  .$data->entryid
                  .'\')" href="'. $url->out(false) .'"><img src="pix/delete.png" title="'
                  .get_string('delete', 'diary') .'" alt="'
                  .get_string('delete', 'diary') .'"/></a>';

        // If user can edit, create an edit link to the current prompt.
        // Use prompt ID so we can come back to the Prompt Editor we came from.
        // 20230810 Changed based on pull request #29.
        $url = new moodle_url('prompt_edit.php', ['id' => $id, 'action' => 'edit', 'promptid' => $data->entryid]);
        $jlink2 = '<a href="'.$url->out(false).'"><img src="pix/edit.png" alt='
                  .get_string('eeditlabel', 'diary').'></a>';
        $counter++;
        $prompttext = '<td bgcolor="'.$data->promptbgc.'">'
                      .get_string('writingpromptlable2', 'diary')
                      .$counter
                      .get_string('idlable', 'diary', $data->entryid)
                      .'<br>'.$data->text.'</td>';
        $promptbgc = '<td>'.$data->promptbgc.'</td>';
        $start = '<td>'.userdate($data->datestart).'</td>';
        $stop = '<td>'.userdate($data->datestop).'</td>';
        $characters = '<td>'.get_string('chars', 'diary').'<br>'
                      .get_string('minc', 'diary').$data->minchar.'<br>'
                      .get_string('maxc', 'diary').$data->maxchar.'<br>'
                      .get_string('errp', 'diary').$data->minmaxcharpercent.'</td>';
        $words = '<td>'.get_string('words', 'diary').'&nbsp;&nbsp;&nbsp;<br>'
                 .get_string('minc', 'diary').$data->minword.'<br>'
                 .get_string('maxc', 'diary').$data->maxword.'<br>'
                 .get_string('errp', 'diary').$data->minmaxwordpercent.'</td>';
        $sentences = '<td>'.get_string('sentences', 'diary').'<br>'
                     .get_string('minc', 'diary').$data->minsentence.'<br>'
                     .get_string('maxc', 'diary').$data->maxsentence.'<br>'
                      .get_string('errp', 'diary').$data->minmaxsentencepercent.'</td>';
        $paragraphs = '<td>'.get_string('paragraphs', 'diary').'<br>'
                      .get_string('minc', 'diary').$data->minparagraph.'<br>'
                      .get_string('maxc', 'diary').$data->maxparagraph.'<br>'
                      .get_string('errp', 'diary').$data->minmaxparagraphpercent.'</td>';
        $edit = '<td>'.$jlink2.' | '.$jlink1.'</td></tr>';
        // Create a line containing the data for our current prompt.
        $line[] = $status.$prompttext.$promptbgc.$start.$stop.$characters.$words.$sentences.$paragraphs.$edit;
    }

    // Now print out all the prompts for this diary.
    $table->data[] = $line;
    $output = html_writer::table($table);
    $counter = 0;
} else {
    $line = [];
    $data->entryid = null;
    $data->text = '';
    $data->format = FORMAT_HTML;
    $prompttext = get_string('promptzerocount', 'diary', $counter);
    $line[] = $prompttext.'';
    $table->data[] = $line;
    $output = html_writer::table($table);
    $counter = 0;
}

$data->id = $cm->id;
$data->textformat = FORMAT_HTML;

$maxfiles = 99; // Need to add some setting.
$maxbytes = $course->maxbytes; // Need to add some setting.
$editoroptions = [
    'promptid' => $data->entryid,
    'format' => $data->textformat,
    'promptbgc' => $data->promptbgc,
    'timeopen' => $diary->timeopen,
    'timeclose' => $diary->timeclose,
    'editall' => $diary->editall,
    'editdates' => $diary->editdates,
    'action' => $action,
    'texttrust' => true,
    'maxbytes' => $maxbytes,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'context' => $context,
    'subdirs' => false,
    'enable_filemanagement' => true,
];

$attachmentoptions = [
    'subdirs' => false,
    'maxfiles' => $maxfiles,
    'maxbytes' => $maxbytes,
];

$data = file_prepare_standard_editor($data,
                                     'text',
                                     $editoroptions,
                                     $context,
                                     'mod_diary',
                                     'prompt',
                                     $data->entryid);

$form = new mod_diary_prompt_form(null,
    [
        'current' => $data,
        'cm' => $cm,
        'diary' => $diary->editdates,
        'entryid' => $data->entryid,
        'editoroptions' => $editoroptions,
    ]
);
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot.'/mod/diary/view.php?id='.$id);
} else if ($fromform = $form->get_data()) {
    // If the prompt was submitted, then process and store.
    $newentry = new stdClass();
    $newentry->id = $fromform->entryid;
    $newentry->datestart = $fromform->datestart;
    $newentry->datestop = $fromform->datestop;
    $newentry->text = $fromform->text_editor['text'];
    $newentry->format = $fromform->text_editor['format'];
    $newentry->promptbgc = $fromform->promptbgc;
    $newentry->minchar = $fromform->minchar;
    $newentry->maxchar = $fromform->maxchar;
    $newentry->minmaxcharpercent = $fromform->minmaxcharpercent;
    $newentry->minword = $fromform->minword;
    $newentry->maxword = $fromform->maxword;
    $newentry->minmaxwordpercent = $fromform->minmaxwordpercent;
    $newentry->minsentence = $fromform->minsentence;
    $newentry->maxsentence = $fromform->maxsentence;
    $newentry->minmaxsentencepercent = $fromform->minmaxsentencepercent;
    $newentry->minparagraph = $fromform->minparagraph;
    $newentry->maxparagraph = $fromform->maxparagraph;
    $newentry->minmaxparagraphpercent = $fromform->minmaxparagraphpercent;

    if ($fromform->entryid) {
        $newentry->id = $fromform->entryid;
        if (!$DB->update_record('diary_prompts', $newentry)) {
            throw new \moodle_exception(get_string('couldnotupdateprompt', 'diary'));
        }
    } else {
        $newentry->diaryid = $diary->id;
        if (!$newentry->id = $DB->insert_record('diary_prompts', $newentry)) {
            throw new \moodle_exception(get_string('countnotinsertdiaryprompt', 'diary'));
        }
    }

    // Relink using the proper entryid.
    // We need to do this as draft area didn't have an itemid associated when creating the entry.
    $fromform = file_postupdate_standard_editor($fromform, 'text', $editoroptions,
        $editoroptions['context'], 'mod_diary', 'prompt', $newentry->id);

    $newentry->datestart = $fromform->datestart;
    $newentry->datestop = $fromform->datestop;
    $newentry->text = $fromform->text;
    $newentry->format = FORMAT_HTML;
    $newentry->promptbgc = $fromform->promptbgc;
    $newentry->minchar = $fromform->minchar;
    $newentry->maxchar = $fromform->maxchar;
    $newentry->minmaxcharpercent = $fromform->minmaxcharpercent;
    $newentry->minword = $fromform->minword;
    $newentry->maxword = $fromform->maxword;
    $newentry->minmaxwordpercent = $fromform->minmaxwordpercent;
    $newentry->minsentence = $fromform->minsentence;
    $newentry->maxsentence = $fromform->maxsentence;
    $newentry->minmaxsentencepercent = $fromform->minmaxsentencepercent;
    $newentry->minparagraph = $fromform->minparagraph;
    $newentry->maxparagraph = $fromform->maxparagraph;
    $newentry->minmaxparagraphpercent = $fromform->minmaxparagraphpercent;

    $DB->update_record('diary_prompts', $newentry);
    // 20230810 Changed based on pull request #29.
    redirect(new moodle_url('/mod/diary/prompt_edit.php', ['id' => $cm->id, 'promptid' => $newentry->id]));
}

echo $OUTPUT->header();
echo $output;
// Need to change this to a string.
echo $OUTPUT->heading(get_string('writingpromptlable3', 'diary'));

$intro = format_module_intro('diary', $diary, $cm->id);

$form->display();

// 20230810 Changed based on pull request #29.
$url1 = new moodle_url($CFG->wwwroot.'/mod/diary/view.php', ['id' => $id]);
$url2 = new moodle_url($CFG->wwwroot.'/mod/diary/prompt_edit.php', ['id' => $cm->id, 'action' => 'create', 'promptid' => 0]);
// 20220920 Add a Create button and a return button. 20230810 Changed due to pull request #29.
echo '<br><a href="'.$url2->out(false).'"
    class="btn btn-warning"
    style="border-radius: 8px">';
// 20230810 Changed due to pull request #29.
echo get_string('createnewprompt', 'diary').'</a> <a href="'.$url1->out(false)
    .'" class="btn btn-success" style="border-radius: 8px">'
    .get_string('returnto', 'diary', $diary->name)
    .'</a> ';

// Trigger prompts viewed event.
$event = \mod_diary\event\prompts_viewed::create(
    [
        'objectid' => $cm->id,
        'context' => $context,
        'other' => [
            'diaryid' => $diary->id,
        ],
    ]
);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('diary', $diary);
$event->trigger();

echo $OUTPUT->footer();
