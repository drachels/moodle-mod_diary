<?php

require_once($CFG->dirroot.'/mod/diary/backup/moodle2/backup_diary_stepslib.php');

class backup_diary_activity_task extends backup_activity_task {

    protected function define_my_settings() {}

    protected function define_my_steps() {
        $this->add_step(new backup_diary_activity_structure_step('diary_structure', 'diary.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/diary','#');

        $pattern = "#(".$base."\/index.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@diaryINDEX*$2@$', $content);

        $pattern = "#(".$base."\/view.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@diaryVIEWBYID*$2@$', $content);

        $pattern = "#(".$base."\/report.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@diaryREPORT*$2@$', $content);

        $pattern = "#(".$base."\/edit.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@diaryEDIT*$2@$', $content);

        return $content;
    }
}
