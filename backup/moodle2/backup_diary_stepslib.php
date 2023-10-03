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
 * Define all the backup steps that will be used by the backup_diary_activity_task
 *
 * @package mod_diary
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

/**
 * Define the complete diary structure for backup, with file and id annotations.
 *
 * @package mod_diary
 * @copyright 2020 AL Rachels <drachels@drachels.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_diary_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the complete data structure for backup, with file and id annotations
     *
     * @return void
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $diary = new backup_nested_element('diary',
            [
                'id',
            ],
            [
                'name',
                'intro',
                'introformat',
                'alwaysshowdescription',
                'days',
                'scale',
                'assessed',
                'assesstimestart',
                'assesstimefinish',
                'timemodified',
                'timeopen',
                'timeclose',
                'editall',
                'editdates',
                'entrybgc',
                'entrytextbgc',
                'enablestats',
                'teacheremail',
                'studentemail',
                'mincharacterlimit',
                'maxcharacterlimit',
                'minmaxcharpercent',
                'minwordlimit',
                'maxwordlimit',
                'minmaxwordpercent',
                'minsentencelimit',
                'maxsentencelimit',
                'minmaxsentpercent',
                'minparagraphlimit',
                'maxparagraphlimit',
                'minmaxparapercent',
                'enableautorating',
                'showtextstats',
                'textstatitems',
                'errorcmid',
                'errorpercent',
                'errorfullmatch',
                'errorcasesensitive',
                'errorignorebreaks',
            ]
        );

        $prompts = new backup_nested_element('prompts');
        $prompt = new backup_nested_element('prompt',
            [
                'id',
            ],
            [
                'diaryid',
                'datestart',
                'datestop',
                'text',
                'format',
                'minchar',
                'maxchar',
                'minmaxcharpercent',
                'minword',
                'maxword',
                'minmaxwordpercent',
                'minsentence',
                'maxsentence',
                'minmaxsentencepercent',
                'minparagraph',
                'maxparagraph',
                'minmaxparagraphpercent',
            ]
        );

        $entries = new backup_nested_element('entries');
        $entry = new backup_nested_element('entry',
            [
                'id',
            ],
            [
                'promptid',
                'userid',
                'timecreated',
                'timemodified',
                'text',
                'format',
                'rating',
                'entrycomment',
                'teacher',
                'timemarked',
                'mailed',
            ]
        );

        $tags = new backup_nested_element('entriestags');
        $tag = new backup_nested_element('tag',
            [
                'id',
            ],
            [
                'itemid',
                'rawname',
            ]
        );

        $ratings = new backup_nested_element('ratings');
        $rating = new backup_nested_element('rating',
            [
                'id',
            ],
            [
                'component',
                'ratingarea',
                'scaleid',
                'value',
                'userid',
                'timecreated',
                'timemodified',
            ]
        );

        // Build the tree.
        $diary->add_child($prompts);
        $prompts->add_child($prompt);

        $diary->add_child($entries);
        $entries->add_child($entry);

        $entry->add_child($ratings);
        $ratings->add_child($rating);

        $diary->add_child($tags);
        $tags->add_child($tag);

        // Define sources.
        $diary->set_source_table('diary', ['id' => backup::VAR_ACTIVITYID]);
        $prompt->set_source_table('diary_prompts', ['diaryid' => backup::VAR_ACTIVITYID]);

        // All the rest of elements only happen if we are including user info.
        if ($this->get_setting_value('userinfo')) {
            $entry->set_source_table('diary_entries', ['diary' => backup::VAR_PARENTID]);

            $rating->set_source_table('rating', ['contextid' => backup::VAR_CONTEXTID,
                                                    'itemid' => backup::VAR_PARENTID,
                                                    'component' => backup_helper::is_sqlparam('mod_diary'),
                                                    'ratingarea' => backup_helper::is_sqlparam('entry'),
                                                ]);

            $rating->set_source_alias('rating', 'value');

            if (core_tag_tag::is_enabled('mod_diary', 'diary_entries')) {
                $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                        FROM {tag} t
                                        JOIN {tag_instance} ti
                                          ON ti.tagid = t.id
                                       WHERE ti.itemtype = ?
                                         AND ti.component = ?
                                         AND ti.contextid = ?',
                    [
                        backup_helper::is_sqlparam('diary_entries'),
                        backup_helper::is_sqlparam('mod_diary'),
                        backup::VAR_CONTEXTID,
                    ]
                );
            }
        }

        // Define id annotations.
        $diary->annotate_ids('scale', 'scale');

        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('user', 'teacher'); // Not sure if this is needed.
        $entry->annotate_ids('promptid', 'promptid');

        $prompt->annotate_ids('diaryid', 'diaryid');

        $rating->annotate_ids('scale', 'scaleid');
        $rating->annotate_ids('user', 'userid');

        // Define file annotations.
        $diary->annotate_files('mod_diary', 'intro', null); // This file areas haven't itemid.
        $entry->annotate_files('mod_diary_entries', 'entry', 'id');
        $entry->annotate_files('mod_diary_entries', 'attachment', 'id');

        $entry->annotate_files('mod_diary_prompts', 'entry', 'id');
        $entry->annotate_files('mod_diary_prompts', 'attachment', 'id');

        return $this->prepare_activity_structure($diary);
    }
}
