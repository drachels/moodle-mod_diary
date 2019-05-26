<?php

class backup_diary_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $diary = new backup_nested_element('diary', array('id'), array(
            'name', 'intro', 'introformat', 'days', 'grade', 'timemodified'));

        $entries = new backup_nested_element('entries');

        $entry = new backup_nested_element('entry', array('id'), array(
            'userid', 'modified', 'text', 'format', 'rating',
            'entrycomment', 'teacher', 'timemarked', 'mailed'));

        // diary -> entries -> entry
        $diary->add_child($entries);
        $entries->add_child($entry);

        // Sources
        $diary->set_source_table('diary', array('id' => backup::VAR_ACTIVITYID));

        if ($this->get_setting_value('userinfo')) {
            $entry->set_source_table('diary_entries', array('diary' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('user', 'teacher');

        // Define file annotations
        $diary->annotate_files('mod_diary', 'intro', null); // This file areas haven't itemid
        $entry->annotate_files('mod_diary_entries', 'text', null); // This file areas haven't itemid
        $entry->annotate_files('mod_diary_entries', 'entrycomment', null); // This file areas haven't itemid

        return $this->prepare_activity_structure($diary);
    }
}
