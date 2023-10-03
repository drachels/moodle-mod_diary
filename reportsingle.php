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
 * @copyright 2020 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot.'/rating/lib.php');

$id = required_param('id', PARAM_INT); // Course module.
$action = optional_param('action', 'currententry', PARAM_ACTION); // Action(default to current entry).
$user = required_param('user', PARAM_INT); // User ID.

if (!$cm = get_coursemodule_from_id('diary', $id)) {
    throw new moodle_exception(get_string('incorrectmodule', 'diary'));
}

if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/diary:manageentries', $context);

if (!$diary = $DB->get_record('diary', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('invalidid', 'diary'));
}

// 20201016 Get the name for this diary activity.
$diaryname = format_string($diary->name, true, ['context' => $context]);

// 20201014 Set a default sorting order for entry retrieval.
if ($sortoption = get_user_preferences('sortoption')) {
    $sortoption = get_user_preferences('sortoption');
} else {
    set_user_preference('sortoption', 'u.lastname ASC, u.firstname ASC');
    $sortoption = get_user_preferences('sortoption');
}

if (has_capability('mod/diary:manageentries', $context)) {
    $stringlable = 'reportsingleallentries';
    // Get ALL diary entries from this diary, for this user, from newest to oldest.
    $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user], $sort = 'timecreated DESC');
}

// 20211214 Header with additional info in the url.
$PAGE->set_url('/mod/diary/reportsingle.php',
    [
        'id' => $id,
        'user' => $user,
        'action' => $action,
    ]
);
$PAGE->navbar->add((get_string("rate", "diary")).' '.(get_string("entries", "diary")));
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($diaryname);

// 20201016 Added missing header label. 20210511 Changed to remove hard coded <h5>'s.
echo '<div>'.(get_string('sortorder', "diary"));
echo (get_string($stringlable, "diary"));

// 20200827 Added link to index.php page.
echo '<span style="float: right;"><a href="index.php?id='.$course->id.'">'
    .get_string('viewalldiaries', 'diary').'</a></span></div>';

// Save our current user id and also get his details. CHECK - might not need this.
$users = $user;
$user = $DB->get_record('user', ['id' => $user]);

if ($eee) {
    // Now, filter down to get entry by any user who has made at least one entry.
    foreach ($eee as $ee) {
        $entrybyuser[$ee->userid] = $ee;
        $entrybyentry[$ee->id] = $ee;
        $entrybyuserentry[$ee->userid][$ee->id] = $ee;
    }
} else {
    $entrybyuser = [];
    $entrybyentry = [];
}

// Process incoming data if there is any.
if ($data = data_submitted()) {
    results::diary_entries_feedback_update($cm, $context, $diary, $data, $entrybyuser, $entrybyentry);

    // Trigger module feedback updated event.
    $event = \mod_diary\event\feedback_updated::create(
        [
            'objectid' => $diary->id,
            'context' => $context,
        ]
    );
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();

} else {

    // Trigger module viewed event.
    $event = \mod_diary\event\entries_viewed::create(
        [
            'objectid' => $diary->id,
            'context' => $context,
        ]
    );
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('diary', $diary);
    $event->trigger();
}

if (! $users) {
    echo $OUTPUT->heading(get_string("nousersyet"));
} else {

    // Next line is different from Journal line 171.
    $grades = make_grades_menu($diary->scale);

    if (! $teachers = get_users_by_capability($context, 'mod/diary:manageentries')) {
        throw new moodle_exception(get_string('noentriesmanagers', 'diary'));
    }
    // 20211213 Start the page area where feedback and grades are added and will need to be saved.
    // 20230810 Changed due to pull request #29.
    $url = new moodle_url('reportsingle.php', ['id' => $id, 'user' => $user->id, 'action' => 'allentries']);
    echo '<form action="'.$url->out(false).'" method="post">';
    // Create a variable with all the info to save all my feedback, so it can be used multiple places.
    // 20211210 Cleaned up unnecessary escaped double quotes.
    $saveallbutton = '';
    $saveallbutton = '<p class="feedbacksavereturn">';
    $saveallbutton .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
    $saveallbutton .= '<input type="hidden" name="sesskey" value="sesskey()" />';
    $saveallbutton .= '<input type="submit" class="btn btn-primary" style="border-radius: 8px" value="'
                      .get_string('saveallfeedback', 'diary').'" />';

    // @codingStandardsIgnoreLine
    /*
    $url = $CFG->wwwroot.'/mod/diary/reportsingle.php?id='.$id.'&user='.$user->id.'&action=allentries';
    // 20211210 Cleaned up unnecessary escaped double quotes.
    $saveallbutton .= ' <a href="'.$url.' class="feedbacksavestay">';
    $saveallbutton .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
    $saveallbutton .= '<input type="hidden" name="sesskey" value="sesskey()" />';
    $saveallbutton .= '<input type="submit" class="btn btn-primary" style="border-radius: 8px" value="'
                      .get_string('addtofeedback', 'diary').'"</a>';
    */

    // 20211230 Tacked on an action for the return URL.
    // 20201222 Added a return to report.php button if you do not want to save feedback.
    // 20230810 Made changes based on pull request#29.
    $url2 = new moodle_url($CFG->wwwroot.'/mod/diary/report.php', ['id' => $id, 'action' => 'currententry']);
    $saveallbutton .= ' <a href="'.$url2->out(true)
                     .'" class="btn btn-secondary" role="button" style="border-radius: 8px">'
                     .get_string('returntoreport', 'diary', $diary->name)
                     .'</a>';

    $saveallbutton .= "</p>";

    // Add save button at the top of the list of users with entries.
    echo $saveallbutton;
    // 20210705 Added new activity color setting. Only need to set the overall background color here.
    $dcolor3 = $diary->entrybgc;
    foreach ($eee as $ee) {
        // 20210511 Changed to using class.
        echo '<div class="entry" style="background: '.$dcolor3.'">';
        // Based on the single selected user, print all their entries on screen.
        echo results::diary_print_user_entry($context,
                                             $course,
                                             $diary,
                                             $user,
                                             $ee,
                                             $teachers,
                                             $grades);
        echo '</div>';
        // Since the list can be quite long, add a save button after each entry that will save ALL visible changes.
        echo $saveallbutton;
    }

    // End the page area where feedback and grades are added and will need to be saved.
    echo "</form>";
}

echo $OUTPUT->footer();
