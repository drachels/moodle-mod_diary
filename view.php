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
 * This page opens the current view instance of diary.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\diarystats;
use mod_diary\local\prompts;
use mod_diary\local\results;
// @codingStandardsIgnoreLine
// use core_text;

// 20210605 Changed to this format.
require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ .'/lib.php');
require_once(__DIR__ .'/../../lib/gradelib.php');

$id = required_param('id', PARAM_INT); // Course Module ID (cmid).
$cm = get_coursemodule_from_id('diary', $id, 0, false, MUST_EXIST); // Complete details for cmid.
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST); // Complete details about this course.
$action = optional_param('action', 'currententry', PARAM_ACTION); // Action(default to current entry).

if (!$cm) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (!$course) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

$context = context_module::instance($cm->id);

// Confirm login.
require_login($course, true, $cm);

$entriesmanager = has_capability('mod/diary:manageentries', $context);
$canadd = has_capability('mod/diary:addentries', $context);

if (!$entriesmanager && !$canadd) {
    throw new moodle_exception(get_string('accessdenied', 'diary'));
}

if (!$diarys = get_all_instances_in_course('diary', $course)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'diary')), '../../course/view.php?id=$course->id');
    die();
}

foreach ($diarys as $temp) {
    if ($temp->id === $cm->instance) {
        $diary = $temp;
        // 20210705 Added new activity color setting. Gets the setting for the correct Diary activity.
        $color3 = $diary->entrybgc;
        $color4 = $diary->entrytextbgc;
        // 20230324 Added a background color for prompts used with any individual diary entries.
        // Will need to convert this to a setting.
        $promptbgc = 'yellow';
    }
}

// 20230511 Following two lines are for View, Automatic Completion marking.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Need to call a prompt function that returns the current promptid, if there is one that is current.
$promptid = prompts::get_current_promptid($diary);

if (!$cw = $DB->get_record('course_sections', ['id' => $cm->section])) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

// Get the name for this diary activity.
$diaryname = format_string($diary->name, true, ['context' => $context]);

// Get local renderer.
$output = $PAGE->get_renderer('mod_diary');
$output->init($cm);

