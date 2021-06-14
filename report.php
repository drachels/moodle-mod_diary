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
 * This page opens the current report instance of diary.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot . '/rating/lib.php');

$id = required_param('id', PARAM_INT); // Course module.
$action = optional_param('action', 'currententry', PARAM_ACTION); // Action(default to current entry).

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (! $course = $DB->get_record("course", array(
    "id" => $cm->course
))) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/diary:manageentries', $context);

if (! $diary = $DB->get_record("diary", array(
    "id" => $cm->instance
))) {
    throw new moodle_exception(get_string('invalidid', 'diary'));
}

// 20201016 Get the name for this diary activity.
$diaryname = format_string($diary->name, true, array(
    'context' => $context
));

// 20201014 Set a default sorting order for entry retrieval.
if ($sortoption = get_user_preferences('sortoption')) {
    $sortoption = get_user_preferences('sortoption');
} else {
    set_user_preference('sortoption', 'u.lastname ASC, u.firstname ASC');
    $sortoption = get_user_preferences('sortoption');
}

// Handle toolbar capabilities.
if (! empty($action)) {
    switch ($action) {
        case 'download':
            if (has_capability('mod/diary:manageentries', $context)) {
                // Call download entries function in lib.php.
                results::download_entries($context, $course, $diary);
            }
            break;
        case 'lastnameasc':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'lastnameasc';
                // 20201014 Set order and get ALL diary entries in lastname ascending order.
                set_user_preference('sortoption', 'u.lastname ASC, u.firstname ASC');
                $sortoption = get_user_preferences('sortoption');
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ));
            }
            break;
        case 'lastnamedesc':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'lastnamedesc';
                // 20201014 Set order and get ALL diary entries in lastname descending order.
                set_user_preference('sortoption', 'u.lastname DESC, u.firstname DESC');
                $sortoption = get_user_preferences('sortoption');
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ));
            }
            break;
        case 'currententry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'currententry';
                // Get ALL diary entries in an order that will result in showing the users most current entry.
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ));
            }
            break;
        case 'firstentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'firstentry';
                // Get ALL diary entries in an order that will result in showing the users very first entry.
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ), $sort = 'timecreated DESC');
            }
            break;
        case 'lowestgradeentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'lowestgradeentry';
                // Get ALL diary entries in an order that will result in showing the users
                // oldest, ungraded entry. Once all ungraded entries have a grade, the entry
                // with the lowest grade is shown. For duplicate low grades, the entry that
                // is oldest, is shown.
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ), $sort = 'rating DESC, timemodified DESC');
            }
            break;
        case 'highestgradeentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'highestgradeentry';
                // Get ALL diary entries in an order that will result in showing the users highest
                // graded entry. Duplicates high grades result in showing the most recent entry.
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ), $sort = 'rating ASC');
            }
            break;
        case 'latestmodifiedentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'latestmodifiedentry';
                // Get ALL diary entries in an order that will result in showing the users
                // most recently modified entry. At the moment, this is no different from current entry.
                // May be needed for future version if editing old entries is allowed.
                $eee = $DB->get_records("diary_entries", array(
                    "diary" => $diary->id
                ), $sort = 'timemodified ASC');
            }
            break;
        default:
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'currententry';
            }
    }
}

// Header.
$PAGE->set_url('/mod/diary/report.php', array(
    'id' => $id
));
$PAGE->navbar->add((get_string("rate", "diary")) . ' ' . (get_string("entries", "diary")));
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($diaryname);

// 20210511 Changed to using div and span.
echo '<div class="sortandaggregate">';
echo ('<span>'.get_string('sortorder', "diary"));
echo (get_string($stringlable, "diary").'</span>');

// 20200827 Added link to index.php page. 20210501 Moved to here.
echo '<span><a style="float: right;" href="index.php?id='.$course->id.'">'
    .get_string('viewalldiaries', 'diary').'</a></span></div>';

// Get a list of groups for this course.
$currentgroup = groups_get_activity_group($cm, true);
if ($currentgroup) {
    $groups = $currentgroup;
} else {
    $groups = '';
}

// Get a sorted list of users in the current group to use for processing the report.
$users = get_users_by_capability($context, 'mod/diary:addentries', '', $sort = 'lastname ASC, firstname ASC', '', '', $groups);

if ($eee) {
    // Now, filter down to get entry by any user who has made at least one entry.
    foreach ($eee as $ee) {
        $entrybyuser[$ee->userid] = $ee;
        $entrybyentry[$ee->id] = $ee;
        $entrybyuserentry[$ee->userid][$ee->id] = $ee;
    }
} else {
    $entrybyuser = array();
    $entrybyentry = array();
}

