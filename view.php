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
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
use \mod_diary\local\results;

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot.'/lib/gradelib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID (cmid).
$cm = get_coursemodule_from_id('diary', $id, 0, false, MUST_EXIST); // Complete details for cmid.
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST); // Complete details about this course.
$action  = optional_param('action', 'currententry', PARAM_ACTION);  // Action(default to current entry).

if (! $cm) {
    print_error('invalidcoursemodule');
}

if (! $course) {
    print_error('coursemisconf');
}

$context = context_module::instance($cm->id);

// Confirm login.
require_login($course, true, $cm);

$entriesmanager = has_capability('mod/diary:manageentries', $context);
$canadd = has_capability('mod/diary:addentries', $context);

if (!$entriesmanager && !$canadd) {
    print_error('accessdenied', 'diary');
}

if (! $diary = $DB->get_record("diary", array("id" => $cm->instance))) {
    print_error('invalidcoursemodule');
}

if (! $cw = $DB->get_record("course_sections", array("id" => $cm->section))) {
    print_error('invalidcoursemodule');
}

// Ge the name for this diary activity.
$diaryname = format_string($diary->name, true, array('context' => $context));

// Get local renderer.
$output = $PAGE->get_renderer('mod_diary');
$output->init($cm);

// Handle toolbar capabilities.
if (!empty($action)) {
    switch ($action) {
        case 'download':
            if (has_capability('mod/diary:addentries', $context)) {
                // Call download entries function in lib.php.
                results::download_entries($context, $course, $id, $diary);
            }
            break;

        // Show the reload button for sorting from current entry to oldest entry.
        case 'reload':
            if (has_capability('mod/diary:addentries', $context)) {
                // Reload the current page.
                // $stringlable = 'currententry';
                $sortorderinfo = ('<h5>'.get_string('sortcurrententry', 'diary').'</h5>');
                $entrys = $DB->get_records('diary_entries', array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'timecreated DESC');
                // This works in php 7.3 only.
                // $firstkey = array_key_first($entrys);
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
                // $stringlable = 'currententry';
                $sortorderinfo = ('<h5>'.get_string('sortcurrententry', 'diary').'</h5>');
                $entrys = $DB->get_records('diary_entries', array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'timecreated DESC');
                // This works in php 7.3 only.
                // $firstkey = array_key_first($entrys);
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort the list of entries from oldest to newest based on timecreated.
        case 'sortfirstentry':
            if (has_capability('mod/diary:addentries', $context)) {
                // $stringlable = 'firstentry';
                $sortorderinfo = ('<h5>'.get_string('sortfirstentry', 'diary').'</h5>');
                $entrys = $DB->get_records("diary_entries", array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'timecreated ASC');
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort the list from lowest grade to highest grade. Show ungraded first, from oldest to newest.
        case 'lowestgradeentry':
            if (has_capability('mod/diary:addentries', $context)) {
                // $stringlable = 'lowestgradeentry';
                $sortorderinfo = ('<h5>'.get_string('sortlowestentry', 'diary').'</h5>');
                $entrys = $DB->get_records("diary_entries", array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'rating ASC, timemodified ASC');
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort list from highest grade to lowest grade. If tie grade, further sort from newest to oldest.
        case 'highestgradeentry':
            if (has_capability('mod/diary:addentries', $context)) {
                // $stringlable = 'highestgradeentry';
                $sortorderinfo = ('<h5>'.get_string('sorthighestentry', 'diary').'</h5>');
                $entrys = $DB->get_records("diary_entries", array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'rating DESC, timecreated DESC');
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        // Sort list from most recently modified to the one modified the longest time ago.
        case 'latestmodifiedentry':
            if (has_capability('mod/diary:addentries', $context)) {
                // $stringlable = 'latestmodifiedentry';
                $sortorderinfo = ('<h5>'.get_string('sortlastentry', 'diary').'</h5>');
                // May be needed for future version if editing old entries is allowed.
                $entrys = $DB->get_records("diary_entries", array('userid' => $USER->id,
                                                                  'diary' => $diary->id),
                                                            $sort = 'timemodified DESC');
                $firstkey = ''; // Fixes error if user has no entries at all.
                foreach ($entrys as $firstkey => $firstvalue) {
                    break;
                }
            }
            break;

        default:
            if (has_capability('mod/diary:addentries', $context)) {
                 // $stringlable = 'currententry';
            }
    }
}

// Header.
$PAGE->set_url('/mod/diary/view.php', array('id' => $cm->id));
$PAGE->navbar->add($diaryname);
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

// 20190523 Added this to force editing cog to show for Boost based themes.
if ($CFG->branch > 31) {
    $PAGE->force_settings_menu();
}

echo $OUTPUT->header();
echo $OUTPUT->heading($diaryname);

// If viewer is a manager, create a link to report.php showing diary entries made by users.
if ($entriesmanager) {
    // Check to see if groups are being used here.
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);
    groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/diary/view.php?id=$cm->id");

    $entrycount = diary_count_entries($diary, $currentgroup);

    echo '<div class="reportlink"><a href="report.php?id='.$cm->id.'&action=currententry">'.
        get_string('viewallentries', 'diary', $entrycount).'</a></div>';
}

echo $output->introduction($diary, $cm); // Ouput introduction in renderer.php.

echo '<br />';

// Check to see if diary is currently available.
$timenow = time();
if ($course->format == 'weeks' and $diary->days) {
    $timestart = $course->startdate + (($cw->section - 1) * 604800);
    if ($diary->days) {
        $timefinish = $timestart + (3600 * 24 * $diary->days);
    } else {
        $timefinish = $course->enddate;
    }
} else {
    // Have no time limits on the diarys.
    $timestart = $timenow - 1;
    $timefinish = $timenow + 1;
    $diary->days = 0;
}

// 20200815 Get the current rating for this user!
if ($diary->assessed != 'RATING_AGGREGATE_NONE') {
    $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $diary->id, $USER->id);
    $gradeitemgrademax = $gradinginfo->items[0]->grademax;
    $userfinalgrade = $gradinginfo->items[0]->grades[$USER->id];
    $currentuserrating = $userfinalgrade->str_long_grade;
} else {
    $currentuserrating = '';
}

$aggregatestr = results::get_diary_aggregation($diary->assessed);

if ($timenow > $timestart) {

    echo $OUTPUT->box_start();
    // Add Current entry Edit button and user toolbar.
    if ($timenow < $timefinish) {

        if ($canadd) {
            // 20200815 Added type of rating and current rating.
            echo '<table style="width:100%"><tr><td>'.get_string('sortorder', 'diary').'</td>'
                .'<td> </td><td><h5>'.$aggregatestr.'</h5></td></tr>';
            echo '<tr><td>'.$sortorderinfo.'</td><td> </td><td><h5>'.$currentuserrating.' </h5></td></table>';

            echo $output->box_start();

            // Add button for editing current entry or starting a new entry.
            echo $OUTPUT->single_button('edit.php?id='
               .$cm->id
               .'&firstkey='.$firstkey
               .'&action=currententry'
               , get_string('startoredit', 'diary'), 'get',
               array("class" => "singlebutton diarystart"));
            // Print user toolbar icons.
            echo ' '.get_string('usertoolbar', 'diary');
            echo $output->toolbar(has_capability('mod/diary:addentries', $context), $course, $id, $diary, $firstkey);

            // 20200709 Added selector for prefered number of entries per page. Default is 7.
            echo '<form method="post">';

            $oldperpage = get_user_preferences('diary_perpage_'.$diary->id, 7);
            $perpage = optional_param('perpage', $oldperpage, PARAM_INT);

            if ($perpage < 2) {
                $perpage = 2;
            }
            if ($perpage != $oldperpage) {
                set_user_preference('diary_perpage_'.$diary->id, $perpage);
            }

            $pagesizes = array(2 => 2,
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
                               1000 => 1000);
            // This creates the dropdown list for how many entries to show on the page.
            $selection = html_writer::select($pagesizes,
                                             'perpage',
                                             $perpage,
                                             false,
                                             array('id' => 'pref_perpage',
                                                   'class' => 'custom-select'));

            echo get_string('pagesize', 'diary').': <select onchange="this.form.submit()" name="perpage">';
            echo '<option selected="true" value="'.$selection.'</option>';
            echo '</select>';
            echo '</form>';

            echo $output->box_end();
        }
    }

    // Display entry with the $DB portion supplied/set by the toolbar.
    if ($entrys) {
        $thispage = 1;
        foreach ($entrys as $entry) {
            if (empty($entry->text)) {
                echo '<p align="center"><b>'.get_string('blankentry', 'diary').'</b></p>';

            } else if ($thispage <= $perpage) {
                $thispage++;
                $color3 = get_config('mod_diary', 'entrybgc');
                $color4 = get_config('mod_diary', 'entrytextbgc');

                // Start a division to contain the overall entry.
                echo '<div align="left" style="font-size:1em; padding: 5px;
                    font-weight:bold;background: '.$color3.';
                    border:2px solid black;
                    -webkit-border-radius:16px;
                    -moz-border-radius:16px;border-radius:16px;">';

                $date1 = new DateTime(date('Y-m-d G:i:s', time()));
                $date2 = new DateTime(date('Y-m-d G:i:s', $entry->timecreated));
                $diff = date_diff($date1, $date2);

                // Create edit entry toolbutton link to use for each individual entry.
                $options['id'] = $cm->id;
                $options['action'] = 'editentry';
                $options['firstkey'] = $entry->id;
                $url = new moodle_url('/mod/diary/edit.php', $options);
                $editthisentry = html_writer::link($url,
                    $output->pix_icon('i/edit',
                    get_string('editthisentry', 'diary')),
                    array('class' => 'toolbutton'));

                // Add a heading for each entry on the page.
                echo $OUTPUT->heading(get_string('entry', 'diary').': '
                    .date(get_config('mod_diary', 'dateformat'), $entry->timecreated)
                    .'  '.$editthisentry);

                // Start an inner division for the user's text entry container.
                echo '<div align="left" style="font-size:1em; padding: 5px;
                    font-weight:bold;background: '.$color4.';
                    border:1px solid black;
                    -webkit-border-radius:16px;
                    -moz-border-radius:16px;
                    border-radius:16px;">';

                // This adds the actual entry text division close tag for each entry listed on the page.
                echo results::diary_format_entry_text($entry, $course, $cm).'</div></p>';

                // Info regarding last edit and word count.
                if ($timenow < $timefinish) {
                    if (!empty($entry->timemodified)) {

                        echo '<div class="lastedit"><strong>Details: </strong> ('
                           .get_string('numwords', '', count_words($entry->text))
                           .') '.get_string('created', 'diary', ['one' => $diff->days
                           , 'two' => $diff->h]).'<br>';

                        echo '<strong>'.get_string('timecreated', 'diary').': </strong> ';
                        echo date(get_config('mod_diary', 'dateformat'), $entry->timecreated).'<br>';

                        echo '<strong> '.get_string('lastedited').': </strong> ';
                        echo date(get_config('mod_diary', 'dateformat'), $entry->timemodified).'<br>';

                        echo "</div>";
                    }

                    if (!empty($entry->timecreated) AND !empty($entry->timemodified) AND empty($entry->timemarked)) {
                        echo "<div class=\"needsedit\">".get_string("needsgrading", "diary"). "</div>";
                    } else if (!empty($entry->timemodified)
                        AND !empty($entry->timemarked)
                        AND $entry->timemodified > $entry->timemarked) {
                        echo "<div class=\"needsedit\">".get_string("needsregrade", "diary"). "</div>";
                    }

                    if (!empty($diary->days)) {
                        echo '<div class="editend"><strong>'.get_string('editingends', 'diary').': </strong> ';
                        echo userdate($timefinish).'</div>';
                    }

                } else {
                    echo '<div class="editend"><strong>'.get_string('editingended', 'diary').': </strong> ';
                    echo userdate($timefinish).'</div>';
                }

                // Print feedback from the teacher for the current entry.
                if (!empty($entry->entrycomment) or !empty($entry->rating)) {
                    // Get the rating for the current entry.
                    $grades = $entry->rating;
                    // Add a heading for each feedback on the page.
                    echo $OUTPUT->heading(get_string('feedback'));
                    // Format output using renderer.php.
                    echo $output->diary_print_feedback($course, $entry, $grades);
                }
                echo '</div></p>';

            }

        }

    } else {
        echo '<span class="warning">'.get_string('notstarted', 'diary').'</span>';
    }
    echo $OUTPUT->box_end();


} else {
    echo '<div class="warning">'.get_string('notopenuntil', 'diary').': ';
    echo userdate($timestart).'</div>';
}

// Trigger module viewed event.
$event = \mod_diary\event\course_module_viewed::create(array(
   'objectid' => $diary->id,
   'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('diary', $diary);
$event->trigger();

echo $OUTPUT->footer();
