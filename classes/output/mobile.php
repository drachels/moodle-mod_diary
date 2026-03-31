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

namespace mod_diary\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/diary/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/weblib.php');

use mod_diary\local\results;

/**
 * Mobile output class for the Moodle App.
 *
 * @package   mod_diary
 * @copyright 2026 AL Rachels
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Main Diary mobile page.
     *
     * @param array $args Incoming app args.
     * @return array
     */
    public static function mobile_course_view($args) {
        global $DB, $USER, $OUTPUT;

        $cmid = (int)($args['cmid'] ?? 0);
        $courseid = (int)($args['courseid'] ?? 0);

        $cm = get_coursemodule_from_id('diary', $cmid, 0, false, MUST_EXIST);
        $course = $courseid ? $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST)
            : $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $diary = $DB->get_record('diary', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        require_login($course, false, $cm);

        $canadd = has_capability('mod/diary:addentries', $context);
        $canmanage = has_capability('mod/diary:manageentries', $context);
        $requestedgrading = !empty($args['showgrading']);
        $groupmode = groups_get_activity_groupmode($cm);
        $selectedgroup = isset($args['groupid']) ? (int)$args['groupid'] : groups_get_activity_group($cm, true);
        $entrycount = results::diary_count_entries($diary, $selectedgroup);

        // If a user can both manage and add entries (typical teacher/admin),
        // default to personal entry view and allow switching to grading list.
        $showgrading = $canmanage && (!$canadd || $requestedgrading);

        [$isopen, $warning, $info] = self::get_open_status($diary, $course, $cm);

        $data = [
            'cmid' => $cmid,
            'courseid' => $course->id,
            'diaryid' => $diary->id,
            'name' => format_string($diary->name),
            'intro' => format_module_intro('diary', $diary, $cm->id),
            'warning' => $warning,
            'info' => $info,
            'canedit' => $canadd && $isopen,
            'canmanage' => $canmanage,
            'canadd' => $canadd,
            'showgrading' => $showgrading,
            'showstartnewentry' => !empty($diary->editdates),
            'showstartoredit' => empty($diary->editdates),
            'viewallentrieslabel' => get_string('viewallentries', 'diary', $entrycount),
            'indexurl' => (new \moodle_url('/mod/diary/index.php', ['id' => $course->id]))->out(false),
            'showgroupselector' => false,
            'selectedgroupname' => get_string('allparticipants'),
        ];

        if ($canmanage && $groupmode != NOGROUPS) {
            $groupoptions = [];
            $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);

            if ($canaccessallgroups) {
                $groupoptions[] = [
                    'id' => 0,
                    'name' => get_string('allparticipants'),
                    'selected' => ($selectedgroup === 0),
                ];
            }

            $groups = groups_get_all_groups($course->id, 0, $cm->groupingid, 'g.id, g.name');
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $groupoptions[] = [
                        'id' => (int)$group->id,
                        'name' => format_string($group->name),
                        'selected' => ((int)$group->id === $selectedgroup),
                    ];
                }
            }

            if (!empty($groupoptions)) {
                $data['showgroupselector'] = true;
                $data['groupoptions'] = $groupoptions;
                foreach ($groupoptions as $option) {
                    if (!empty($option['selected'])) {
                        $data['selectedgroupname'] = $option['name'];
                        break;
                    }
                }
            }
        }

        if ($showgrading) {
            $data['submissions'] = self::build_teacher_submissions($cm, $context, $diary, $OUTPUT, $selectedgroup);
        }

        self::populate_student_view($data, $cm, $context, $course, $diary, $USER->id, $OUTPUT);

        if (!$showgrading && $canadd && $isopen) {
            $data['editableentries'] = self::build_user_editable_entries($diary, $USER->id);
            $data['haseditableentries'] = !empty($data['editableentries']);
        }

        $js = <<<'JS'
