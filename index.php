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
 * This page lists all the instances of diary in a particular course
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_diary\local\results;
require_once(__DIR__ . "/../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Course.
$currentgroup = optional_param('currentgroup', 0, PARAM_INT); // Id of the current group(default to zero).

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception(get_string('incorrectcourseid', 'diary'));
}

require_course_login($course);

// Header.
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
echo $OUTPUT->heading(format_string($strplural));

if (! $diarys = get_all_instances_in_course('diary', $course)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'diary')), '../../course/view.php?id=$course->id');
    die();
}

// Sections.
$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
}

$timenow = time();

// Table data.
$table = new html_table();

$table->head = [];
$table->align = [];
if ($usesections) {
    // Add column heading based on the course format. e.g. Week, Topic.
    $table->head[] = get_string('sectionname', 'format_' . $course->format);
    $table->align[] = 'left';
}
// Add activity, Name, and activity, Description, headings.
$table->head[] = get_string('name');
$table->align[] = 'left';
$table->head[] = get_string('description');
$table->align[] = 'left';

$currentsection = '';
$i = 0;

foreach ($diarys as $diary) {

    $context = context_module::instance($diary->coursemodule);
    $entriesmanager = has_capability('mod/diary:manageentries', $context);

    // Section.
    $printsection = '';
    if ($diary->section !== $currentsection) {
        if ($diary->section) {
            $printsection = get_section_name($course, $sections[$diary->section]);
        }
        if ($currentsection !== '') {
            $table->data[$i] = 'hr';
            $i ++;
        }
        $currentsection = $diary->section;
    }
    if ($usesections) {
        $table->data[$i][] = $printsection;
    }

    // Link.
    $diaryname = format_string($diary->name, true, ['context' => $context]);
    if (! $diary->visible) {
        // Show dimmed if the mod is hidden. 20230810 Changed based on pull rquest #29.
        $url = new moodle_url('view.php', ['id' => $diary->coursemodule]);
        $table->data[$i][] = '<a class="dimmed" href="'.$url->out(false).'">'.$diaryname.'</a>';
    } else {
        // Show normal if the mod is visible. 20230810 Changed based on pull rquest #29.
        $url = new moodle_url('view.php', ['id' => $diary->coursemodule]);
        $table->data[$i][] = '<a href="'.$url->out(false).'">'.$diaryname. '</a>';
    }

    // Description.
    $table->data[$i][] = format_text($diary->intro, $diary->introformat);

    // Entries info.
    if ($entriesmanager) {

        // Display the report.php col only if is a entries manager in some CONTEXT_MODULE.
        if (empty($managersomewhere)) {
            $table->head[] = get_string('viewentries', 'diary');
            $table->align[] = 'left';
            $managersomewhere = true;

            // Fill the previous col cells.
            $manageentriescell = count($table->head) - 1;
            for ($j = 0; $j < $i; $j ++) {
                if (is_array($table->data[$j])) {
                    $table->data[$j][$manageentriescell] = '';
                }
            }
        }
        // 20230810 Diary_1066 changed this to use $currentgroup.
        $entrycount = results::diary_count_entries($diary, $currentgroup);

        // 20220102 Added action to the href. 20230810 Changed based on pull request #29.
        $url = new moodle_url('report.php', ['id' => $diary->coursemodule, 'action' => 'currententry']);
        $table->data[$i][] = '<a href="'.$url->out(false).'">'
            .get_string('viewallentries', 'diary', $entrycount).'</a>';
    } else if (! empty($managersomewhere)) {
        $table->data[$i][] = "";
    }

    $i ++;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
