<?php

defined('MOODLE_INTERNAL') || die();

/**
 * diary conversion handler
 */
class moodle1_mod_diary_handler extends moodle1_mod_handler {

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'diary', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary',
                array(
                    'renamefields' => array(
                        'assessed' => 'grade'
                    )
                )
            ),
            new convert_path('entries', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary/ENTRIES'),
            new convert_path('entry', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary/ENTRIES/ENTRY'),
        );
    }

    public function process_diary($data) {

        // get the course module id and context id
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid);
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // we now have all information needed to start writing into the file
        $this->open_xml_writer("activities/diary_{$moduleid}/diary.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'diary', 'contextid' => $contextid));
        $this->xmlwriter->begin_tag('diary', array('id' => $instanceid));

        unset($data['id']);
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }

        return $data;
    }

    /**
     * This is executed when the parser reaches the <ENTRIES> opening element
     */
    public function on_entries_start() {
        $this->xmlwriter->begin_tag('entries');
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/DIARY/ENTRIES/ENTRY
     * data available
     */
    public function process_entry($data) {
        $this->write_xml('entry', $data, array('/entry/id'));
    }

    /**
     * This is executed when the parser reaches the closing </ENTRIES> element
     */
    public function on_entries_end() {
        $this->xmlwriter->end_tag('entries');
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'diary' path
     */
    public function on_diary_end() {

        $this->xmlwriter->end_tag('diary');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }
}
