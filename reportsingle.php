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
require_once($CFG->dirroot . '/rating/lib.php');

$id = required_param('id', PARAM_INT); // Course module.
$action = optional_param('action', 'currententry', PARAM_ALPHANUMEXT); // Action(default to current entry).
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

// Handle toolbar capabilities.
if (! empty($action)) {
    switch ($action) {
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
                $eee = $DB->get_records(
                    'diary_entries',
                    ['diary' => $diary->id, 'userid' => $user],
                    $sort = 'rating ASC, timemodified DESC'
                );
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
$PAGE->set_url(
    '/mod/diary/reportsingle.php',
    [
        'id' => $id,
        'user' => $user,
        'action' => $action,
    ]
);
$PAGE->navbar->add((get_string("rate", "diary")) . ' ' . (get_string("entries", "diary")));
$PAGE->set_title($diaryname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($diaryname);

// 20201016 Added missing header label. 20210511 Changed to remove hard coded <h5>'s.
echo '<div>' . (get_string('sortorder', "diary"));
echo (get_string($stringlable, "diary"));

// 20200827 Added link to index.php page.
echo '<span style="float: right;"><a href="index.php?id=' . $course->id . '">'
    . get_string('viewalldiaries', 'diary') . '</a></span></div>';

// Save our current user id and also get his details. CHECK - might not need this.
$users = $user;
$user = $DB->get_record('user', ['id' => $user]);
$countnum = 0;

if ($eee) {
    // Now, filter down to get entry by any user who has made at least one entry.
    foreach ($eee as $ee) {
        $countnum++;
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

    // 20260215 Re-fetch entries from database to display updated values after feedback save.
    $eee = $DB->get_records('diary_entries', ['diary' => $diary->id, 'userid' => $user->id], 'timecreated DESC');
    if ($eee) {
        foreach ($eee as $ee) {
            $entrybyentry[$ee->id] = $ee;
            $entrybyuser[$ee->userid] = $ee;
        }
    }

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
    // Create download, reload, current, oldest, lowest, highest, and most recent tool buttons for all entries.
    if (has_capability('mod/diary:manageentries', $context)) {
        // 20201003 Changed toolbar code to $output instead of html_writer::alist.
        $options = [];
        $options['id'] = $id;
        $options['diary'] = $diary->id;
        $options['user'] = $users;

        // Add download button.
        $options['action'] = 'download';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('i/export', get_string('csvexport', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        // Add reload toolbutton.
        $options['action'] = $stringlable;
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/reload', get_string('reload', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'currententry';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('i/edit', get_string('currententry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'firstentry';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/left', get_string('firstentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'lowestgradeentry';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/down', get_string('lowestgradeentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'highestgradeentry';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/up', get_string('highestgradeentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        $options['action'] = 'latestmodifiedentry';
        $url = new moodle_url('/mod/diary/reportsingle.php', $options);
        $output .= html_writer::link(
            $url,
            $OUTPUT->pix_icon('t/right', get_string('latestmodifiedentry', 'diary')),
            [
                'class' => 'toolbutton',
            ]
        );

        // 20210511 Reorganized group and toolbar output. 20220102 Added action.
        echo '<span>' .
            groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/diary/reportsingle.php?id=$cm->id&action=currententry")
            . '</span><span style="float: right;">' .
            get_string('toolbar', 'diary') .
            $output .
            '</span>';
    }




    // Next line is different from Journal line 171.
    $grades = make_grades_menu($diary->scale);

    if (! $teachers = get_users_by_capability($context, 'mod/diary:manageentries')) {
        throw new moodle_exception(get_string('noentriesmanagers', 'diary'));
    }
    // 20211213 Start the page area where feedback and grades are added and will need to be saved.
    // 20230810 Changed due to pull request #29.
    $url = new moodle_url('reportsingle.php', ['id' => $id, 'user' => $user->id, 'action' => 'allentries']);
    echo '<form action="' . $url->out(false) . '" method="post" id="feedbackform">';
    // 20260215 Add hidden field to track last edited entry for scroll-back functionality.
    // This MUST be before the save button to only appear once.
    echo '<input type="hidden" name="last_edited_entry" id="last_edited_entry" value="" />';
    
    // Create a variable with all the info to save all my feedback, so it can be used multiple places.
    // 20211210 Cleaned up unnecessary escaped double quotes.
    $saveallbutton = '';
    $saveallbutton = '<p class="feedbacksavereturn">';
    $saveallbutton .= '<input type="hidden" name="id" value="' . $cm->id . '" />';
    $saveallbutton .= '<input type="hidden" name="sesskey" value="sesskey()" />';
    $saveallbutton .= '<input type="submit" class="btn btn-primary" style="border-radius: 8px" value="'
                      . get_string('saveallfeedback', 'diary') . '" />';

    // phpcs:ignore
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
    $url2 = new moodle_url($CFG->wwwroot . '/mod/diary/report.php', ['id' => $id, 'action' => 'currententry']);
    $saveallbutton .= ' <a href="' . $url2->out(true)
                     . '" class="btn btn-secondary" role="button" style="border-radius: 8px">'
                     . get_string('returntoreport', 'diary', $diary->name)
                     . '</a>';

    $saveallbutton .= "</p>";

    // Add save button at the top of the list of users with entries.
    echo $saveallbutton;
    // 20210705 Added new activity color setting. Only need to set the overall background color here.
    $dcolor3 = $diary->entrybgc;
    foreach ($eee as $ee) {
        // 20210511 Changed to using class.
        echo '<div class="entry" style="background: ' . $dcolor3 . '">';
        // Based on the single selected user, print all their entries on screen.
        echo results::diary_print_user_entry(
            $context,
            $course,
            $diary,
            $user,
            $ee,
            $teachers,
            $grades
        );
        echo '</div>';
        // Since the list can be quite long, add a save button after each entry that will save ALL visible changes.
        echo $saveallbutton;
    }

    // End the page area where feedback and grades are added and will need to be saved.
    echo "</form>";
}

// 20260211 New: If a button was clicked, output JS to scroll back to that entry's rating area.
global $SESSION;
if (isset($SESSION->diary_clicked_entry)) {
    echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var target = document.getElementById("rating-anchor-' . $SESSION->diary_clicked_entry . '");
            if (target) {
                // Auto scroll, align to top of viewport (but with margin via CSS)
                target.scrollIntoView({
                    behavior: "auto",   // Use "smooth" or "auto" for instant.
                    block: "start",       // Aligns target to top (good with scroll-margin-top).
                    inline: "nearest"
                });
            }
        });
    </script>';

    // Clear the session var to avoid repeating on next loads.
    unset($SESSION->diary_clicked_entry);
}

// 20260215 Added: Track which entry is being edited to enable scroll-back on save.
// 20260215 Improved: Detect which save button was clicked to determine scroll-back entry.
echo '<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        // Track the last edited entry from field changes.
        var lastEditedEntry = 0;
        
        // Listen for changes to rating fields.
        var ratingSelects = document.querySelectorAll("select[id^=\"r\"]");
        ratingSelects.forEach(function(select) {
            select.addEventListener("change", function() {
                var entryId = this.id.replace(/[^0-9]/g, "");
                if (entryId) {
                    lastEditedEntry = entryId;
                }
            });
        });
        
        // Listen for changes to all possible comment field types (textarea, input, contenteditable).
        var commentFields = document.querySelectorAll(
            "textarea[name^=\"c\"], input[name^=\"c\"], [name^=\"c\"][contenteditable]"
        );
        commentFields.forEach(function(field) {
            field.addEventListener("input", function() {
                var entryId = this.name.replace(/[^0-9]/g, "");
                if (entryId) {
                    lastEditedEntry = entryId;
                }
            });
            field.addEventListener("change", function() {
                var entryId = this.name.replace(/[^0-9]/g, "");
                if (entryId) {
                    lastEditedEntry = entryId;
                }
            });
        });
        
        // Find all input submit buttons with value "Save all my feedback" and track which one is clicked.
        var submitButtons = document.querySelectorAll("input[type=\"submit\"]");
        submitButtons.forEach(function(button) {
            button.addEventListener("click", function(e) {
                // If we have a lastEditedEntry from field tracking, use that.
                if (lastEditedEntry > 0) {
                    document.getElementById("last_edited_entry").value = lastEditedEntry;
                } else {
                    // Fallback: find the previous entry div before this button.
                    var currentElement = button;
                    var entryDiv = null;
                    
                    // Walk backwards through siblings and ancestors to find an entry div.
                    while (currentElement && !entryDiv) {
                        // Check previous siblings.
                        var prevSibling = currentElement.previousElementSibling;
                        while (prevSibling) {
                            if (prevSibling.classList && prevSibling.classList.contains("entry")) {
                                entryDiv = prevSibling;
                                break;
                            }
                            prevSibling = prevSibling.previousElementSibling;
                        }
                        
                        // If not found in siblings, go up to parent and try again.
                        if (!entryDiv) {
                            currentElement = currentElement.parentElement;
                        }
                    }
                    
                    // If we found the entry div, extract the entry ID from its anchor.
                    if (entryDiv) {
                        var anchors = entryDiv.querySelectorAll("[id^=\"rating-anchor-\"]");
                        if (anchors.length > 0) {
                            var anchorId = anchors[0].id; // rating-anchor-{id}
                            var entryIdFromAnchor = anchorId.replace(/[^0-9]/g, "");
                            if (entryIdFromAnchor) {
                                document.getElementById("last_edited_entry").value = entryIdFromAnchor;
                            }
                        }
                    }
                }
            });
        });
    });
</script>';

echo $OUTPUT->footer();
