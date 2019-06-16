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
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once("lib.php");
//require_once("locallib.php");

$id = required_param('id', PARAM_INT);   // Course module.
$action  = optional_param('action', '', PARAM_ACTION);  // Action(download, refresh page).

if (! $cm = get_coursemodule_from_id('diary', $id)) {
    print_error("Course Module ID was incorrect");
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error("Course module is misconfigured");
}
//print_object($id);
//print_object($id);
//print_object($id);
//print_object($id);
require_login($course, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/diary:manageentries', $context);


if (! $diary = $DB->get_record("diary", array("id" => $cm->instance))) {
    print_error("Course module is incorrect");
}
//print_object('Here is the data for $diary');
//print_object($diary);
// Handle download entries, previous day, and next day toolbuttons.
if (!empty($action)) {
    switch ($action) {
        case 'download':
            if (has_capability('mod/diary:manageentries', $context)) {
                //$d = $cm->instance; // Course module to download entries from.
                // Call download entries function in lib.
                download_entries($context, $course, $id, $diary);
            }
            break;
    }
}

// Header.
$PAGE->set_url('/mod/diary/report.php', array('id' => $id));

$PAGE->navbar->add(get_string("entries", "diary"));
$PAGE->set_title(get_string("modulenameplural", "diary"));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("entries", "diary"));

// Make some easy ways to access the latest entries.
// First, get all the entries from this diary activity.
if ($eee = $DB->get_records("diary_entries", array("diary" => $diary->id))) {
// Now, filter down to get the latest entry by any user who has made at least one entry.
    foreach ($eee as $ee) {
        $entrybyuser[$ee->userid] = $ee;
        $entrybyentry[$ee->id]  = $ee;
    }

} else {
    $entrybyuser  = array () ;
    $entrybyentry = array () ;
}

// Group mode
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

// Process incoming data if there is any.
if ($data = data_submitted()) {

    confirm_sesskey();

    $feedback = array();
    $data = (array)$data;

    // Peel out all the data from variable names.
    foreach ($data as $key => $val) {
        if (strpos($key, 'r') === 0 || strpos($key, 'c') === 0) {
            $type = substr($key,0,1);
            $num  = substr($key,1);
            $feedback[$num][$type] = $val;
        }
    }

    $timenow = time();
    $count = 0;
    foreach ($feedback as $num => $vals) {
        $entry = $entrybyentry[$num];
        // Only update entries where feedback has actually changed.
        $rating_changed = false;

        $studentrating = clean_param($vals['r'], PARAM_INT);
        $studentcomment = clean_text($vals['c'], FORMAT_PLAIN);

        if ($studentrating != $entry->rating && !($studentrating == '' && $entry->rating == "0")) {
            $rating_changed = true;
        }

        if ($rating_changed || $studentcomment != $entry->entrycomment) {
            $newentry = new StdClass();
            $newentry->rating       = $studentrating;
            $newentry->entrycomment = $studentcomment;
            $newentry->teacher      = $USER->id;
            $newentry->timemarked   = $timenow;
            $newentry->mailed       = 0;           // Make sure mail goes out (again, even)
            $newentry->id           = $num;
            if (!$DB->update_record("diary_entries", $newentry)) {
                notify("Failed to update the diary feedback for user $entry->userid");
            } else {
                $count++;
            }
            $entrybyuser[$entry->userid]->rating     = $studentrating;
            $entrybyuser[$entry->userid]->entrycomment    = $studentcomment;
            $entrybyuser[$entry->userid]->teacher    = $USER->id;
            $entrybyuser[$entry->userid]->timemarked = $timenow;

            $diary = $DB->get_record("diary", array("id" => $entrybyuser[$entry->userid]->diary));
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
    echo $OUTPUT->notification(get_string("feedbackupdated", "journal", "$count"), "notifysuccess");

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

// Print out the diary entries.
if ($currentgroup) {
    $groups = $currentgroup;
} else {
    $groups = '';
}

$users = get_users_by_capability($context, 'mod/diary:addentries', '', '', '', '', $groups);

if (!$users) {
    echo $OUTPUT->heading(get_string("nousersyet"));
} else {
    groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/diary/report.php?id=$cm->id");

    // Add download and refresh button for all entries in this diary.
    if (has_capability('mod/diary:manageentries', $context)) {
        $options = array();
        $options['id'] = $id;
        $options['diary'] = $diary->id;
        $options['action'] = 'download';
        $url = new moodle_url('/mod/diary/report.php', $options);
        // Add download button.
        $tools[] = html_writer::link($url, $OUTPUT->pix_icon('a/download_all'
                       , get_string('csvexport', 'diary'))
                       , array('class' => 'toolbutton'));

        // Add refresh toolbutton.
        $options{'action'} = 'refresh';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $tools[] = html_writer::link($url, $OUTPUT->pix_icon('t/reload'
                       , get_string('reload'))
                       , array('class' => 'toolbutton'));

        $options{'action'} = 'previousentry';
        $url = new moodle_url('/mod/diary/report.php', $options);
        $tools[] = html_writer::link($url, $OUTPUT->pix_icon('t/collapsed_rtl'
                       , get_string('previousentry', 'diary'))
                       , array('class' => 'toolbutton'));


        // This needs to become a string.
        echo 'Download or refresh page toolbar: ';
                                        
        echo $output = html_writer::alist($tools, array('id' => 'toolbar'));

        $d = $cm->instance; // Course module to download questions from.

        // Call download question function in lib.
        //echo 'The following is download diary entries:';
        //print_object($d);
        // $d = km,($d);
    }

    $grades = make_grades_menu($diary->grade);

    if (!$teachers = get_users_by_capability($context, 'mod/diary:manageentries')) {
        print_error('noentriesmanagers', 'diary');
    }
    // Start the page area where feedback and grades are added and will need to be saved.
    echo '<form action="report.php" method="post">';
    // Create a variable with all the info to save all my feedback, so it can be used multiple places.
    $saveallbutton = '';
    $saveallbutton =  "<p class=\"feedbacksave\">";
    $saveallbutton .=  "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
    $saveallbutton .=  "<input type=\"hidden\" name=\"sesskey\" value=\"" . sesskey() . "\" />";
    $saveallbutton .=  "<input type=\"submit\" value=\"".get_string("saveallfeedback", "journal")."\" />";
    $saveallbutton .=  "</p>";
    //echo "</form>";

	// Add save button at the top of the list of users with entries.
    echo $saveallbutton;

    // Get a list of user who have completed at least one entry.
    if ($usersdone = diary_get_users_done($diary, $currentgroup)) {
//print_object($usersdone);
        foreach ($usersdone as $user) {
            // Based on list of users with at least one entry, print the latest entry onscreen.
            echo diary_print_user_entry($course, $user, $entrybyuser[$user->id], $teachers, $grades);
            // Since the list can be quite long, add a save button after each entry that will save ALL visible changes.
            echo $saveallbutton;
//    echo toolbar($course, $user, $entrybyuser[$user->id]);
//print_object($entrybyuser[$user->id]);
            unset($users[$user->id]);
        }
    }

    // List remaining users with no entries.
    foreach ($users as $user) {
        echo diary_print_user_entry($course, $user, NULL, $teachers, $grades);
    }
    // Add a, Save all my feedback, button at the bottom of the page/list of users with no entries.
    echo $saveallbutton;
    // End the page area where feedback and grades are added and will need to be saved.
    echo "</form>";
}

echo $OUTPUT->footer();