this.scrollToSavedDiaryAnchor = function(retriesLeft) {
    var self = this;
    try {
        var targetid = window.localStorage.getItem('mod_diary_last_anchor');
        if (!targetid) {
            return;
        }

        var target = document.getElementById(targetid);
        if (!target) {
            target = document.querySelector('#' + targetid.replace(/([:.\[\],=])/g, '\\$1'));
        }

        if (!target) {
            if (retriesLeft > 0) {
                setTimeout(function() {
                    self.scrollToSavedDiaryAnchor(retriesLeft - 1);
                }, 150);
            }
            return;
        }

        if (target.scrollIntoView) {
            target.scrollIntoView({behavior: 'auto', block: 'start', inline: 'nearest'});
        }

        if (self.content && self.content.scrollToPoint && self.content.getScrollElement) {
            self.content.getScrollElement().then(function(scrollEl) {
                var top = target.getBoundingClientRect().top + scrollEl.scrollTop - 80;
                self.content.scrollToPoint(0, Math.max(0, top), 0);
            }).catch(function() {
                // Ignore content-scroll failures and keep native scrollIntoView result.
            });
        }

        window.localStorage.removeItem('mod_diary_last_anchor');
    } catch (e) {
        // Ignore storage/DOM issues in restricted app contexts.
    }
};

this.bindDiaryAnchorCapture = function() {
    if (window.__modDiaryAnchorCaptureBound) {
        return;
    }
    window.__modDiaryAnchorCaptureBound = true;

    document.addEventListener('click', function(e) {
        try {
            var target = e.target;
            var button = target && target.closest ? target.closest('ion-button.diary-save-feedback[data-entryid]') : null;
            if (!button) {
                return;
            }
            var entryid = button.getAttribute('data-entryid');
            if (entryid) {
                window.localStorage.setItem('mod_diary_last_anchor', 'rating-anchor-' + entryid);
            }
        } catch (err) {
            // Ignore event-capture issues.
        }
    }, true);
};

this.bindDiaryFeedbackActions = function() {
    if (window.__modDiaryFeedbackActionsBound) {
        return;
    }
    window.__modDiaryFeedbackActionsBound = true;

    document.addEventListener('click', function(e) {
        try {
            var target = e.target;
            var button = target && target.closest ? target.closest('ion-button.diary-feedback-action[data-entryid][data-action]') : null;
            if (!button) {
                return;
            }

            var entryid = button.getAttribute('data-entryid');
            var action = button.getAttribute('data-action');
            if (!entryid || !action) {
                return;
            }

            var form = document.getElementById('diary-feedback-' + entryid);
            if (!form) {
                return;
            }

            var textarea = form.querySelector('textarea[name="feedback"]');
            var seedinput = form.querySelector('input[name="feedbackseed"]');
            var seedhtmlinput = form.querySelector('textarea[name="feedbackseedhtml"]');
            if (!textarea) {
                return;
            }

            var seed = seedinput ? (seedinput.value || '').trim() : '';
            var seedhtml = seedhtmlinput ? (seedhtmlinput.value || '').trim() : '';
            var current = textarea.value || '';

            if (action === 'clear') {
                textarea.value = '';
            } else if (action === 'add') {
                var addcontent = seedhtml || seed;
                if (!addcontent) {
                    return;
                }
                if (!current.trim()) {
                    textarea.value = addcontent;
                } else if (current.indexOf(addcontent) === -1) {
                    textarea.value = current.replace(/\s+$/, '') + "\n\n" + addcontent;
                }
            }

            textarea.dispatchEvent(new Event('input', {bubbles: true}));
            textarea.dispatchEvent(new Event('change', {bubbles: true}));
            textarea.focus();
        } catch (err) {
            // Ignore button binding issues.
        }
    }, true);
};

this.ionViewWillEnter = function() {
    var self = this;
    this.bindDiaryAnchorCapture();
    this.bindDiaryFeedbackActions();
    Promise.resolve(this.refreshContent(false)).then(function() {
        setTimeout(function() {
            self.scrollToSavedDiaryAnchor(20);
        }, 200);
    });
};

