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
 * This file contains a renderer for various parts of the Diary module.
 *
 * @package   mod_diary
 * @copyright 2019 onwards AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * A custmom renderer class that extends the plugin_renderer_base and is used by the diary module.
 *
 * @package   mod_diary
 * @copyright 2019 onwards AL Rachels drachels@drachels.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_diary_renderer extends plugin_renderer_base {

    /**
     * Rendering diary files.
     * @var init $diary
     */
    private $diary;

    /**
     * Initialise internal objects.
     *
     * @param object $diary
     */
    public function init($diary) {
        $this->diary = $diary;
    }

    /**
     * Return introduction
     */
    public function introduction($diary, $cm) {
        $output = '';

        if (trim($diary->intro)) {
            $output .= $this->box_start('generalbox boxaligncenter', 'intro');
            $output .= format_module_intro('diary', $diary, $cm->id);
            $output .= $this->box_end();
        }
        return $output;
    }

    /**
     * Return the toolbar
     *
     * @param bool $shownew whether show "New round" button
     * return alist of links
     */
    public function toolbar($shownew = true) {
        $output = '';
        $toolbuttons = array();
        $roundp = new stdClass();
        $round = '';
        $roundn = '';
        $roundp = '';

        // Print export to .csv file toolbutton.
        if ($shownew) {
            $options = array();
            $options['id'] = $this->diary->cm->id;
            $options['action'] = 'download';
            $url = new moodle_url('/mod/diary/view.php', $options);
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('a/download_all'
                , get_string('csvexport', 'diary'))
                , array('class' => 'toolbutton'));
        }

        // Print prev/next round toolbuttons.
        if ($this->diary->get_prevround() != null) {
            $roundp = $this->diary->get_prevround()->id;
            $roundn = '';

            $url = new moodle_url('/mod/diary/view.php', array('id' => $this->diary->cm->id, 'round' => $roundp));
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/collapsed_rtl'
                , get_string('previousround', 'diary')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $this->pix_icon('t/collapsed_empty_rtl', '')
                , array('class' => 'dis_toolbutton'));
        }
        if ($this->diary->get_nextround() != null) {
            $roundn = $this->diary->get_nextround()->id;
            $roundp = '';

            $url = new moodle_url('/mod/diary/view.php', array('id' => $this->diary->cm->id, 'round' => $roundn));
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/collapsed'
                , get_string('nextround', 'diary')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $this->pix_icon('t/collapsed_empty', ''), array('class' => 'dis_toolbutton'));
        }

        // Print new round toolbutton.
        if ($shownew) {
            $options = array();
            $options['id'] = $this->diary->cm->id;
            $options['action'] = 'newround';
            $url = new moodle_url('/mod/diary/view.php', $options);
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/add'
                , get_string('newround', 'diary')), array('class' => 'toolbutton'));
        }

        // Print remove round toolbutton.
        if ($shownew) {
            $options = array();
            $options['id'] = $this->diary->cm->id;
            $options['action'] = 'removeround';
            $options['round'] = $this->diary->get_currentround()->id;
            $url = new moodle_url('/mod/diary/view.php', $options);
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/less'
            , get_string('removeround', 'diary')), array('class' => 'toolbutton'));
        }

        // Print refresh toolbutton.
        $url = new moodle_url('/mod/diary/view.php', array('id' => $this->diary->cm->id));
        $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/reload', get_string('reload')), array('class' => 'toolbutton'));

        // Return all available toolbuttons.
        $output .= html_writer::alist($toolbuttons, array('id' => 'toolbar'));
        return $output;
    }

    /**
     * Returns HTML for a diary inaccessible message.
     * Added 10/2/16
     * @param string $message
     * @return <type>
     */
    public function diary_inaccessible($message) {
        global $CFG;
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= $this->output->box_start('center');
        $output .= (get_string('notavailable', 'diary'));
        $output .= $message;
        $output .= $this->output->box('<a href="'.$CFG->wwwroot.'/course/view.php?id='
                . $this->page->course->id .'">'
                . get_string('returnto', 'diary', format_string($this->page->course->fullname, true))
                .'</a>', 'diarybutton standardbutton');
        $output .= $this->output->box_end();
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Prints the currently selected diary entry of student identified as $user.
     *
     * @param integer $course
     * @param integer $user
     * @param integer $entry
     * @param integer $teachers
     * @param integer $grades
     */
    public function diary_print_user_entry($course, $user, $entry, $teachers, $grades) {
        global $USER, $OUTPUT, $DB, $CFG;

        require_once($CFG->dirroot.'/lib/gradelib.php');

        echo "\n<table class=\"diaryuserentry\" id=\"entry-" . $user->id . "\">";

        echo "\n<tr>";
        echo "\n<td class=\"userpix\" rowspan=\"2\">";
        echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
        echo "</td>";
        echo "<td class=\"userfullname\">".fullname($user);
        if ($entry) {
            echo " <span class=\"lastedit\">".get_string("lastedited").": ".userdate($entry->timemodified)." </span>";
        }

        // Pass current course, user and entry to the toolbar function.
        echo toolbar($course, $user, $entry);

        echo "</td>";
        echo "</tr>";

        echo "\n<tr><td>";
        if ($entry) {
            //echo format_text($entry->text, $entry->format);
            //echo "<p>In lib.php, this is in the print user entry function.</p>";

            // Print toolbar.
            //echo $output->container_start("toolbar");
            //echo $output->toolbar(has_capability('mod/diary:manageentries', $context));
            //echo $output->container_end();

            echo diary_format_entry_text($entry, $course);

        } else {
            print_string("noentry", "diary");
        }
        echo "</td></tr>";

        if ($entry) {
            echo "\n<tr>";
            echo "<td class=\"userpix\">";
            if (!$entry->teacher) {
                $entry->teacher = $USER->id;
            }
            if (empty($teachers[$entry->teacher])) {
                $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
            }
            echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id, 'alttext' => true));
            echo "</td>";
            echo "<td>".get_string("feedback").":";

            $attrs = array();
            $hiddengradestr = '';
            $gradebookgradestr = '';
            $feedbackdisabledstr = '';
            $feedbacktext = $entry->entrycomment;

            // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
            $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));
            if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
                if ($gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked || $gradinginfo->items[0]->grades[$user->id]->overridden) {
                    $attrs['disabled'] = 'disabled';
                    $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
                    $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
                    $gradebooklink .= $gradinginfo->items[0]->grades[$user->id]->str_long_grade.'</a>';
                    $gradebookgradestr = '<br/>'.get_string("gradeingradebook", "diary").':&nbsp;'.$gradebooklink;

                    $feedbackdisabledstr = 'disabled="disabled"';
                    $feedbacktext = $gradinginfo->items[0]->grades[$user->id]->str_feedback;
                }
            }

            // Grade selector.
            $attrs['id'] = 'r' . $entry->id;
            echo html_writer::label(fullname($user)." ".get_string('grade'), 'r'.$entry->id, true, array('class' => 'accesshide'));
            echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
            echo $hiddengradestr;
            // Rewrote next three lines to show entry needs to be regraded due to resubmission.
            if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
                echo " <span class=\"lastedit\">".get_string("needsregrade", "diary"). "</span>";
            } else if ($entry->timemarked) {
                echo " <span class=\"lastedit\">".userdate($entry->timemarked)."</span>";
            }
            echo $gradebookgradestr;

            // Feedback text.
            echo html_writer::label(fullname($user)." ".get_string('feedback'), 'c'.$entry->id, true, array('class' => 'accesshide'));
            echo "<p><textarea id=\"c$entry->id\" name=\"c$entry->id\" rows=\"12\" cols=\"60\" $feedbackdisabledstr>";
            p($feedbacktext);
            echo "</textarea></p>";

            if ($feedbackdisabledstr != '') {
                echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
            }
            echo "</td></tr>";
        }
        echo "</table>\n";
    }

    /**
     * Print the teacher feedback.
     *
     */
    public function diary_print_feedback($course, $entry, $grades) {

        global $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot.'/lib/gradelib.php');

        if (! $teacher = $DB->get_record('user', array('id' => $entry->teacher))) {
            print_error('Weird diary error');
        }

        echo '<table class="feedbackbox">';

        echo '<tr>';
        echo '<td class="left picture">';
        echo $OUTPUT->user_picture($teacher, array('courseid' => $course->id, 'alttext' => true));
        echo '</td>';
        echo '<td class="entryheader">';
        echo '<span class="author">'.fullname($teacher).'</span>';
        echo '&nbsp;&nbsp;<span class="time">'.userdate($entry->timemarked).'</span>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="entrycontent">';

        echo '<div class="grade">';
        ///////////////////////////////////////////////////

        // Got it working, but it is showing 5 decimal places! REALLY need to figure out how
        // to make $gradinginfo come up with the right diary_entries record, instead of always
        // coming up with the last one.

        /////////////////////////////////////////////////////
        // Need to remove this Gradebook preference section and use it for the activity overall grade.
        // Will need to make my own individual entry preference.

        // Gradebook preference
        $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($entry->userid));

        //echo 'This is grading_info ';
        //print_object($gradinginfo);

        //echo 'This is course ';
        //print_object($course);
        //echo 'This is entry ';
        //print_object($entry);
        //echo 'This is grades ';
        //print_object($grades);

        //if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
        //    echo get_string('grade').': ';
        //    echo $gradinginfo->items[0]->grades[$entry->userid]->str_long_grade;
        //} else {
        //    print_string('nograde');
        //}
        //print_object($gradinginfo);

        // My preference.
        if (!empty($grades)) {
            echo get_string('grade').': ';
            echo $grades.'/'.number_format($gradinginfo->items[0]->grademax, 2);
        } else {
            print_string('nograde');
        }
        echo '</div>';

        // Feedback text.
        echo format_text($entry->entrycomment, FORMAT_PLAIN);
        echo '</td></tr></table>';
    }
}
