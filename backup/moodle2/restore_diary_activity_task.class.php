<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/diary/backup/moodle2/restore_diary_stepslib.php');

class restore_diary_activity_task extends restore_activity_task {

    protected function define_my_settings() {}

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