this.ionViewDidEnter = function() {
    this.bindDiaryAnchorCapture();
    this.bindDiaryFeedbackActions();
    this.scrollToSavedDiaryAnchor(20);
};
JS;

        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template('mod_diary/mobileapp/mobile_view', $data),
            ]],
            'javascript' => $js,
            'otherdata' => json_encode([]),
        ];
    }

    /**
     * Student edit page rendered as a second mobile route.
     *
     * @param array $args Incoming app args.
     * @return array
     */
    public static function mobile_entry_edit($args) {
        global $DB, $USER, $OUTPUT;

        $cmid = (int)($args['cmid'] ?? 0);
        $courseid = (int)($args['courseid'] ?? 0);
        $requestedentryid = (int)($args['entryid'] ?? 0);

        $cm = get_coursemodule_from_id('diary', $cmid, 0, false, MUST_EXIST);
        $course = $courseid ? $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST)
            : $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $diary = $DB->get_record('diary', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        require_login($course, false, $cm);

        $canadd = has_capability('mod/diary:addentries', $context);
        [$isopen, $warning, $info] = self::get_open_status($diary, $course, $cm);

        if ($requestedentryid > 0) {
            $entry = $DB->get_record('diary_entries', [
                'id' => $requestedentryid,
                'diary' => $diary->id,
                'userid' => $USER->id,
            ]);

            if (!$entry) {
                throw new \moodle_exception('invalidaccess', 'diary');
            }
        } else {
            $entry = $DB->get_record_sql(
                "SELECT *
                   FROM {diary_entries}
                  WHERE diary = :diaryid AND userid = :userid
               ORDER BY timemodified DESC",
                ['diaryid' => $diary->id, 'userid' => $USER->id],
                IGNORE_MULTIPLE
            );

            // Match desktop edit.php 'currententry' behavior:
            // - if editdates is enabled, always start a new entry,
            // - otherwise start a new entry when the last one is from a previous day.
            if ($entry) {
                $newdaystarted = strtotime('today midnight') > (int)$entry->timecreated;
                if (!empty($diary->editdates) || $newdaystarted) {
                    $entry = null;
                }
            }
        }

        $textplain = '';
        $entryid = 0;
        if ($entry) {
            $entryid = (int)$entry->id;
            $entryhtml = file_rewrite_pluginfile_urls(
                (string)$entry->text,
                'pluginfile.php',
                $context->id,
                'mod_diary',
                'entry',
                $entry->id
            );
            $textplain = trim(html_to_text($entryhtml, 0, false));
            $textplain = str_replace("\xc2\xa0", ' ', $textplain);
        }

        $data = [
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'diaryid' => $diary->id,
            'name' => format_string($diary->name),
            'format' => FORMAT_MOODLE,
            'canedit' => $canadd && $isopen,
            'warning' => $warning,
            'info' => $info,
            'entryid' => $entryid,
            'text_plain' => $textplain,
        ];

        $js = <<<'JS'
this.onDiaryEntrySaved = function(result) {
    try {
        var entryid = parseInt(result && result.entryid ? result.entryid : 0, 10);
        if (!entryid) {
            return;
        }

        var form = document.querySelector('form[id^="diary-entry-form-"]');
        if (!form) {
            return;
        }

        var entryinput = form.querySelector('input[name="entryid"]');
        if (entryinput) {
            entryinput.value = String(entryid);
        }
    } catch (e) {
        // Ignore malformed payloads in older app builds.
    }
};
JS;

        return [
            'templates' => [[
                'id' => 'main',
                'html' => $OUTPUT->render_from_template('mod_diary/mobileapp/mobile_edit_entry', $data),
            ]],
            'javascript' => $js,
            'otherdata' => json_encode([]),
        ];
    }

    /**
     * Build a list of the current user's entries that can be opened for editing.
     *
     * @param \stdClass $diary Diary record.
     * @param int $userid User id.
     * @return array
     */
    protected static function build_user_editable_entries($diary, $userid) {
        global $DB;

        $entries = $DB->get_records_sql(
            "SELECT id, title, text, format, timecreated, timemodified
               FROM {diary_entries}
              WHERE diary = :diaryid AND userid = :userid
           ORDER BY timemodified DESC",
            ['diaryid' => $diary->id, 'userid' => $userid]
        );

        if (empty($entries)) {
            return [];
        }

        $result = [];
        foreach ($entries as $entry) {
            $title = trim((string)$entry->title);
            if ($title === '') {
                $title = userdate((int)$entry->timemodified ?: (int)$entry->timecreated);
            }

            $plain = trim(html_to_text((string)$entry->text, 0, false));
            $plain = str_replace("\xc2\xa0", ' ', $plain);
            if (\core_text::strlen($plain) > 120) {
                $plain = \core_text::substr($plain, 0, 120) . '...';
            }

            $result[] = [
                'entryid' => (int)$entry->id,
                'title' => $title,
                'preview' => $plain,
                'timemodified' => userdate((int)$entry->timemodified ?: (int)$entry->timecreated),
            ];
        }

        return $result;
    }

    /**
     * Build teacher submissions list for mobile view.
     *
     * @param \cm_info|\stdClass $cm Course module.
     * @param \context_module $context Module context.
     * @param \stdClass $diary Diary record.
     * @param \renderer_base $output Renderer.
     * @return array
     */
    protected static function build_teacher_submissions($cm, $context, $diary, $output, $selectedgroup = 0) {
        global $DB;

        $submissions = [];
        $users = diary_get_users_done($diary, $selectedgroup, 'u.lastname ASC, u.firstname ASC');
        $grades = make_grades_menu($diary->scale);

        if (!$users) {
            return [];
        }

        foreach ($users as $student) {
            $entry = $DB->get_record_sql(
                "SELECT *
                   FROM {diary_entries}
                  WHERE diary = :diaryid AND userid = :userid
               ORDER BY timemodified DESC",
                ['diaryid' => $diary->id, 'userid' => $student->id],
                IGNORE_MULTIPLE
            );

            if (!$entry) {
                continue;
            }

            $entryhtml = file_rewrite_pluginfile_urls(
                (string)$entry->text,
                'pluginfile.php',
                $context->id,
                'mod_diary',
                'entry',
                $entry->id
            );

            $feedbackplain = trim(html_to_text((string)$entry->entrycomment, 0, false));
            $feedbackplain = str_replace("\xc2\xa0", ' ', $feedbackplain);
            $feedbackseedhtml = (string)$entry->entrycomment;
            $feedbackformatted = format_text((string)$entry->entrycomment, FORMAT_HTML, ['context' => $context]);
            $hasfeedback = trim(strip_tags((string)$entry->entrycomment)) !== '';

            $gradeoptions = [];
            foreach ($grades as $value => $label) {
                $gradeoptions[] = [
                    'val' => $value,
                    'label' => $label,
                    'selected' => ((string)$value === (string)$entry->rating) ? 'selected' : '',
                ];
            }

            $submissions[] = [
                'studentid' => $student->id,
                'studentname' => fullname($student),
                'studentpic' => $output->user_picture($student, ['size' => 35]),
                'timemodified' => userdate($entry->timemodified),
                'text' => format_text($entryhtml, $entry->format, ['context' => $context]),
                'entryid' => $entry->id,
                'gradeoptions' => $gradeoptions,
                'rating_minus_one' => ((string)$entry->rating === '-1' || $entry->rating === null) ? 'selected' : '',
                'feedback_plain' => $feedbackplain,
                'feedback_seed' => $feedbackplain,
                'feedback_seed_html' => $feedbackseedhtml,
                'feedback_formatted' => $feedbackformatted,
                'has_feedback' => $hasfeedback,
            ];
        }

        return $submissions;
    }

    /**
     * Populate student entry + feedback data for mobile main template.
     *
     * @param array $data Data to update by reference.
     * @param \stdClass $cm Course module.
     * @param \context_module $context Module context.
     * @param \stdClass $course Course record.
     * @param \stdClass $diary Diary record.
     * @param int $userid User id.
     * @param \renderer_base $output Renderer.
     * @return void
     */
    protected static function populate_student_view(array &$data, $cm, $context, $course, $diary, $userid, $output) {
        global $DB;

        $entry = $DB->get_record_sql(
            "SELECT *
               FROM {diary_entries}
              WHERE diary = :diaryid AND userid = :userid
           ORDER BY timemodified DESC",
            ['diaryid' => $diary->id, 'userid' => $userid],
            IGNORE_MULTIPLE
        );

        if (!$entry) {
            $data['hasentry'] = false;
            return;
        }

        $data['hasentry'] = true;
        $data['lastedited'] = userdate($entry->timemodified ?: $entry->timecreated);

        $entrytext = file_rewrite_pluginfile_urls(
            (string)$entry->text,
            'pluginfile.php',
            $context->id,
            'mod_diary',
            'entry',
            $entry->id
        );
        $data['text'] = format_text($entrytext, $entry->format, ['context' => $context]);

        $hasfeedbacktext = trim(strip_tags((string)$entry->entrycomment)) !== '';
        $hasrating = $entry->rating !== null && $entry->rating !== '' && (string)$entry->rating !== '-1';

        if (!$hasfeedbacktext && !$hasrating) {
            return;
        }

        $data['hasfeedback'] = true;
        $data['feedbacktext'] = format_text((string)$entry->entrycomment, FORMAT_HTML, ['context' => $context]);

        if (!empty($entry->teacher) && ($teacher = $DB->get_record('user', ['id' => $entry->teacher]))) {
            $data['teachername'] = fullname($teacher);
            $data['teacherpic'] = $output->user_picture($teacher, ['size' => 35]);
            if (!empty($entry->timemarked)) {
                $data['feedbackdate'] = userdate($entry->timemarked);
            }
        }

        if ($hasrating) {
            $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $diary->id, [$userid]);
            if (!empty($gradinginfo->items[0]->grades[$userid]->str_long_grade)) {
                $data['grade'] = $gradinginfo->items[0]->grades[$userid]->str_long_grade;
            }
        }
    }

    /**
     * Return open status/warning text for Diary edit actions.
     *
     * @param \stdClass $diary Diary record.
     * @param \stdClass|null $course Course record.
     * @param \stdClass|null $cm Course module.
     * @return array
     */
    protected static function get_open_status($diary, $course = null, $cm = null) {
        global $DB;

        $timenow = time();
        $isopen = true;
        $warning = '';
        $info = '';

        // Match desktop view.php behavior for weekly courses using the Diary "days" setting.
        if (!empty($course) && !empty($cm) && !empty($diary->days) && !empty($course->format) && $course->format === 'weeks') {
            $section = $DB->get_record('course_sections', ['id' => $cm->section], 'id,section');
            if ($section) {
                $timestart = (int)$course->startdate + (((int)$section->section - 1) * WEEKSECS);
                $timefinish = $timestart + (DAYSECS * (int)$diary->days);

                if ($timenow < $timestart) {
                    $isopen = false;
                    $warning = get_string('notopenuntil', 'diary') . ': ' . userdate($timestart);
                } else if ($timenow > $timefinish) {
                    $isopen = false;
                    $warning = get_string('editingended', 'diary') . ': ' . userdate($timefinish);
                } else {
                    $isopen = true;
                    $info = get_string('editingends', 'diary') . ': ' . userdate($timefinish);
                }

                return [$isopen, $warning, $info];
            }
        }

        // Mirror desktop fallback: if diary is unavailable, use configured open/close timestamps.
        if (!results::diary_available($diary)) {
            $timestart = (int)($diary->timeopen ?? 0);
            $timefinish = (int)($diary->timeclose ?? 0);

            if ($timenow < $timestart) {
                $isopen = false;
                $warning = get_string('notopenuntil', 'diary') . ': ' . userdate($timestart);
            } else if ($timefinish && $timenow > $timefinish) {
                $isopen = false;
                $warning = get_string('editingended', 'diary') . ': ' . userdate($timefinish);
            } else if ($timefinish) {
                $isopen = true;
                $info = get_string('editingends', 'diary') . ': ' . userdate($timefinish);
            } else {
                // Desktop shows closed-state messaging once the window is not available.
                $isopen = false;
                $warning = get_string('editingended', 'diary');
            }

            return [$isopen, $warning, $info];
        }

        // Default: available with no explicit end window.
        if (!empty($diary->timeopen) && $timenow < $diary->timeopen) {
            $isopen = false;
            $warning = get_string('notopenuntil', 'diary') . ': ' . userdate($diary->timeopen);
        } else if (!empty($diary->timeclose) && $timenow > $diary->timeclose) {
            $isopen = false;
            $warning = get_string('editingended', 'diary') . ': ' . userdate($diary->timeclose);
        } else if (!empty($diary->timeclose)) {
            $isopen = true;
            $info = get_string('editingends', 'diary') . ': ' . userdate($diary->timeclose);
        }

        return [$isopen, $warning, $info];
    }
}
