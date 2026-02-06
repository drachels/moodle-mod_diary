<?php
namespace mod_diary\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use stdClass;
use moodle_url;

class renderer extends plugin_renderer_base {

    /**
     * Renders the reportone page.
     *
     * @param stdClass $data Data for the template
     * @return string HTML
     */
    public function render_reportone(stdClass $data) {
        return $this->render_from_template('mod_diary/reportone', $data);
    }

    /**
     * Prepares context data for reportone.mustache.
     *
     * @param stdClass $cm Course module
     * @param stdClass $course Course
     * @param stdClass $diary Diary instance
     * @param stdClass $user User record
     * @param stdClass $entry Diary entry (or null)
     * @param array $teachers Teachers list (for feedback)
     * @param array $grades Grades menu
     * @return stdClass Template context
     */
    public function prepare_reportone_data($cm, $course, $diary, $user, $entry, $teachers, $grades) {
        global $USER;

        $context = \context_module::instance($cm->id);

        $data = new stdClass();
        $data->cmid        = $cm->id;
        $data->diaryname   = format_string($diary->name, true, ['context' => $context]);
        $data->entrybgc    = $diary->entrybgc ?? '#ffffff'; // Default white if unset
        $data->sesskey     = sesskey();
        $data->formaction  = (new moodle_url('/mod/diary/reportone.php', [
            'id' => $cm->id, 'user' => $user->id, 'entryid' => $entry ? $entry->id : 0, 'action' => 'currententry'
        ]))->out(false);

        // Back link to index.php (all diaries? or adjust if it's to report.php)
        $data->backlink = (object)[
            'url' => (new moodle_url('/mod/diary/index.php', ['id' => $course->id]))->out()
        ];

        $data->heading = $data->diaryname; // Or customize

        // Entry content
        if ($entry) {
            // Capture the output of diary_print_user_entry as raw HTML string
            ob_start();
            \mod_diary\local\results::diary_print_user_entry(
                $context, $course, $diary, $user, $entry, $teachers, $grades
            );
            $data->entryhtml = ob_get_clean();
            $data->hasentry = true;
        } else {
            $data->entryhtml = '';
            $data->hasentry  = false;
        }

        // Save/return buttons (shown if entry exists and capability allows)
        $data->savebuttons = has_capability('mod/diary:manageentries', $context);
        if ($data->savebuttons) {
            $data->returnlink = (object)[
                'url' => (new moodle_url('/mod/diary/report.php', ['id' => $cm->id, 'action' => 'currententry']))->out()
            ];
        }

        return $data;
    }
}