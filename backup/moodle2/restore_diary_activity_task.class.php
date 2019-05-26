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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/diary/backup/moodle2/restore_diary_stepslib.php');

class restore_diary_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }
    protected function define_my_steps() {
        $this->add_step(new restore_diary_activity_structure_step('diary_structure', 'diary.xml'));
    }

    static public function define_decode_contents() {

        $contents = array();
        $contents[] = new restore_decode_content('diary', array('intro'), 'diary');
        $contents[] = new restore_decode_content('diary_entries', array('text', 'entrycomment'), 'diary_entry');

        return $contents;
    }

    static public function define_decode_rules() {

        $rules = array();
        $rules[] = new restore_decode_rule('diaryINDEX', '/mod/diary/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('diaryVIEWBYID', '/mod/diary/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('diaryREPORT', '/mod/diary/report.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('diaryEDIT', '/mod/diary/edit.php?id=$1', 'course_module');

        return $rules;

    }

    public static function define_restore_log_rules() {

        $rules = array();
        $rules[] = new restore_log_rule('diary', 'view', 'view.php?id={course_module}', '{diary}');
        $rules[] = new restore_log_rule('diary', 'view responses', 'report.php?id={course_module}', '{diary}');
        $rules[] = new restore_log_rule('diary', 'add entry', 'edit.php?id={course_module}', '{diary}');
        $rules[] = new restore_log_rule('diary', 'update entry', 'edit.php?id={course_module}', '{diary}');
        $rules[] = new restore_log_rule('diary', 'update feedback', 'report.php?id={course_module}', '{diary}');

        return $rules;
    }

    public static function define_restore_log_rules_for_course() {

        $rules = array();
        $rules[] = new restore_log_rule('diary', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
