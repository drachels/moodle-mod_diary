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
 * This page lists all the instances of diary in a particular course.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;

require_once(__DIR__ . "/../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Course.
$currentgroup = optional_param('currentgroup', 0, PARAM_INT); // Id of the current group (default to zero).

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

require_course_login($course);

if ($CFG->version > 2025041400) {
    \core_courseformat\activityoverviewbase::redirect_to_overview_page($id, 'diary');
}

// Header and page setup.
$PAGE->set_url('/mod/diary/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');

// Trigger course module instance list event.
$params = [
    'context' => context_course::instance($course->id),
];
\mod_diary\event\course_module_instance_list_viewed::create($params)->trigger();

// Print the header.
$strplural = get_string('modulenameplural', 'diary');
$PAGE->navbar->add($strplural);
$PAGE->set_title($strplural);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Fetch all Diary instances in the course.
if (!$diarys = get_all_instances_in_course('diary', $course)) {
    // No instances → renderer will handle empty message.
    $diarys = [];
}

// Determine if we should show the "View entries" column.
// (Only if user has manageentries capability in at least one Diary context.)
$showentriescolumn = false;
foreach ($diarys as $diary) {
    $context = context_module::instance($diary->coursemodule);
    if (has_capability('mod/diary:manageentries', $context)) {
        $showentriescolumn = true;
        break;
    }
}

// Prepare instances with extra data for the template.
$instances = [];
foreach ($diarys as $diary) {
    $context = context_module::instance($diary->coursemodule);

    $row = new stdClass();
    $row->name        = format_string($diary->name, true, ['context' => $context]);
    $row->viewurl     = (new moodle_url('/mod/diary/view.php', ['id' => $diary->coursemodule]))->out();
    $row->dimmed      = !$diary->visible;  // Used in template for class="dimmed" if needed.
    $row->intro       = format_module_intro('diary', $diary, $diary->coursemodule, false); // Short version.
    $row->summary     = format_text($diary->intro, $diary->introformat, ['context' => $context]);

    // Optional: section name (if course uses sections).
    $row->sectionname = '';
    if (course_format_uses_sections($course->format)) {
        $modinfo = get_fast_modinfo($course);
        if (isset($modinfo->get_sections()[$diary->section])) {
            $row->sectionname = get_section_name($course, $modinfo->get_section_info($diary->section));
        }
    }

    // Entries link (only shown if user can manage entries in this instance).
    $row->entrieslink = '';
    if (has_capability('mod/diary:manageentries', $context)) {
        $entrycount = results::diary_count_entries($diary, $currentgroup);
        $url = new moodle_url('/mod/diary/report.php', ['id' => $diary->coursemodule, 'action' => 'currententry']);
        $row->entrieslink = html_writer::link($url, get_string('viewallentries', 'diary', $entrycount));
    }

    $instances[] = $row;
}

// Render via renderer + Mustache.
$renderer = $PAGE->get_renderer('mod_diary');
echo $renderer->render_index_page($course, $instances, true, $showentriescolumn);

echo $OUTPUT->footer();
