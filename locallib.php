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
 * Diary module local lib functions.
 *
 *
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

/**
 * Returns diary entries tagged with a specified tag.
 *
 * This is a callback used by the tag area mod_diary/locallib to search for diary entries
 * tagged with a specific tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function mod_diary_get_tagged_entries($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    // Find items.
    global $OUTPUT, $USER;
    $perpage = $exclusivemode ? 20 : 5;

    // Build the SQL query.
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $query = "SELECT de.id, de.text, de.diary, de.userid,
                    cm.id AS cmid, c.id AS courseid, c.shortname, c.fullname, $ctxselect
                FROM {diary_entries} de
                JOIN {diary} d ON d.id = de.diary
                JOIN {modules} m ON m.name='diary'
                JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = d.id
                JOIN {tag_instance} tt ON de.id = tt.itemid
                JOIN {course} c ON cm.course = c.id
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :coursemodulecontextlevel
               WHERE tt.itemtype = :itemtype AND tt.tagid = :tagid AND tt.component = :component
                 AND cm.deletioninprogress = 0
                 AND de.id %ITEMFILTER% AND c.id %COURSEFILTER%";

    $params = [
        'itemtype' => 'diary_entries',
        'tagid' => $tag->id,
        'component' => 'mod_diary',
        'coursemodulecontextlevel' => CONTEXT_MODULE,
    ];

    if ($ctx) {
        $context = $ctx ? context::instance_by_id($ctx) : context_system::instance();
        $query .= $rec ? ' AND (ctx.id = :contextid OR ctx.path LIKE :path)' : ' AND ctx.id = :contextid';
        $params['contextid'] = $context->id;
        $params['path'] = $context->path.'/%';
    }

    $query .= " ORDER BY ";
    if ($fromctx) {
        // In order-clause specify that modules from inside "fromctx" context should be returned first.
        $fromcontext = context::instance_by_id($fromctx);
        $query .= ' (CASE WHEN ctx.id = :fromcontextid OR ctx.path LIKE :frompath THEN 0 ELSE 1 END),';
        $params['fromcontextid'] = $fromcontext->id;
        $params['frompath'] = $fromcontext->path.'/%';
    }
    $query .= ' c.sortorder, cm.id, de.id';

    $totalpages = $page + 1;

    // Use core_tag_index_builder to build and filter the list of items.
    // Notice how we search for 6 items when we need to display 5.
    // This way we will know that we need to display a link to the next page.
    $builder = new core_tag_index_builder('mod_diary', 'diary_entries', $query, $params, $page * $perpage, $perpage + 1);

    while ($item = $builder->has_item_that_needs_access_check()) {
        context_helper::preload_from_record($item);
        $courseid = $item->courseid;
        if (!$builder->can_access_course($courseid)) {
            $builder->set_accessible($item, false);
            continue;
        }
        $modinfo = get_fast_modinfo($builder->get_course($courseid));
        // Set accessibility of this item and all other items in the same course.
        $builder->walk(function ($taggeditem) use ($courseid, $modinfo, $builder) {
            if ($taggeditem->courseid == $courseid) {
                $accessible = false;
                if (($cm = $modinfo->get_cm($taggeditem->cmid)) && $cm->uservisible) {
                    if (empty($taggeditem->hidden)) {
                        $accessible = true;
                    } else {
                        $accessible = has_capability('mod/book:viewhiddenentries', context_module::instance($cm->id));
                    }
                }
                $builder->set_accessible($taggeditem, $accessible);
            }
        });
    }

    $items = $builder->get_items();
    if (count($items) > $perpage) {
        // We don't need exact page count, just indicate that the next page exists.
        $totalpages = $page + 2;
        array_pop($items);
    }

    // Build the display contents.
    if ($items) {
        $tagfeed = new core_tag\output\tagfeed();
        foreach ($items as $item) {
            $context = context_module::instance($item->cmid);
            $canmanage = has_capability('mod/diary:manageentries', $context);
            // 20230325 Show tagged item only if allowed to see it.
            // if (($item->userid == $USER->id) || has_capability('mod/diary:manageentries', $context)) {
            if (($item->userid == $USER->id) || $canmanage) {
                context_helper::preload_from_record($item);
                $modinfo = get_fast_modinfo($item->courseid);
                $cm = $modinfo->get_cm($item->cmid);
                // 20230325 Student go to their view page and teachers, etc. go to reportsingle for the applicable user.
                if ($canmanage) {
                    $pageurl = new moodle_url('/mod/diary/reportsingle.php',
                        [
                            'id' => $item->cmid,
                            'action' => 'allentries',
                            'user' => $item->userid,
                        ]
                    );
                } else {
                    $pageurl = new moodle_url('/mod/diary/view.php', ['id' => $item->cmid]);
                }
                // 20230308 Added this code to limit the amount of an entries text that is shown.
                $strtocut = $item->text;
                $strtocut = str_replace('\n', '<br>', $strtocut);
                if (strlen($strtocut) > 200) {
                    $strtocut = substr($strtocut, 0, 200).'...';
                }

                $pagename = format_string($strtocut, true, ['context' => context_module::instance($item->cmid)]);
                $pagename = html_writer::link($pageurl, $pagename);
                $courseurl = course_get_url($item->courseid, $cm->sectionnum);
                $cmname = html_writer::link($cm->url, $cm->get_formatted_name());
                $coursename = format_string($item->fullname, true, ['context' => context_course::instance($item->courseid)]);
                $coursename = html_writer::link($courseurl, $coursename);
                $icon = html_writer::link($pageurl, html_writer::empty_tag('img', ['src' => $cm->get_icon_url()]));
                $tagfeed->add($icon, $item->id.' '.$pagename, $cmname.'<br>'.$coursename);
            }
        }

        $content = $OUTPUT->render_from_template('core_tag/tagfeed', $tagfeed->export_for_template($OUTPUT));

        return new core_tag\output\tagindex($tag, 'mod_diary', 'diary_entries', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    }
}
