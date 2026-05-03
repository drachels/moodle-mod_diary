<?php // phpcs:ignore
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
use mod_diary\local\prompts_form;
use mod_diary\event\invalid_access_attempt;
use mod_diary\event\prompt_edited;

require_once("../../config.php");
require_once('lib.php'); // May not need this.
require_once('./mod_form.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course Module ID.
$cm = get_coursemodule_from_id('diary', $id);
$action = optional_param('action', '', PARAM_ALPHANUMEXT); // Action(promt).
$promptid = optional_param('promptid', '', PARAM_INT); // Prompt ID.
$ruleaction = optional_param('ruleaction', '', PARAM_ALPHANUMEXT); // Rule action.
$ruleid = optional_param('ruleid', 0, PARAM_INT); // Rule id.
$ruleeditid = optional_param('ruleeditid', 0, PARAM_INT); // Rule id to prefill edit form.
$saveandcontinue = optional_param('saveandcontinue', '', PARAM_RAW_TRIMMED);
$promptbgc = optional_param('promptbgc', '#ffffff', PARAM_TEXT); // Prompt bgc default to fix undefined error down around line 322.
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $promptbgc)) {
    $promptbgc = '#ffffff';
}
$viewby = optional_param('viewby', -1, PARAM_INT);
$view = optional_param('viewp', -1, PARAM_INT);
$viewbyp = optional_param('viewbyp', 1, PARAM_INT);
$viewbyc = optional_param('viewbyc', 1, PARAM_INT);
$viewbyf = optional_param('viewbyf', 1, PARAM_INT);
$jumptocurrent = optional_param('jumptocurrent', 0, PARAM_INT);
$jumpdone = optional_param('jumpdone', 0, PARAM_INT);
$collapsedidsraw = optional_param('collapsedids', '', PARAM_TEXT);
$collapsedids = [];
if (!empty($collapsedidsraw)) {
    $collapsedidparts = explode(',', $collapsedidsraw);
    foreach ($collapsedidparts as $collapsedidpart) {
        $collapsedidpart = trim($collapsedidpart);
        if (ctype_digit($collapsedidpart)) {
            $collapsedid = (int)$collapsedidpart;
            if ($collapsedid > 0) {
                $collapsedids[$collapsedid] = $collapsedid;
            }
        }
    }
}
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
$selectedpromptdata = null;

// 20221002 Added sort for ticket Diary_926.
$prompts = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], $sort = 'datestart, datestop');

