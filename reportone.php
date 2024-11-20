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
 * This page opens the current reportone instance of diary.
 *
 * @package   mod_diary
 * @copyright 2024 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->dirroot.'/rating/lib.php');

$id = required_param('id', PARAM_INT); // Course module.
$action = optional_param('action', 'oneentry', PARAM_ALPHANUMEXT); // Action(default to current entry).
$entryid = optional_param('entryid', '', PARAM_ALPHANUMEXT); // Action(default to current entry).
//$user = required_param('user', PARAM_INT); // User ID.
$user = optional_param('user', '', PARAM_INT); // User ID.

$debug;
$debug['ROnea $id; '] = $id;
$debug['ROneb $action; '] = $action;
$debug['ROnec $entryid; '] = $entryid;
$debug['ROned $user; '] = $user;


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
    $stringlable = 'reportoneentry';
    // Get ALL diary entries from this diary, for this user, from newest to oldest.
    $eee = $DB->get_record('diary_entries', ['id' => $entryid, 'diary' => $diary->id, 'userid' => $user]);
    $debug['ROnee $eee: '] = $eee;
}

// Handle toolbar capabilities.
if (! empty($action)) {
    switch ($action) {
        case 'oneentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'oneentry';
                // Get ALL diary entries in an order that will result in showing the users very first entry.
                $eee = $DB->get_record('diary_entries', ['id' => $entryid, 'diary' => $diary->id, 'userid' => $user]);
            }
            break;
        case 'download':
            if (has_capability('mod/diary:manageentries', $context)) {
                // Call download entries function in lib.php.
                // 20231007 Added set_url to fix error.
                $PAGE->set_url('/mod/diary/view.php', ['id' => $cm->id]);
                results::download_entries($context, $course, $diary);
            }
            break;
        case 'currententry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'currententry';
                // Get ALL diary entries in an order that will result in showing the users most current entry.
                $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user]);
            }
            break;
        case 'firstentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'firstentry';
                // Get ALL diary entries in an order that will result in showing the users very first entry.
                $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user], $sort = 'timecreated ASC');
            }
            break;
        case 'lowestgradeentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'lowestgradeentry';
                // Get ALL diary entries in an order that will result in showing the users
                // oldest, ungraded entry. Once all ungraded entries have a grade, the entry
                // with the lowest grade is shown. For duplicate low grades, the entry that
                // is oldest, is shown.
                $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user],
                    $sort = 'rating ASC, timemodified DESC');
            }
            break;
        case 'highestgradeentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'highestgradeentry';
                // Get ALL diary entries in an order that will result in showing the users highest
                // graded entry. Duplicates high grades result in showing the most recent entry.
                $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user], $sort = 'rating DESC');
            }
            break;
        case 'latestmodifiedentry':
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'latestmodifiedentry';
                // Get ALL diary entries in an order that will result in showing the users
                // most recently modified entry. At the moment, this is no different from current entry.
                // May be needed for future version if editing old entries is allowed.
                $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user], $sort = 'timemodified DESC');
            }
            break;
        default:
            if (has_capability('mod/diary:manageentries', $context)) {
                $stringlable = 'currententry';
            }
    }
}


