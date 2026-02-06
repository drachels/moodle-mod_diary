<?php
// ... (header and namespace unchanged)

class entry_deleted extends \core\event\base {

    protected function init() {
        $this->data['crud']         = 'd';
        $this->data['edulevel']     = self::LEVEL_PARTICIPATING;
        $this->data['objecttable']  = 'diary_entries';
    }

    public static function get_name() {
        return get_string('evententrydeleted', 'mod_diary');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' deleted diary entry '{$this->objectid}' "
             . "belonging to user '{$this->relateduserid}' in the Diary activity "
             . "with course module id '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/diary/view.php', ['id' => $this->contextinstanceid]);
    }

    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' must be set to the deleted entry ID.');
        }
    }
}