// Handle toolbar capabilities.
if (!empty($action)) {
    switch ($action) {
        case 'download':
            if (has_capability('mod/diary:addentries', $context)) {
                // Call download entries function in results.php.
                // 20231007 Added set_url to fix error.
                $PAGE->set_url('/mod/diary/view.php', ['id' => $cm->id]);
                results::download_entries($context, $course, $diary);
            }
            break;

        // Show the reload button for sorting from current entry to oldest entry.
        case 'reload':
            if (has_capability('mod/diary:addentries', $context)) {
                // Reload the current page.
                $sortorderinfo = (get_string('sortcurrententry', 'diary'));
                $entrys = $DB->get_records('diary_entries',
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'timecreated DESC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Show the edit button for editing the first entry in the current list of entries.
        case 'currententry':
            if (has_capability('mod/diary:addentries', $context)) {
                // Reload the current page.
                $sortorderinfo = (get_string('sortcurrententry', 'diary'));
                $entrys = $DB->get_records('diary_entries',
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'timecreated DESC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort the list of entries from oldest to newest based on timecreated.
        case 'sortfirstentry':
            if (has_capability('mod/diary:addentries', $context)) {
                $sortorderinfo = (get_string('sortfirstentry', 'diary'));
                $entrys = $DB->get_records("diary_entries",
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'timecreated ASC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort the list from lowest grade to highest grade. Show ungraded first, from oldest to newest.
        case 'lowestgradeentry':
            if (has_capability('mod/diary:addentries', $context)) {
                $sortorderinfo = (get_string('sortlowestentry', 'diary'));
                $entrys = $DB->get_records("diary_entries",
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'rating ASC, timemodified ASC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort list from highest grade to lowest grade. If tie grade, further sort from newest to oldest.
        case 'highestgradeentry':
            if (has_capability('mod/diary:addentries', $context)) {
                $sortorderinfo = (get_string('sorthighestentry', 'diary'));
                $entrys = $DB->get_records("diary_entries",
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'rating DESC, timecreated DESC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort list from most recently modified to the one modified the longest time ago.
        case 'latestmodifiedentry':
            if (has_capability('mod/diary:addentries', $context)) {
                $sortorderinfo = (get_string('sortlastentry', 'diary'));
                // May be needed for future version if editing old entries is allowed.
                $entrys = $DB->get_records("diary_entries",
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'timemodified DESC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        default:
            if (has_capability('mod/diary:addentries', $context)) {
                // Reload the current page.
                $sortorderinfo = (get_string('sortcurrententry', 'diary'));
                $entrys = $DB->get_records('diary_entries',
                    [
                        'userid' => $USER->id,
                        'diary' => $diary->id,
                    ],
                    $sort = 'timecreated DESC'
                );
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
    }
}

// Header.
$PAGE->set_url('/mod/diary/view.php', ['id' => $cm->id]);
$PAGE->navbar->add(get_string("viewentries", "diary"));
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

// 20190523 Added this to force editing cog to show for Boost based themes.
if ($CFG->branch > 31) {
    $PAGE->force_settings_menu();
}

echo $OUTPUT->header();
// 20220123 Added if check for an intro and also checks for Moodle 4.0 code. Was showing twice on last update.
if (($diary->intro) && ($CFG->branch < 400)) {
    echo $OUTPUT->heading($diaryname);
    echo $output->introduction($diary, $cm); // Output introduction in renderer.php.
}

// 20221008 Hide the prompts info if the Diary activity is not available.
// 20221027 Halt and force a fix if too many current prompts.
if (prompts::diary_available($diary)) {
    list($tcount, $past, $current, $future) = prompts::diary_count_prompts($diary);
    if ($current > 1) {
        // 20230810 Changed via pull request #29.
        $url1 = new moodle_url($CFG->wwwroot.'/mod/diary/prompt_edit.php', ['id' => $cm->id]);
        echo '</a> <a href="'.$url1->out(true)
            .'" class="btn btn-success" style="border-radius: 8px">'
            .get_string('warning', 'diary', $current)
            .'</a> ';
        die;
    } else {
        $status = prompts::prompts_viewcurrent($diary);
        // Show the current prompt.
        echo '<b>'.$diary->intro.'</b>';
    }
    echo get_string('tcount', 'diary', $tcount);
    echo get_string('promptinfo', 'diary', ['past' => $past, 'current' => $current, 'future' => $future]);
}

// If viewer is a manager, create a link to report.php showing diary entries made by users.
if ($entriesmanager) {
    // Check to see if groups are being used here.
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);
    $ouput = groups_print_activity_menu($cm, $CFG->wwwroot."/mod/diary/view.php?id=$cm->id");
    // 20230131 Ticket, Diary_954, fixes entry count shown for selected group.
    $entrycount = results::diary_count_entries($diary, $currentgroup);

    // 20200827 Add link to index.php page right after the report.php link. 20210501 modified to remove div.
    // 20230810 Added current group to index.php href.
    $temp = '<span class="reportlink"><a href="report.php?id='.$cm->id.'&action=currententry">';
    $temp .= get_string('viewallentries', 'diary', $entrycount).'</a>&nbsp;&nbsp;|&nbsp;&nbsp;';
    $temp .= '<a href="index.php?id='.$course->id.'&currentgroup='.$currentgroup.'">'.get_string('viewalldiaries', 'diary').'</a>';
    $temp .= '</a></span>';
    echo $temp;
} else {
    // 20200831 Added to show link to only index.php page for students. 20210501 modified to remove div.
    echo '<a class="reportlink" href="index.php?id='.$course->id.'">'.get_string('viewalldiaries', 'diary').'</a>';
}

// 20200901 Visual separator between activity info and entries.
echo '<hr>';

// Check to see if diary is currently available.
$timenow = time();
if ($course->format == 'weeks' && $diary->days) {
    $timestart = $course->startdate + (($cw->section - 1) * 604800);
    if ($diary->days) {
        $timefinish = $timestart + (3600 * 24 * $diary->days);
    } else {
        $timefinish = $course->enddate;
    }
} else if (!(results::diary_available($diary))) {
    // 20200904 If used, set calendar availability time limits on the diarys.
    $timestart = $diary->timeopen;
    $timefinish = $diary->timeclose;
    $diary->days = 0;
} else {
    // Have no time limits on the diarys.
    $timestart = $timenow - 1;
    $timefinish = $timenow + 1;
    $diary->days = 0;
}

// 20200815 Get the current rating for this user, if this diary is assessed.
if ($diary->assessed != 0) {
    $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $diary->id, $USER->id);

    $gradeitemgrademax = $gradinginfo->items[0]->grademax;
    $userfinalgrade = $gradinginfo->items[0]->grades[$USER->id];
    $currentuserrating = $userfinalgrade->str_long_grade;
} else {
    $currentuserrating = '';
}

$aggregatestr = results::get_diary_aggregation($diary->assessed);

if ($timenow > $timestart) {
    // Initialize now so it doesn't break if cannot edit.
    $oldperpage = get_user_preferences('diary_perpage_'.$diary->id, 7);
    $perpage = optional_param('perpage', $oldperpage, PARAM_INT);

    $oldstatspreference = get_user_preferences('diary_statspreference_'.$diary->id, null);
    $statspreference = optional_param('statspreference', $oldstatspreference, PARAM_INT);

    $oldemailpreference = get_user_preferences('diary_emailpreference_'.$diary->id, null);
    $emailpreference = optional_param('emailpreference', $oldemailpreference, PARAM_INT);

    echo $OUTPUT->box_start();
    // 20200815 Create table and added sort order and type of rating and current rating. 20201004 Moved info here.
    echo '<table class="sortandaggregate">'
        .'<tr><td>'.get_string('sortorder', 'diary').'</td>'
        .'<td> </td>'
        .'<td class="cell">'.$aggregatestr.'</td></tr>'
        . '<tr><td>'.$sortorderinfo.'</td><td> </td><td class="cell">'.$currentuserrating.' </td></tr></table>';

    // Add Current entry Edit button and user toolbar.
    if ($timenow < $timefinish) {
        if ($canadd) {
            echo $output->box_start();

            if ($diary->editdates) {
                // 20210425 Add button for starting a new entry.
                echo $OUTPUT->single_button('edit.php?id='.$cm->id
                    .'&firstkey='.$firstkey
                    .'&action=currententry', get_string('startnewentry', 'diary'), 'get',
                    ["class" => "singlebutton diarystart"]
                );
            } else {
                // Add button for editing current entry or starting a new entry.
                echo $OUTPUT->single_button('edit.php?id='.$cm->id
                    .'&firstkey='.$firstkey
                    .'&action=currententry', get_string('startoredit', 'diary'), 'get',
                    ["class" => "singlebutton diarystart"]
                );
            }
            // Print user toolbar icons only if there is at least one entry for this user.
            if ($entrys) {
                echo '<span style="float: right;">'.get_string('usertoolbar', 'diary');
                echo $output->toolbar(has_capability('mod/diary:addentries', $context), $course, $id, $diary, $firstkey).'</span>';
            }
            // 20200709 Added selector for prefered number of entries per page. Default is 7.
            echo '<form method="post">';

            if ($perpage < 2) {
                $perpage = 2;
            }
            if ($perpage != $oldperpage) {
                set_user_preference('diary_perpage_'.$diary->id, $perpage);
            }

            $pagesizes = [
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5,
                6 => 6,
                7 => 7,
                8 => 8,
                9 => 9,
                10 => 10,
                15 => 15,
                20 => 20,
                30 => 30,
                40 => 40,
                50 => 50,
                100 => 100,
                200 => 200,
                300 => 300,
                400 => 400,
                500 => 500,
                1000 => 1000,
            ];
            // This creates the dropdown list for how many entries to show on the page.
            $selection = html_writer::select($pagesizes, 'perpage', $perpage, false,
                ['id' => 'pref_perpage', 'class' => 'custom-select']
            );

            echo get_string('pagesize', 'diary').': <select onchange="this.form.submit()" name="perpage">';
            echo '<option selected="true" value="'.$selection.'</option>';
            // 20200905 Added count of all user entries.
            echo '</select>'.get_string('outof', 'diary', (count($entrys)));
            // Extend the form to include the stats selector.

            // 20230322 Added selector for prefered stats view. Default is ON.
            if ($statspreference != $oldstatspreference) {
                set_user_preference('diary_statspreference_'.$diary->id, $statspreference);
            }

            $listoptions = [
                1 => get_string('statsshow', 'diary'),
                2 => get_string('statshide', 'diary'),
            ];
            // This creates the dropdown list for how many entries to show on the page.
            $selection = html_writer::select($listoptions, 'statspreference', $statspreference, false,
                ['id' => 'pref_stats', 'class' => 'custom-select']
            );

            echo ' | '.get_string('statshdr', 'diary').': <select onchange="this.form.submit()" name="statspreference">';
            echo '<option selected="true" value="'.$selection.'</option>';
            echo '</select>';

            // 20230323 Extend form and added selector for prefered email delivery. Default is ON.
            // Need to check if user is an entry manager here so that students do not see the email pref.
            if (($entriesmanager) && (($diary->teacheremail == 1) || ($diary->studentemail == 1))) {
                if ($emailpreference != $oldemailpreference) {
                    set_user_preference('diary_emailpreference_'.$diary->id, $emailpreference);
                }

                $listoptions = [
                    1 => get_string('emailnow', 'diary'),
                    2 => get_string('emaillater', 'diary'),
                ];
                // This creates the dropdown list for email preference on the page above the first entry.
                $selection = html_writer::select($listoptions, 'emailpreference', $emailpreference, false,
                    ['id' => 'pref_emails', 'class' => 'custom-select']
                );

                echo ' | '.get_string('emailpreference', 'diary').': <select onchange="this.form.submit()" name="emailpreference">';
                echo '<option selected="true" value="'.$selection.'</option>';
                echo '</select>';
            }
            echo '</form>';
            echo $output->box_end();
        }
    } else {
        // 20201004 added Editing period has ended message.
        echo '<div class="editend"><strong>'.get_string('editingended', 'diary').': </strong> ';
        echo userdate($timefinish).'</div>';
    }

    // Display entry with the $DB portion supplied/set by the toolbar.
    if ($entrys) {
        // 20200905 Fixed Entries per page when activity is closed.
        if ($timenow > $timefinish) {
            // 20200905 If a diary is closed, show all entries to a user.
            $perpage = (count($entrys));
            $thispage = '1';
        } else {
            $thispage = '1';
        }
        foreach ($entrys as $entry) {
            if (empty($entry->text)) {
                echo '<p align="center"><b>'.get_string('blankentry', 'diary').'</b></p>';
            } else if ($thispage <= $perpage) {
                $thispage ++;
                // 20210501 Changed to class, start a division to contain the overall entry.
                echo '<div class="entry" style="background: '.$color3.';">';

                $date1 = new DateTime(date('Y-m-d G:i:s', time()));
                $date2 = new DateTime(date('Y-m-d G:i:s', $entry->timecreated));
                $diff = date_diff($date1, $date2);

                // Create edit entry toolbutton link to use for each individual entry.
                $options['id'] = $cm->id;
                $options['action'] = 'editentry';
                $options['firstkey'] = $entry->id;
                $options['promptid'] = $promptid;
                $url = new moodle_url('/mod/diary/edit.php', $options);
                // 20200901 If editing time has expired, remove the edit toolbutton from the title.
                // 20201015 Enable/disable check of the edit old entries editing tool.
                if ($timenow < $timefinish && $diary->editall) {
                    $editthisentry = html_writer::link($url, $output->pix_icon('i/edit', get_string('editthisentry', 'diary')),
                        ['class' => 'toolbutton']);
                } else {
                    $editthisentry = ' ';
                }

                // 20231108 If there is a title for the entry add it as a heading.
                echo $OUTPUT->heading($entry->title);

                // Add, Entry, then date time group heading for each entry on the page.
                // 20231108 Add the old heading version as a sub-heading.
                echo ('<h5>'.get_string('entry', 'diary').': '.userdate($entry->timecreated).' '.$editthisentry.'</h5>');

                // 20230314 If one exists, display the apllicable prompt.
                if ($entry->promptid > 0) {
                    $promptused = get_string('writingpromptused', 'diary', $entry->promptid);
                    $prompt = $DB->get_record('diary_prompts', ['id' => $entry->promptid, 'diaryid' => $diary->id]);
                    // 20230321 Use contrasting color for the prompt used background.
                    echo '<div class="entry" style="background: '.$promptbgc.';">';

                    echo '<strong>'.get_string('prompttext', 'diary').'</strong>: '.$prompt->text.'</div>';
                }

                // 20210511 Start an inner division for the user's text entry container.
                // 20210705 Added new activity color setting. 20210704 Switched to a setting.
                echo '<div class="entry" style="background: '.$color4.';">';

                // This adds the actual entry text division close tag for each entry listed on the page.
                echo results::diary_format_entry_text($entry, $course, $cm).'</div>';

                // Info regarding entry details with stats, date when created, and date of last edit.
                if ($timenow < $timefinish) {
                    // 20211217 If there is a user entry, format it and show it.
                    if ($entry) {
                        // 20230323 Added a check for stats preference.
                        if ($entry && ($statspreference == 1)) {
                            $temp = $entry;
                            // 20210704 Go calculate stats and print stats table.
                            // 20210703 Moved to here from up above so the table gets rendered in the right spot.
                            $statsdata = diarystats::get_diary_stats($temp, $diary);
                            // 20211212 Moved the echo for output here instead of in the function in the diarystats file.
                            echo $statsdata;
                            // 20211212 Added separate function to get the glossary common error data here.
                            $comerrdata = diarystats::get_common_error_stats($temp, $diary);
                            echo $comerrdata;
                            // 20211212 Added separate function to get the autorating data here.
                            list($autoratingdata,
                                $currentratingdata)
                                = diarystats::get_auto_rating_stats($temp, $diary);
                            echo $autoratingdata;
                        }
                    } else {
                        print_string("noentry", "diary");
                        // 20210701 Moved copy 2 of 2 here due to new stats.
                        echo '</div></td><td style="width:55px;"></td></tr>';
                    }

                    echo '</table>';

                    // Added lines to mark entry as needing to be rated or rated again after the entry was updated.
                    if (!empty($entry->timecreated) && !empty($entry->timemodified) && empty($entry->timemarked)) {
                        echo '<div class="needsedit">'.get_string('needsgrading', 'diary').'</div>';
                    } else if (!empty($entry->timemodified) && !empty($entry->timemarked)
                        && $entry->timemodified > $entry->timemarked) {
                        echo '<div class="needsedit">'.get_string('needsregrade', 'diary').'</div>';
                    }

                    if (!empty($diary->days)) {
                        echo '<div class="editend"><strong>'.get_string('editingends', 'diary').': </strong> ';
                        echo userdate($timefinish).'</div>';
                    }
                } else {
                    echo '<div class="editend"><strong>'.get_string('editingended', 'diary').': </strong> ';
                    echo userdate($timefinish).'</div>';
                }

                // 20230302 Added tags to each entry.
                echo $OUTPUT->tag_list(
                    core_tag_tag::get_item_tags(
                        'mod_diary',
                        'diary_entries',
                        $entry->id
                    ),
                    null,
                    'diary-tags'
                );

                // Print feedback from the teacher for the current entry.
                if (!empty($entry->entrycomment) || !empty($entry->rating)) {
                    // Get the rating for the current entry.
                    $grades = $entry->rating;
                    // Add a heading for each feedback on the page.
                    echo $OUTPUT->heading(get_string('feedback'));
                    // Add the teachers feedback.
                    echo results::diary_print_feedback($course, $entry, $grades);
                }
                // This adds blank space between entries.
                echo '</div></p>';
            }
        }
    } else {
        echo '<span class="warning">'.get_string('notstarted', 'diary').'.</span>';
    }
    echo $OUTPUT->box_end();
} else {
    echo '<div class="warning">'.get_string('notopenuntil', 'diary').': ';
    echo userdate($timestart).'.</div>';
}

// Trigger module viewed event.
$event = \mod_diary\event\course_module_viewed::create(
    [
        'objectid' => $diary->id,
        'context' => $context,
    ]
);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('diary', $diary);
$event->trigger();

echo $OUTPUT->footer();