// 20211214 Header with additional info in the url.
$PAGE->set_url('/mod/diary/reportone.php',
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

// Save our current user id and also get his details.
$users = $user;

$user = $DB->get_record('user', ['id' => $user]);

if ($eee) {
    // Organize data.
    $entrybyuser[$eee->userid] = $eee;
    $entrybyentry[$eee->id] = $eee;
    $entrybyuserentry[$eee->userid][$eee->id] = $eee;
} else {
    $entrybyuser = [];
    $entrybyentry = [];
}
print_object($debug);
//die;
// Process incoming data if there is any.
if ($data = data_submitted()) {
    
    print_object('printing $eee');
    print_object($eee);
    print_object('printing $cm');
    print_object($cm);
    print_object('printing $context');
    print_object($context);
    print_object('printing $diary');
    print_object($diary);
    print_object('printing $data');
    print_object($data);
    // Undefined variable.
    print_object('printing $entrybyuser');
    //print_object($entrybyuser);
    // Undefined variable.
    print_object('printing $entrybyentry');
    //print_object($entrybyentry);
    //die;
print_object($debug);

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

    $output = '';
    /*
    // The output for Groups and the Toolbar are NOT needed when grading one single entry accessed via Grade Me block.
    // Create download, reload, current, oldest, lowest, highest, and most recent tool buttons for all entries.
    if (has_capability('mod/diary:manageentries', $context)) {
        // 20201003 Changed toolbar code to $output instead of html_writer::alist.
        $options = [];
        $options['id'] = $id;
        $options['diary'] = $diary->id;
        $options['user'] = $users;

        // Add download button.
        $options['action'] = 'download';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('i/export', get_string('csvexport', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        // Add reload toolbutton.
        $options['action'] = $stringlable;
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/reload', get_string('reload', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'currententry';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('i/edit', get_string('currententry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'firstentry';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/left', get_string('firstentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'lowestgradeentry';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/down', get_string('lowestgradeentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'highestgradeentry';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/up', get_string('highestgradeentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'latestmodifiedentry';
        $url = new moodle_url('/mod/diary/reportone.php', $options);
        $output .= html_writer::link($url, $OUTPUT->pix_icon('t/right', get_string('latestmodifiedentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        // 20210511 Reorganized group and toolbar output. 20220102 Added action.
        echo '<span>'.groups_print_activity_menu($cm, $CFG->wwwroot."/mod/diary/reportone.php?id=$cm->id&action=currententry")
            .'</span><span style="float: right;">'.get_string('toolbar', 'diary').$output.'</span>';
    }
    */

    // Next line is different from Journal line 171.
    $grades = make_grades_menu($diary->scale);

    if (! $teachers = get_users_by_capability($context, 'mod/diary:manageentries')) {
        throw new moodle_exception(get_string('noentriesmanagers', 'diary'));
    }
    // 20211213 Start the page area where feedback and grades are added and will need to be saved.
    // 20230810 Changed due to pull request #29.
    //$url = new moodle_url('report.php', ['id' => $id, 'user' => $user->id, 'action' => 'allentries']);
    //$url = new moodle_url('report.php', ['id' => $id, 'diaryid' => $diary->id, 'action' => 'currententry']);
    // 20241001 Changed to remain on reportone.php page.
    $url = new moodle_url('reportone.php', ['id' => $id, 'diaryid' => $diary->id, 'action' => 'oneentry']);
    echo '<form action="'.$url->out(false).'" method="post">';
    // Create a variable with all the info to save all my feedback, so it can be used multiple places.
    // 20211210 Cleaned up unnecessary escaped double quotes.
    $saveallbutton = '';
    $saveallbutton = '<p class="feedbacksavereturn">';
    $saveallbutton .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
    $saveallbutton .= '<input type="hidden" name="sesskey" value="sesskey()" />';
    $saveallbutton .= '<input type="submit" class="btn btn-primary" style="border-radius: 8px" value="'
                      .get_string('saveallfeedback', 'diary').'" />';

    // phpcs:ignore
    /*
    $url = $CFG->wwwroot.'/mod/diary/reportone.php?id='.$id.'&user='.$user->id.'&action=allentries';
    // 20211210 Cleaned up unnecessary escaped double quotes.
    $saveallbutton .= ' <a href="'.$url.' class="feedbacksavestay">';
    $saveallbutton .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
    $saveallbutton .= '<input type="hidden" name="sesskey" value="sesskey()" />';
    $saveallbutton .= '<input type="submit" class="btn btn-primary" style="border-radius: 8px" value="'
                      .get_string('addtofeedback', 'diary').'"</a>';
    */

    //$url2 = new moodle_url($CFG->wwwroot.'/mod/diary/reportone.php', ['id' => $id, 'action' => 'currententry']);
    // 20240927 Added a return to report.php button if you do not want to save feedback.
    //$url2 = new moodle_url($CFG->wwwroot.'/mod/diary/report.php', ['id' => $id, 'action' => 'currententry']);
    // 20241001 Changed to remain on reportone.php page.
    $url2 = new moodle_url($CFG->wwwroot.'/mod/diary/reportone.php', ['id' => $id, 'action' => 'oneentry']);
    $saveallbutton .= ' <a href="'.$url2->out(true)
                     .'" class="btn btn-secondary" role="button" style="border-radius: 8px">'
                     .get_string('returntoreport', 'diary', $diary->name)
                     .'</a>';
    $saveallbutton .= "</p>";

    // Add save button at the top of the list of users with entries.
    echo $saveallbutton;
    // 20210705 Added new activity color setting. Only need to set the overall background color here.
    $dcolor3 = $diary->entrybgc;
    // 20210511 Changed to using class. 20240930 Added reportone.php to the class.
    echo '<div class="entry" style="background: '.$dcolor3.'">';
    // Based on the single selected user, print all their entries on screen.
    echo results::diary_print_user_entry($context,
                                         $course,
                                         $diary,
                                         $user,
                                         $eee,
                                         $teachers,
                                         $grades);
    echo '</div>';
    // Since the list can be quite long, add a save button after each entry that will save ALL visible changes.
    echo $saveallbutton;

    // End the page area where feedback and grades are added and will need to be saved.
    echo "</form>";
}

echo $OUTPUT->footer();