if (!empty($action)) {
    switch ($action) {
        case 'delete':
            if (has_capability('mod/diary:manageentries', $context)) {
                $promptid = required_param('promptid', PARAM_INT);  // Prompt ID to delete.
                // Before allowing the prompt to be removed, need to make sure it is NOT being used anywhere!
                if (!prompts::prompt_in_use($cm, $promptid)) {
                    prompts::prompt_remove($cm);
                    // Need redirect back to where we came from, with a success message.
                    redirect('prompt_edit.php?id=' . $id, get_string('promptremovesuccess', 'diary', $promptid));
                } else {
                    // Need redirect back to where we came from, with a failure message.
                    redirect('prompt_edit.php?id=' . $id, get_string('promptremovefailure', 'diary', $promptid));
                }
            }
            break;
        case 'edit':
            if (has_capability('mod/diary:manageentries', $context)) {
                $promptid = required_param('promptid', PARAM_INT); // Prompt ID to edit.
                $action = optional_param('action', 'edit', PARAM_ACTION); // Action(promt).
                $data = $DB->get_record('diary_prompts', ['id' => $promptid]);
                if ($data) {
                    $selectedpromptdata = clone $data;
                }

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

if (!empty($ruleaction) && has_capability('mod/diary:manageentries', $context)) {
    require_sesskey();

    $targetpromptid = required_param('promptid', PARAM_INT);
    $targetprompt = $DB->get_record('diary_prompts', ['id' => $targetpromptid, 'diaryid' => $diary->id]);
    if (!$targetprompt) {
        throw new moodle_exception(get_string('generalerror', 'diary'));
    }

    if ($ruleaction === 'delete' && !empty($ruleid)) {
        prompts::delete_autograde_rule((int)$ruleid, (int)$targetpromptid);
        $deleteurl = new moodle_url('/mod/diary/prompt_edit.php', [
            'id' => $cm->id,
            'action' => 'edit',
            'promptid' => $targetpromptid,
        ]);
        $deleteurl->set_anchor('promptautograderules');
        redirect($deleteurl, get_string('autograderuledeleted', 'diary'));
    }

    if ($ruleaction === 'save') {
        $phrase = trim((string)optional_param('rulephrase', '', PARAM_TEXT));
        if ($phrase === '') {
            $saveurl = new moodle_url('/mod/diary/prompt_edit.php', [
                'id' => $cm->id,
                'action' => 'edit',
                'promptid' => $targetpromptid,
            ]);
            $saveurl->set_anchor('promptautograderules');
            redirect(
                $saveurl,
                get_string('autograderulephraseempty', 'diary'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $rule = new stdClass();
        if (!empty($ruleid)) {
            $rule->id = (int)$ruleid;
        }
        $rule->diaryid = (int)$diary->id;
        $rule->promptid = (int)$targetpromptid;
        $rule->phrase = $phrase;
        $rule->matchtype = optional_param('rulematchtype', 0, PARAM_INT);
        $rule->casesensitive = optional_param('rulecasesensitive', 0, PARAM_INT);
        $rule->fullmatch = optional_param('rulefullmatch', 0, PARAM_INT);
        $rule->ignorebreaks = optional_param('ruleignorebreaks', 0, PARAM_INT);
        $rule->weightpercent = optional_param('ruleweightpercent', 0, PARAM_INT);
        $rule->required = optional_param('rulerequired', 0, PARAM_INT);
        $rule->studentvisible = optional_param('rulestudentvisible', 1, PARAM_INT);
        $rule->sortorder = optional_param('rulesortorder', 0, PARAM_INT);

        prompts::save_autograde_rule($rule);
        $saveurl = new moodle_url('/mod/diary/prompt_edit.php', [
            'id' => $cm->id,
            'action' => 'edit',
            'promptid' => $targetpromptid,
        ]);
        $saveurl->set_anchor('promptautograderules');
        redirect($saveurl, get_string('autograderulesaved', 'diary'));
    }
}

if ($view == -1) {
    $view = 0;
}

$editmode = ($action === 'edit' && !empty($promptid));
$selectedpromptcounter = 0;
if ($editmode && !empty($prompts)) {
    $promptindex = 0;
    foreach ($prompts as $promptrecord) {
        $promptindex++;
        if ((int)$promptrecord->id === (int)$promptid) {
            $selectedpromptcounter = $promptindex;
            break;
        }
    }
}

// Set up a general table to hold the list of prompts.
$tableheadrow1 = '';
$tableheadrow2 = '';

// 20240603 View prompt list view/hide.
if ($view == -1 || $view == 1) {
    $lnkadd = "&viewp=0";
} else {
    $lnkadd = "&viewp=1";
}

$arrtextadds = [];
$arrtextadds[1] = '<span class="arrow-s" style="font-size:1em;"></span>';

$arrtextadds[$viewby] = $view == -1 || $view == 1 ? '<span class="arrow-s" style="font-size:1em;">
    </span>' : '<span class="arrow-n" style="font-size:1em;"></span>';

$tableheadrow1 .= '<tr>';
$tableheadrow1 .= '<th><a href="?id=' . $id . '&viewby=1' . $lnkadd . '&collapsedids=#promptlist">'
    . get_string('tablecolumnstatus', 'diary') . $arrtextadds[1] . '</a></th>';
$tableheadrow1 .= '<th colspan="8">' . get_string('tablecolumnprompts', 'diary') . '</th>';
$tableheadrow1 .= '</tr>';

$tableheadrow2 .= '<tr>';
$tableheadrow2 .= '<th></th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnpromptsbgc', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnstart', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnstop', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumncharacters', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnwords', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnsentences', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnparagraphs', 'diary') . '</th>';
$tableheadrow2 .= '<th>' . get_string('tablecolumnedit', 'diary') . '</th>';
$tableheadrow2 .= '</tr>';

$output = '<a id="promptlist"></a><table class="generaltable" cellpadding="5"><thead>'
    . $tableheadrow1 . $tableheadrow2 . '</thead><tbody>';
$rows = '';
// Initialize a prompt counter.
$counter = 0;

// If there are any prompts for this diary, create a descending list of them.
if ($prompts && $view == 0) {
    foreach ($prompts as $prompt) {
        if ($editmode && (int)$prompt->id !== (int)$promptid) {
            continue;
        }
        $rowanchor = 'prompt-' . $prompt->id;
        $promptidint = (int)$prompt->id;
        $rowcollapsed = isset($collapsedids[$promptidint]);
        $nextcollapsedids = $collapsedids;
        if ($rowcollapsed) {
            unset($nextcollapsedids[$promptidint]);
        } else {
            $nextcollapsedids[$promptidint] = $promptidint;
        }
        ksort($nextcollapsedids);
        $nextcollapsedidsparam = implode(',', $nextcollapsedids);
        $statuslabel = get_string('promptsf', 'diary');
        if ($prompt->datestop < time()) {
            $statuslabel = get_string('promptsp', 'diary');
        } else if (($prompt->datestart < time()) && ($prompt->datestop > time())) {
            $statuslabel = get_string('promptsc', 'diary');
        }
        $statusicon = $rowcollapsed ? '&#9654;' : '&#9660;';
        $status = '<a href="?id=' . $id . '&collapsedids=' . urlencode($nextcollapsedidsparam) . '#' . $rowanchor . '">'
            . $statuslabel . '<span style="font-size:1em;">' . $statusicon . '</span></a>';

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
        $data->promptmaxeditopens = isset($prompt->maxeditopens) ? (int)$prompt->maxeditopens : -1;
        $data->title = $prompt->title ?? '';

        // If user can edit, create a delete link to the current prompt.
        // 20230810 Changed based on pull request #29.
        $url = new moodle_url('prompt_edit.php', ['id' => $id, 'action' => 'delete', 'promptid' => $prompt->id]);
        $jlink1 = '&nbsp;<a onclick="return confirm(\''
                  . get_string('deleteexconfirm', 'diary')
                  . $data->entryid
                  . '\')" href="' . $url->out(false) . '"><img src="pix/delete.png" title="'
                  . get_string('delete', 'diary') . '" alt="'
                  . get_string('delete', 'diary') . '"/></a>';

        // If user can edit, create an edit link to the current prompt.
        // Use prompt ID so we can come back to the Prompt Editor we came from.
        // 20230810 Changed based on pull request #29.
        $url = new moodle_url('prompt_edit.php', ['id' => $id, 'action' => 'edit', 'promptid' => $data->entryid]);
        $url->set_anchor('prompt-' . $data->entryid);
        $jlink2 = '<a href="' . $url->out(false) . '"><img src="pix/edit.png" alt='
                  . get_string('eeditlabel', 'diary') . '></a>';
        $counter++;
        $displaycounter = $counter;
        if ($editmode && $selectedpromptcounter > 0) {
            $displaycounter = $selectedpromptcounter;
        }

        if ($rowcollapsed) {
            $promptsummary = get_string('idlable', 'diary', $data->entryid) . ' '
                . userdate($data->datestart, get_string('strftimedateshort')) . ' - '
                . userdate($data->datestop, get_string('strftimedateshort'));
            $rows .= '<tr id="' . $rowanchor . '"><td>' . $status . '</td><td colspan="8">' . $promptsummary . '</td></tr>';
        } else {
            $titledisplay = !empty($data->title)
                ? '<br>' . get_string('prompttitle', 'diary') . ': <em>' . s(trim($data->title)) . '</em>'
                : '';
            $prompttext = '<div class="promptentry" style="background: '
                      . $data->promptbgc
                      . ';">'
                      . get_string('writingpromptlable2', 'diary')
                      . $displaycounter
                      . get_string('idlable', 'diary', $data->entryid)
                      . $titledisplay
                      . '<br>' . $data->text . '</div>';
            $promptbgc = '<td>' . $data->promptbgc . '</td>';
            $start = '<td>' . userdate($data->datestart) . '</td>';
            $stop = '<td>' . userdate($data->datestop) . '</td>';
            $characters = '<td>' . get_string('chars', 'diary') . '<br>'
                          . get_string('minc', 'diary') . $data->minchar . '<br>'
                          . get_string('maxc', 'diary') . $data->maxchar . '<br>'
                          . get_string('errp', 'diary') . $data->minmaxcharpercent . '</td>';
            $words = '<td>' . get_string('words', 'diary') . '&nbsp;&nbsp;&nbsp;<br>'
                     . get_string('minc', 'diary') . $data->minword . '<br>'
                     . get_string('maxc', 'diary') . $data->maxword . '<br>'
                     . get_string('errp', 'diary') . $data->minmaxwordpercent . '</td>';
            $sentences = '<td>' . get_string('sentences', 'diary') . '<br>'
                         . get_string('minc', 'diary') . $data->minsentence . '<br>'
                         . get_string('maxc', 'diary') . $data->maxsentence . '<br>'
                          . get_string('errp', 'diary') . $data->minmaxsentencepercent . '</td>';
            $paragraphs = '<td>' . get_string('paragraphs', 'diary') . '<br>'
                          . get_string('minc', 'diary') . $data->minparagraph . '<br>'
                          . get_string('maxc', 'diary') . $data->maxparagraph . '<br>'
                          . get_string('errp', 'diary') . $data->minmaxparagraphpercent . '</td>';

            $rows .= '<tr id="' . $rowanchor . '"><td>' . $status . '</td><td colspan="8">' . $prompttext . '</td></tr>';
            $rows .= '<tr><td></td>'
                . $promptbgc
                . $start
                . $stop
                . $characters
                . $words
                . $sentences
                . $paragraphs
                . '<td>' . $jlink2 . ' | ' . $jlink1 . '</td></tr>';
        }
    }

    // Now print out all the prompts for this diary.
    $output .= $rows;
    $counter = 0;
} else {
    // Double check for prompts when view is 1.
    [$tcount, $past, $current, $future] = prompts::diary_count_prompts($diary);
    $line = [];
    $data->entryid = null;
    $data->text = '';
    $data->format = FORMAT_HTML;
    if ($tcount > 0 && !empty($prompts)) {
        $lastprompt = end($prompts);
        if ($lastprompt) {
            $data->entryid = $lastprompt->id;
            $data->diaryid = $lastprompt->diaryid;
            $data->datestart = $lastprompt->datestart;
            $data->datestop = $lastprompt->datestop;
            $data->text = $lastprompt->text;
            $data->format = FORMAT_HTML;
            $data->promptbgc = $lastprompt->promptbgc;
            $data->minchar = $lastprompt->minchar;
            $data->maxchar = $lastprompt->maxchar;
            $data->minmaxcharpercent = $lastprompt->minmaxcharpercent;
            $data->minword = $lastprompt->minword;
            $data->maxword = $lastprompt->maxword;
            $data->minmaxwordpercent = $lastprompt->minmaxwordpercent;
            $data->minsentence = $lastprompt->minsentence;
            $data->maxsentence = $lastprompt->maxsentence;
            $data->minmaxsentencepercent = $lastprompt->minmaxsentencepercent;
            $data->minparagraph = $lastprompt->minparagraph;
            $data->maxparagraph = $lastprompt->maxparagraph;
            $data->minmaxparagraphpercent = $lastprompt->minmaxparagraphpercent;
            $data->promptmaxeditopens = isset($lastprompt->maxeditopens) ? (int)$lastprompt->maxeditopens : -1;
            $promptbgc = $lastprompt->promptbgc;
        }
    }
    if ($tcount > 0) {
        $prompttext = get_string('promptzerocount', 'diary', $tcount);
    } else {
        $prompttext = get_string('promptzerocount', 'diary', $counter);
    }
    $output .= '<tr><td colspan="9">' . strip_tags($prompttext) . '</td></tr>';
    $counter = 0;
}

$output .= '</tbody></table>';

if (!empty($jumptocurrent) && empty($jumpdone)) {
    $jumpurlparams = ['id' => $cm->id, 'jumptocurrent' => 1, 'jumpdone' => 1];
    if (!empty($data->entryid)) {
        $jumpurlparams['promptid'] = (int)$data->entryid;
        $jumpurl = new moodle_url('/mod/diary/prompt_edit.php', $jumpurlparams);
        $jumpurl->set_anchor('prompt-' . (int)$data->entryid);
    } else {
        $jumpurl = new moodle_url('/mod/diary/prompt_edit.php', $jumpurlparams);
        $jumpurl->set_anchor('prompteditor');
    }
    redirect($jumpurl);
}

if (!empty($selectedpromptdata)) {
    $data = $selectedpromptdata;
}

if (!isset($data->promptmaxeditopens)) {
    $data->promptmaxeditopens = isset($data->maxeditopens) ? (int)$data->maxeditopens : -1;
}

if (empty($data->entryid) && !empty($data->id)) {
    $data->entryid = (int)$data->id;
}

$data->id = $cm->id;
$data->textformat = FORMAT_HTML;

$maxfiles = 99; // Need to add some setting.
$maxbytes = $course->maxbytes; // Need to add some setting.
// 20240806 Moved variables from here down to the $form.
$editoroptions = [
    'format' => $data->textformat,
    'context' => $context,
];

$attachmentoptions = [
    'subdirs' => false,
    'maxfiles' => $maxfiles,
    'maxbytes' => $maxbytes,
];

$data = file_prepare_standard_editor(
    $data,
    'text',
    $editoroptions,
    $context,
    'mod_diary',
    'prompt',
    $data->entryid
);

// 20240806 Moved 12 variables from $editoroptions to here.
// 20260212 Changed the name and moved the form to /mod/diary/classes/local/.
$form = new prompts_form(
    null,
    [
        'current' => $data,
        'cm' => $cm,
        'diary' => $diary->editdates,
        'entryid' => $data->entryid,
        'editoroptions' => $editoroptions,
        'promptid' => $data->entryid,
        'promptbgc' => $promptbgc,
        'timeopen' => $diary->timeopen,
        'timeclose' => $diary->timeclose,
        'editall' => $diary->editall,
        'editdates' => $diary->editdates,
        'action' => $action,
        'texttrust' => true,
        'maxbytes' => $maxbytes,
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'subdirs' => false,
        'enablefilemanagement' => true,
    ]
);
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/diary/view.php?id=' . $id);
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
    $newentry->maxeditopens = (int)$fromform->promptmaxeditopens;
    $newentry->title = isset($fromform->title) ? trim((string)$fromform->title) : '';

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
    $fromform = file_postupdate_standard_editor(
        $fromform,
        'text',
        $editoroptions,
        $editoroptions['context'],
        'mod_diary',
        'prompt',
        $newentry->id
    );

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
    if (!empty($saveandcontinue)) {
        $continueurl = new moodle_url('/mod/diary/prompt_edit.php', [
            'id' => $cm->id,
            'action' => 'edit',
            'promptid' => $newentry->id,
        ]);
        $continueurl->set_anchor('prompteditor');
        redirect($continueurl);
    }

    // 20230810 Changed based on pull request #29.
    $saveurl = new moodle_url('/mod/diary/prompt_edit.php', ['id' => $cm->id, 'promptid' => $newentry->id]);
    $saveurl->set_anchor('prompt-' . $newentry->id);
    redirect($saveurl);
}

echo $OUTPUT->header();
echo $output;
echo '<a id="prompteditor"></a>';
echo $OUTPUT->heading(get_string('writingpromptlable3', 'diary'));
$intro = format_module_intro('diary', $diary, $cm->id);
$form->display();

if (!empty($data->entryid)) {
    $matchtypes = [
        0 => get_string('autograderulematchcontains', 'diary'),
        1 => get_string('autograderulematchexact', 'diary'),
        2 => get_string('autograderulematchregex', 'diary'),
    ];
    $rules = prompts::get_autograde_rules((int)$data->entryid);
    $ruleedit = null;
    if (!empty($ruleeditid)) {
        $ruleedit = prompts::get_autograde_rule((int)$ruleeditid, (int)$data->entryid);
    }

    if (empty($ruleedit)) {
        $ruleedit = new stdClass();
        $ruleedit->id = 0;
        $ruleedit->phrase = '';
        $ruleedit->matchtype = 0;
        $ruleedit->casesensitive = 0;
        $ruleedit->fullmatch = 0;
        $ruleedit->ignorebreaks = 0;
        $ruleedit->weightpercent = 0;
        $ruleedit->required = 0;
        $ruleedit->studentvisible = 1;
        $ruleedit->sortorder = prompts::next_autograde_rule_sortorder((int)$data->entryid);
    }

    echo '<a id="promptautograderules"></a>';
    echo $OUTPUT->heading(get_string('autograderulesheading', 'diary'), 4);
    echo '<p>' . get_string('autograderulesintro', 'diary') . '</p>';

    if (!empty($rules)) {
        echo '<div class="diary-targetphrases">';
        $rulenum = 1;

        foreach ($rules as $rule) {
            $editurl = new moodle_url('/mod/diary/prompt_edit.php', [
                'id' => $cm->id,
                'action' => 'edit',
                'promptid' => (int)$data->entryid,
                'ruleeditid' => (int)$rule->id,
            ]);
            $editurl->set_anchor('promptautograderules');

            $delurl = new moodle_url('/mod/diary/prompt_edit.php', [
                'id' => $cm->id,
                'action' => 'edit',
                'promptid' => (int)$data->entryid,
                'ruleaction' => 'delete',
                'ruleid' => (int)$rule->id,
                'sesskey' => sesskey(),
            ]);
            $delurl->set_anchor('promptautograderules');

            $requiredlabel = empty($rule->required) ? get_string('no') : get_string('yes');
            $matchlabel = $matchtypes[(int)$rule->matchtype] ?? $matchtypes[0];
            $fullmatchlabel = !empty($rule->fullmatch)
                ? get_string('autograderulefullmatch', 'diary')
                : get_string('autograderulematchcontains', 'diary');
            $caselabel = !empty($rule->casesensitive)
                ? get_string('autograderulecasesensitive', 'diary')
                : get_string('autograderulecaseinsensitive', 'diary');
            $breaklabel = !empty($rule->ignorebreaks)
                ? get_string('autograderuleignorebreaks', 'diary')
                : get_string('autograderulerecognizebreaks', 'diary');

            echo '<div class="diary-targetphrase-rule">';
            echo '<div class="diary-targetphrase-rule__label">';
            echo '<span class="diary-targetphrase-rule__title">' . get_string('autograderulephrase', 'diary')
                . ' [' . $rulenum . ']</span>';
            echo '<div class="diary-targetphrase-rule__actions">'
                . '<a href="' . $editurl->out(false) . '">' . get_string('edit') . '</a>'
                . ' | <a onclick="return confirm(\'' . get_string('deleteexconfirm', 'diary')
                . (int)$rule->id . '\')" href="' . $delurl->out(false) . '">' . get_string('delete') . '</a></div>';
            echo '</div>';

            echo '<div class="diary-targetphrase-rule__body">';
            echo '<div class="diary-targetphrase-rule__line">'
                . get_string('autograderulelineif', 'diary') . ' <strong>' . s($rule->phrase) . '</strong> '
                . get_string('autograderulelineusedaward', 'diary') . ' <strong>' . (int)$rule->weightpercent . '%</strong> '
                . get_string('autograderulelineofgrade', 'diary') . '</div>';

            echo '<div class="diary-targetphrase-rule__behavior">';
            echo '<span>' . s($matchlabel) . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . s($fullmatchlabel) . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . s($caselabel) . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . s($breaklabel) . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . get_string('autograderulerequired', 'diary') . ': ' . s($requiredlabel) . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . get_string('autograderulevisible', 'diary') . ': '
                . s(empty($rule->studentvisible)
                    ? get_string('autograderulevisiblehidden', 'diary')
                    : get_string('autograderulevisiblevisible', 'diary'))
                . '</span>';
            echo '<span class="diary-targetphrase-sep" aria-hidden="true">·</span>';
            echo '<span>' . get_string('autograderulesortorder', 'diary') . ': ' . (int)$rule->sortorder . '</span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            $rulenum++;
        }
        echo '</div>';
    }

    $saveurl = new moodle_url('/mod/diary/prompt_edit.php', [
        'id' => $cm->id,
        'action' => 'edit',
        'promptid' => (int)$data->entryid,
    ]);
    $saveurl->set_anchor('promptautograderules');

    echo '<form method="post" action="' . $saveurl->out(false) . '" class="diary-targetphrase-editor">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<input type="hidden" name="ruleaction" value="save">';
    echo '<input type="hidden" name="ruleid" value="' . (int)$ruleedit->id . '">';

    echo '<div class="diary-targetphrase-editor__phraseline">';
    echo '<span>' . get_string('autograderulelineif', 'diary') . '</span>';
    echo '<input type="text" id="rulephrase" name="rulephrase" value="' . s($ruleedit->phrase) . '"'
        . ' class="form-control diary-targetphrase-editor__phrasetext">';
    echo '<span>' . get_string('autograderulelineusedaward', 'diary') . '</span>';
    echo '<input type="number" id="ruleweightpercent" name="ruleweightpercent" min="0" max="100" value="'
        . (int)$ruleedit->weightpercent . '" class="form-control diary-targetphrase-editor__pctinput">';
    echo '<span>% ' . get_string('autograderulelineofgrade', 'diary') . '</span>';
    echo '</div>';

    echo '<div class="diary-targetphrase-editor__behaviorline">';
    echo '<select id="rulematchtype" name="rulematchtype" class="form-select"'
        . ' title="' . get_string('autograderulematchtype', 'diary') . '">';
    foreach ($matchtypes as $matchvalue => $matchlabel) {
        $selected = ((int)$ruleedit->matchtype === (int)$matchvalue) ? ' selected' : '';
        echo '<option value="' . (int)$matchvalue . '"' . $selected . '>' . s($matchlabel) . '</option>';
    }
    echo '</select>';

    echo '<select id="rulefullmatch" name="rulefullmatch" class="form-select"'
        . ' title="' . get_string('autograderulefullmatch', 'diary') . '">'
        . '<option value="0"' . (empty($ruleedit->fullmatch) ? ' selected' : '')
        . '>' . get_string('autograderulematchcontains', 'diary') . '</option>'
        . '<option value="1"' . (!empty($ruleedit->fullmatch) ? ' selected' : '')
        . '>' . get_string('autograderulefullmatch', 'diary') . '</option>'
        . '</select>';

    echo '<select id="rulecasesensitive" name="rulecasesensitive" class="form-select"'
        . ' title="' . get_string('autograderulecasesensitive', 'diary') . '">'
        . '<option value="0"' . (empty($ruleedit->casesensitive) ? ' selected' : '')
        . '>' . get_string('autograderulecaseinsensitive', 'diary') . '</option>'
        . '<option value="1"' . (!empty($ruleedit->casesensitive) ? ' selected' : '')
        . '>' . get_string('autograderulecasesensitive', 'diary') . '</option>'
        . '</select>';

    echo '<select id="ruleignorebreaks" name="ruleignorebreaks" class="form-select"'
        . ' title="' . get_string('autograderuleignorebreaks', 'diary') . '">'
        . '<option value="0"' . (empty($ruleedit->ignorebreaks) ? ' selected' : '')
        . '>' . get_string('autograderulerecognizebreaks', 'diary') . '</option>'
        . '<option value="1"' . (!empty($ruleedit->ignorebreaks) ? ' selected' : '')
        . '>' . get_string('autograderuleignorebreaks', 'diary') . '</option>'
        . '</select>';

    echo '<select id="rulerequired" name="rulerequired" class="form-select"'
        . ' title="' . get_string('autograderulerequired', 'diary') . '">'
        . '<option value="0"' . (empty($ruleedit->required) ? ' selected' : '') . '>' . get_string('no') . '</option>'
        . '<option value="1"' . (!empty($ruleedit->required) ? ' selected' : '') . '>' . get_string('yes') . '</option>'
        . '</select>';

    $visibleselected = isset($ruleedit->studentvisible) ? (int)$ruleedit->studentvisible : 1;
    echo '<select id="rulestudentvisible" name="rulestudentvisible" class="form-select"'
        . ' title="' . get_string('autograderulevisible', 'diary') . '">'
        . '<option value="1"' . ($visibleselected ? ' selected' : '') . '>'
        . get_string('autograderulevisiblevisible', 'diary') . '</option>'
        . '<option value="0"' . (!$visibleselected ? ' selected' : '') . '>'
        . get_string('autograderulevisiblehidden', 'diary') . '</option>'
        . '</select>';

    echo '<span class="diary-targetphrase-editor__sortlabel">' . get_string('autograderulesortorder', 'diary') . '</span>';
    echo '<input type="number" id="rulesortorder" name="rulesortorder" min="1" value="'
        . max(1, (int)$ruleedit->sortorder) . '" class="form-control diary-targetphrase-editor__sortinput">';
    echo '</div>';

    $submitlabel = empty($ruleedit->id) ? get_string('autograderuleadd', 'diary') : get_string('autograderuleupdate', 'diary');
    echo '<div class="diary-targetphrase-editor__formactions"><button type="submit" class="btn btn-primary">'
        . s($submitlabel) . '</button></div>';
    echo '</form><br>';
}

// 20230810 Changed based on pull request #29.
$url1 = new moodle_url($CFG->wwwroot . '/mod/diary/view.php', ['id' => $id]);
$url2 = new moodle_url($CFG->wwwroot . '/mod/diary/prompt_edit.php', ['id' => $cm->id, 'action' => 'create', 'promptid' => 0]);
// 20220920 Add a Create button and a return button. 20230810 Changed due to pull request #29.
echo '<br><a href="' . $url2->out(false) . '#prompteditor"
    class="btn btn-warning"
    style="border-radius: 8px">';
// 20230810 Changed due to pull request #29.
echo get_string('createnewprompt', 'diary') . '</a> <a href="' . $url1->out(false)
    . '" class="btn btn-success" style="border-radius: 8px">'
    . get_string('returnto', 'diary', $diary->name)
    . '</a> ';

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