// Process incoming data if there is any.
if ($data = data_submitted()) {
    confirm_sesskey();
    $feedback = array();
    $data = (array) $data;
    // My single data entry contains id, sesskey, and three other items, entry, feedback, and ???
    // Peel out all the data from variable names.
    foreach ($data as $key => $val) {
        if (strpos($key, 'r') === 0 || strpos($key, 'c') === 0) {
            $type = substr($key, 0, 1);
            $num = substr($key, 1);
            $feedback[$num][$type] = $val;
        }
    }

    $timenow = time();
    $count = 0;
    foreach ($feedback as $num => $vals) {
        $entry = $entrybyentry[$num];
        // Only update entries where feedback has actually changed.
        $ratingchanged = false;
        if ($diary->assessed != RATING_AGGREGATE_NONE) {
            $studentrating = clean_param($vals['r'], PARAM_INT);
        } else {
            $studentrating = '';
        }
        $studentcomment = clean_text($vals['c'], FORMAT_PLAIN);

        if ($studentrating != $entry->rating && ! ($studentrating == '' && $entry->rating == "0")) {
            $ratingchanged = true;
        }

        if ($ratingchanged || $studentcomment != $entry->entrycomment) {
            $newentry = new StdClass();
            $newentry->rating = $studentrating;
            $newentry->entrycomment = $studentcomment;
            $newentry->teacher = $USER->id;
            $newentry->timemarked = $timenow;
            $newentry->mailed = 0; // Make sure mail goes out (again, even).
            $newentry->id = $num;
            if (! $DB->update_record("diary_entries", $newentry)) {
                notify("Failed to update the diary feedback for user $entry->userid");
            } else {
                $count ++;
            }
            $entrybyuser[$entry->userid]->rating = $studentrating;
            $entrybyuser[$entry->userid]->entrycomment = $studentcomment;
            $entrybyuser[$entry->userid]->teacher = $USER->id;
            $entrybyuser[$entry->userid]->timemarked = $timenow;

            $records[$entry->id] = $entrybyuser[$entry->userid];

            // Compare to database view.php line 465.
            if ($diary->assessed != RATING_AGGREGATE_NONE) {
                // 20200812 Added rating code and got it working.
                $ratingoptions = new stdClass();
                $ratingoptions->contextid = $context->id;
                $ratingoptions->component = 'mod_diary';
                $ratingoptions->ratingarea = 'entry';
                $ratingoptions->itemid = $entry->id;
                $ratingoptions->aggregate = $diary->assessed; // The aggregation method.
                $ratingoptions->scaleid = $diary->scale;
                $ratingoptions->rating = $studentrating;
                $ratingoptions->userid = $entry->userid;
                $ratingoptions->timecreated = $entry->timecreated;
                $ratingoptions->timemodified = $entry->timemodified;
                $ratingoptions->returnurl = $CFG->wwwroot . '/mod/diary/report.php?id' . $id;

                $ratingoptions->assesstimestart = $diary->assesstimestart;
                $ratingoptions->assesstimefinish = $diary->assesstimefinish;
                // 20200813 Check if there is already a rating, and if so, just update it.
                if ($rec = results::check_rating_entry($ratingoptions)) {
                    $ratingoptions->id = $rec->id;
                    $DB->update_record('rating', $ratingoptions, false);
                } else {
                    $DB->insert_record('rating', $ratingoptions, false);
                }
            }

            $diary = $DB->get_record("diary", array(
                "id" => $entrybyuser[$entry->userid]->diary
            ));
            $diary->cmidnumber = $cm->idnumber;

            diary_update_grades($diary, $entry->userid);
        }
    }

    // Trigger module feedback updated event.
    $event = \mod_diary\event\feedback_updated::create(array(
        'objectid' => $diary->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();

    // Report how many entries were updated when the, Save all my feedback button was pressed.
    echo $OUTPUT->notification(get_string("feedbackupdated", "diary", "$count"), "notifysuccess");
} else {

    // Trigger module viewed event.
    $event = \mod_diary\event\entries_viewed::create(array(
        'objectid' => $diary->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();
}

if (! $users) {
    echo $OUTPUT->heading(get_string("nousersyet"));
} else {
    $output = '';
    // Create download, reload, current, oldest, lowest, highest, and most recent tool buttons for all entries.
    if (has_capability('mod/diary:manageentries', $context)) {
        // 20201003 Changed toolbar code to $output instead of html_writer::alist.
        $options = array();
        $options['id'] = $id;
        $options['diary'] = $diary->id;

        // Add download button.
        $options['action'] = 'download';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('i/export', get_string('csvexport', 'diary')), array(
            'class' => 'toolbutton'
        ));

        // Add sort by lastname ascending button.
        $options['action'] = 'lastnameasc';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/sort_asc', get_string('lastnameasc', 'diary')), array(
            'class' => 'toolbutton'
        ));

        // Add sort by lastname descending button.
        $options['action'] = 'lastnamedesc';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/sort_desc', get_string('lastnamedesc', 'diary')), array(
            'class' => 'toolbutton'
        ));

        // Add reload toolbutton.
        $options['action'] = $stringlable;
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/reload', get_string('reload', 'diary')), array(
            'class' => 'toolbutton'
        ));

        $options['action'] = 'currententry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('i/edit', get_string('currententry', 'diary')), array(
            'class' => 'toolbutton'
        ));

        $options['action'] = 'firstentry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/left', get_string('firstentry', 'diary')), array(
            'class' => 'toolbutton'
        ));

        $options['action'] = 'lowestgradeentry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/down', get_string('lowestgradeentry', 'diary')), array(
            'class' => 'toolbutton'
        ));

        $options['action'] = 'highestgradeentry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/up', get_string('highestgradeentry', 'diary')), array(
            'class' => 'toolbutton'
        ));

        $options['action'] = 'latestmodifiedentry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/right', get_string('latestmodifiedentry', 'diary')), array(
            'class' => 'toolbutton'
        ));

        // 20210511 Reorganized group and toolbar output.
        echo '<span>'.groups_print_activity_menu($cm, $CFG->wwwroot."/mod/diary/report.php?id=$cm->id")
            .'</span><span style="float: right;">'.get_string('toolbar', 'diary').$output.'</span>';
    }

    // Next line is different from Journal line 171.
    $grades = make_grades_menu($diary->scale);

    if (! $teachers = get_users_by_capability($context, 'mod/diary:manageentries')) {
        throw new moodle_exception(get_string('noentriesmanagers', 'diary'));
    }
    // Start the page area where feedback and grades are added and will need to be saved.
    echo '<form action="report.php" method="post">';
    // Create a variable with all the info to save all my feedback, so it can be used multiple places.
    $saveallbutton = '';
    $saveallbutton = "<p class=\"feedbacksave\">";
    $saveallbutton .= "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
    $saveallbutton .= "<input type=\"hidden\" name=\"sesskey\" value=\"" . sesskey() . "\" />";
    $saveallbutton .= "<input type=\"submit\" class='btn btn-primary' value=\"" . get_string("saveallfeedback", "diary") . "\" />";

    // 20200421 Added a return button.
    $url = $CFG->wwwroot . '/mod/diary/view.php?id=' . $id;
    $saveallbutton .= ' <a href="'.$url
                     .'" class="btn btn-secondary" role="button">'
                     .get_string('returnto', 'diary', $diary->name)
                     .'</a>';

    $saveallbutton .= "</p>";

    // Add save button at the top of the list of users with entries.
    echo $saveallbutton;

    $dcolor3 = get_config('mod_diary', 'entrybgc');
    $dcolor4 = get_config('mod_diary', 'entrytextbgc');

    // Print a list of users who have completed at least one entry.
    if ($usersdone = diary_get_users_done($diary, $currentgroup, $sortoption)) {
        foreach ($usersdone as $user) {
            echo '<div class="entry" style="background: '.$dcolor3.'">';

            // Based on toolbutton and on list of users with at least one entry, print the entries on screen.
            echo results::diary_print_user_entry($course,
                                                 $diary,
                                                 $user,
                                                 $entrybyuser[$user->id],
                                                 $teachers,
                                                 $grades);
            echo '</div>';

            // Since the list can be quite long, add a save button after each entry that will save ALL visible changes.
            echo $saveallbutton;

            // Remove users who are done from our list of everyone so we finish with a list of users with no entries.
            unset($users[$user->id]);
        }
    }


    // List remaining users with no entries.
    foreach ($users as $user) {
        // 20210511 Changed to class.
        echo '<div class="entry" style="background: '.$dcolor3.'">';

        echo results::diary_print_user_entry($course,
                                             $diary,
                                             $user,
                                             null,
                                             $teachers,
                                             $grades);
        echo '</div><br>';
    }
    // 20210609 Check for empty list to prevent two sets of buttons at bottom of the report page.
    if ($users) {
        // Add a, Save all my feedback, button at the bottom of the page/list of users with no entries.
        echo $saveallbutton;
    }

    // End the page area where feedback and grades are added and will need to be saved.
    echo "</form>";
}

echo $OUTPUT->footer();
