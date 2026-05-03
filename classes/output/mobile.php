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

use mod_diary\local\diarystats;
use mod_diary\local\prompts;
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

        if (!isset($args['groupid']) && $canmanage && has_capability('moodle/site:accessallgroups', $context)) {
            $selectedgroup = 0;
        }

        $entrycount = results::diary_count_entries($diary, $selectedgroup);

        // Always default to the lighter landing view on first mobile open.
        // Load grading list only when explicitly requested (showgrading=1).
        $showgrading = $canmanage && $requestedgrading;

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
            'selectedgroupid' => (int)$selectedgroup,
            'selecteduserid' => 0,
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

        $managerlanding = $canmanage && !$showgrading;
        $data['managerlanding'] = $managerlanding;

        if ($managerlanding && $canadd && $isopen) {
            $latestentry = $DB->get_record_sql(
                "SELECT id
                   FROM {diary_entries}
                  WHERE diary = :diaryid AND userid = :userid
               ORDER BY timemodified DESC",
                ['diaryid' => $diary->id, 'userid' => $USER->id],
                IGNORE_MULTIPLE
            );
            if (!empty($latestentry->id)) {
                $data['hasmanagerlatestentry'] = true;
                $data['managerlatestentryid'] = (int)$latestentry->id;
            }
        }

        if ($showgrading) {
            $sortmode = (string)($args['sortmode'] ?? 'currententry');
            $validsortmodes = [
                'currententry' => 'Current entries',
                'firstentry' => 'First entries',
                'latestmodifiedentry' => 'Latest modified entry',
                'lowestgradeentry' => 'Lowest grade entry',
                'highestgradeentry' => 'Highest grade entry',
                'lastnameasc' => 'Name A-Z',
                'lastnamedesc' => 'Name Z-A',
            ];
            if (!array_key_exists($sortmode, $validsortmodes)) {
                $sortmode = 'currententry';
            }
            $data['selectedsortmode'] = $sortmode;
            $data['selectedsortlabel'] = $validsortmodes[$sortmode];
            $data['sortoptions'] = [];
            foreach ($validsortmodes as $optionid => $optionlabel) {
                $data['sortoptions'][] = [
                    'id' => $optionid,
                    'label' => $optionlabel,
                    'selected' => ($optionid === $sortmode),
                ];
            }

            $selecteduserid = max(0, (int)($args['userid'] ?? 0));
            $data['selecteduserid'] = $selecteduserid;
            $data['selectedusername'] = get_string('allparticipants');

            $usersort = ($sortmode === 'lastnamedesc')
                ? 'u.lastname DESC, u.firstname DESC'
                : 'u.lastname ASC, u.firstname ASC';
            $gradingusers = diary_get_users_done($diary, $selectedgroup, $usersort);
            $useroptions = [[
                'id' => 0,
                'name' => get_string('allparticipants'),
                'selected' => ($selecteduserid === 0),
            ]];
            if (!empty($gradingusers)) {
                foreach ($gradingusers as $gradinguser) {
                    $useroptions[] = [
                        'id' => (int)$gradinguser->id,
                        'name' => fullname($gradinguser),
                        'selected' => ((int)$gradinguser->id === $selecteduserid),
                    ];
                    if ((int)$gradinguser->id === $selecteduserid) {
                        $data['selectedusername'] = fullname($gradinguser);
                    }
                }
            }
            $data['hasuserselector'] = count($useroptions) > 1;
            $data['useroptions'] = $useroptions;

            $gradeoffset = max(0, (int)($args['gradeoffset'] ?? 0));
            $gradelimit = 5;
            $graderesult = self::build_teacher_submissions(
                $cm,
                $context,
                $course,
                $diary,
                $OUTPUT,
                $selectedgroup,
                $selecteduserid,
                $sortmode,
                $gradeoffset,
                $gradelimit
            );

            $data['submissions'] = $graderesult['items'];
            $totalgradingsubmissions = (int)$graderesult['total'];
            $activeoffset = (int)$graderesult['offset'];

            if ($totalgradingsubmissions > 0) {
                $rangefrom = $activeoffset + 1;
                $rangeto = min($activeoffset + count($graderesult['items']), $totalgradingsubmissions);
                $data['gradingsummary'] = 'Showing ' . $rangefrom . '-' . $rangeto . ' of ' . $totalgradingsubmissions;
            }

            if ($totalgradingsubmissions > $gradelimit) {
                $data['hasgradingpager'] = true;
                $data['hasgradingprev'] = ($activeoffset > 0);
                $data['hasgradingnext'] = ($activeoffset + $gradelimit < $totalgradingsubmissions);
                $data['gradingprevoffset'] = max(0, $activeoffset - $gradelimit);
                $data['gradingnextoffset'] = $activeoffset + $gradelimit;
            }
        }

        if (!$showgrading && $canadd && $isopen) {
            $entryoffset = max(0, (int)($args['entryoffset'] ?? 0));
            $entrylimit = 5;
            $entryresult = self::build_user_editable_entries($diary, $USER->id, $entryoffset, $entrylimit);

            $data['editableentries'] = $entryresult['items'];
            $data['haseditableentries'] = !empty($entryresult['items']);
            $totaleditableentries = (int)$entryresult['total'];
            $activeentryoffset = (int)$entryresult['offset'];

            if ($totaleditableentries > 0) {
                $rangefrom = $activeentryoffset + 1;
                $rangeto = min($activeentryoffset + count($entryresult['items']), $totaleditableentries);
                $data['editableentrysummary'] = 'Showing ' . $rangefrom . '-' . $rangeto . ' of ' . $totaleditableentries;
            }

            if ($totaleditableentries > $entrylimit) {
                $data['haseditablepager'] = true;
                $data['haseditableprev'] = ($activeentryoffset > 0);
                $data['haseditablenext'] = ($activeentryoffset + $entrylimit < $totaleditableentries);
                $data['editableprevoffset'] = max(0, $activeentryoffset - $entrylimit);
                $data['editablenextoffset'] = $activeentryoffset + $entrylimit;
            }
        }

        if (!$managerlanding) {
            self::populate_student_view($data, $cm, $context, $course, $diary, $USER->id, $OUTPUT);
            self::populate_mobile_prompt_context($data, $context, $diary, $USER->id);
        }

        $js = <<<'JS'
    var self = this;

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

this.findDiaryActionButton = function(e, selector) {
    try {
        if (e && typeof e.composedPath === 'function') {
            var path = e.composedPath();
            for (var i = 0; i < path.length; i++) {
                var node = path[i];
                if (!node) {
                    continue;
                }
                if (node.matches && node.matches(selector)) {
                    return node;
                }
                if (node.closest) {
                    var candidate = node.closest(selector);
                    if (candidate) {
                        return candidate;
                    }
                }
            }
        }

        var target = e ? e.target : null;
        return target && target.closest ? target.closest(selector) : null;
    } catch (err) {
        return null;
    }
};

this.getDiaryFeedbackForm = function(entryid) {
    if (!entryid) {
        return null;
    }
    return document.getElementById('diary-feedback-' + entryid);
};

this.getDiaryFeedbackEditor = function(form) {
    if (!form) {
        return null;
    }
    return form.querySelector('[data-role="feedback-visible"]');
};

this.getDiaryFeedbackValue = function(editor) {
    if (!editor) {
        return '';
    }

    if (editor.matches && editor.matches('textarea')) {
        return editor.value || '';
    }

    return editor.innerHTML || '';
};

this.setDiaryFeedbackValue = function(editor, value) {
    if (!editor) {
        return;
    }

    if (editor.matches && editor.matches('textarea')) {
        editor.value = value || '';
        return;
    }

    editor.innerHTML = value || '';
};

this.escapeDiaryFeedbackHtml = function(text) {
    if (!text) {
        return '';
    }

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

this.convertDiaryFeedbackTextToHtml = function(text) {
    var normalized = (text || '').replace(/\r\n?/g, '\n').trim();
    if (!normalized) {
        return '';
    }

    return self.escapeDiaryFeedbackHtml(normalized)
        .replace(/\n{2,}/g, '</p><p>')
        .replace(/\n/g, '<br>');
};

this.appendDiaryFeedbackHtml = function(current, addition) {
    var currenthtml = (current || '').trim();
    var additionhtml = (addition || '').trim();

    if (!additionhtml) {
        return currenthtml;
    }
    if (!currenthtml) {
        return additionhtml;
    }

    return currenthtml + '<p><br></p>' + additionhtml;
};

this.syncDiaryFeedbackField = function(form) {
    if (!form) {
        return;
    }

    var visible = self.getDiaryFeedbackEditor(form);
    var hidden = form.querySelector('[name="feedback"]');
    if (!visible) {
        return;
    }

    // Single-textarea mode: visible field is the named feedback field.
    if (visible === hidden) {
        return;
    }

    if (!hidden) {
        return;
    }

    hidden.value = self.getDiaryFeedbackValue(visible);
};

this.applyDiaryFeedbackAction = function(entryid, action) {
    var form = self.getDiaryFeedbackForm(entryid);
    if (!form || !action) {
        return false;
    }

    var editor = self.getDiaryFeedbackEditor(form);
    var seedinput = document.getElementById('diary-feedback-seed-' + entryid);
    var seedhtmlinput = document.getElementById('diary-feedback-seed-html-' + entryid);
    var renderedresults = document.getElementById('diary-results-block-' + entryid);
    var autoratinginput = document.getElementById('diary-autorating-value-' + entryid);
    if (!editor) {
        return false;
    }

    var seed = seedinput ? (seedinput.value || '').trim() : '';
    var seedhtml = seedhtmlinput ? (seedhtmlinput.value || '').trim() : '';
    var autorating = autoratinginput ? (autoratinginput.value || '').trim() : '';
    var current = self.getDiaryFeedbackValue(editor);

    var setGradeValue = function(form, value) {
        if (!form) {
            return;
        }
        var select = form.querySelector('select[data-role="grade-visible"]');
        if (!select) {
            return;
        }

        select.value = value;
        for (var i = 0; i < select.options.length; i++) {
            select.options[i].selected = (String(select.options[i].value) === String(value));
        }
        select.dispatchEvent(new Event('input', {bubbles: true}));
        select.dispatchEvent(new Event('change', {bubbles: true}));
    };

    if (action === 'clear') {
        self.setDiaryFeedbackValue(editor, '');
        setGradeValue(form, '-1');
    } else if (action === 'add') {
        var addcontent = seedhtml;

        if (!addcontent) {
            if (renderedresults) {
                addcontent = renderedresults.innerHTML || '';
            }
        }
        if (!addcontent) {
            addcontent = self.convertDiaryFeedbackTextToHtml(seed);
        }
        if (!addcontent) {
            if (renderedresults) {
                addcontent = self.convertDiaryFeedbackTextToHtml(
                    (renderedresults.innerText || renderedresults.textContent || '').trim()
                );
            }
        }
        if (!addcontent) {
            return false;
        }

        self.setDiaryFeedbackValue(editor, self.appendDiaryFeedbackHtml(current, addcontent));

        if (autorating !== '') {
            setGradeValue(form, autorating);
        }
    } else {
        return false;
    }

    self.syncDiaryFeedbackField(form);
    editor.dispatchEvent(new Event('input', {bubbles: true}));
    editor.dispatchEvent(new Event('change', {bubbles: true}));
    editor.focus();
    return false;
};

this.bindDiaryFeedbackActionButtons = function() {
    var handler = function(e) {
        try {
            var button = e && e.currentTarget ? e.currentTarget : null;
            if (!button) {
                return;
            }
            var entryid = button.getAttribute('data-entryid');
            var action = button.getAttribute('data-action');
            if (!entryid || !action) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            self.applyDiaryFeedbackAction(entryid, action);
        } catch (err) {
            // Ignore direct button binding failures.
        }
    };

    var buttons = document.querySelectorAll('ion-button.diary-feedback-action[data-entryid][data-action]');
    for (var i = 0; i < buttons.length; i++) {
        var button = buttons[i];
        if (!button || button.getAttribute('data-diary-bound') === '1') {
            continue;
        }
        button.setAttribute('data-diary-bound', '1');
        button.addEventListener('click', handler, true);
        button.addEventListener('touchend', handler, true);
    }
};

if (!window.modDiaryApplyFeedbackAction) {
    window.modDiaryApplyFeedbackAction = function(entryid, action) {
        if (self && typeof self.applyDiaryFeedbackAction === 'function') {
            return self.applyDiaryFeedbackAction(entryid, action);
        }
        return false;
    };
}

if (!window.modDiaryPrepareFeedbackSave) {
    window.modDiaryPrepareFeedbackSave = function(entryid) {
        if (self && typeof self.getDiaryFeedbackForm === 'function') {
            var form = self.getDiaryFeedbackForm(entryid);
            if (typeof self.syncDiaryFeedbackField === 'function') {
                self.syncDiaryFeedbackField(form);
            }
        }
        return true;
    };
}

this.bindDiaryAnchorCapture = function() {
    if (window.__modDiaryAnchorCaptureBound) {
        return;
    }
    window.__modDiaryAnchorCaptureBound = true;

    document.addEventListener('click', function(e) {
        try {
            var button = self.findDiaryActionButton(e, 'ion-button.diary-save-feedback[data-entryid]');
            if (!button) {
                return;
            }
            var entryid = button.getAttribute('data-entryid');
            if (entryid) {
                var form = self.getDiaryFeedbackForm(entryid);
                self.syncDiaryFeedbackField(form);
                window.localStorage.removeItem('mod_diary_last_anchor');
            }
        } catch (err) {
            // Ignore event-capture issues.
        }
    }, true);
};

this.bindDiaryFeedbackActions = function() {
    self.bindDiaryFeedbackActionButtons();

    if (window.__modDiaryFeedbackActionsBound) {
        return;
    }
    window.__modDiaryFeedbackActionsBound = true;

    document.addEventListener('click', function(e) {
        try {
            var button = self.findDiaryActionButton(e, 'ion-button.diary-feedback-action[data-entryid][data-action]');
            if (!button) {
                return;
            }

            var entryid = button.getAttribute('data-entryid');
            var action = button.getAttribute('data-action');
            if (!entryid || !action) {
                return;
            }
            self.applyDiaryFeedbackAction(entryid, action);
        } catch (err) {
            // Ignore button binding issues.
        }
    }, true);

    document.addEventListener('input', function(e) {
        try {
            var target = e && e.target ? e.target : null;
            if (!target || !target.matches || !target.matches('[data-role="feedback-visible"][data-entryid]')) {
                return;
            }

            var entryid = target.getAttribute('data-entryid');
            if (!entryid) {
                return;
            }

            self.syncDiaryFeedbackField(self.getDiaryFeedbackForm(entryid));
        } catch (err) {
            // Ignore input sync issues.
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
        $editpromptid = $entry ? (int)($entry->promptid ?? 0) : prompts::get_current_promptid($diary, $USER->id, 0);
        $data['promptid'] = $editpromptid;
        self::populate_mobile_prompt_context($data, $context, $diary, $USER->id, $editpromptid);
        self::populate_mobile_prompt_selector($data, $diary, $USER->id, $entry, $editpromptid);

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
     * Populate prompt and requirement context used by the mobile student pages.
     *
     * @param array $data Data passed to the mobile template.
     * @param \context_module $context Module context.
     * @param \stdClass $diary Diary record.
     * @param int $userid User id.
     * @param int $promptid Optional resolved/current prompt id.
     * @return void
     */
    protected static function populate_mobile_prompt_context(array &$data, $context, $diary, $userid, $promptid = 0) {
        global $DB;

        $resolvedpromptid = (int)$promptid;
        if ($resolvedpromptid <= 0) {
            $resolvedpromptid = prompts::get_current_promptid($diary, (int)$userid, 0);
        }

        [$tcount, $past, $current, $future] = prompts::diary_count_prompts($diary);
        $data['promptmode_summary'] = self::get_mobile_prompt_mode_label($diary);
        $data['promptcount_total'] = get_string('tcount', 'diary', $tcount);
        $data['promptcount_breakdown'] = get_string('promptinfo', 'diary', [
            'past' => $past,
            'current' => $current,
            'future' => $future,
        ]);

        $limitnotes = self::get_mobile_limit_notes($diary, $resolvedpromptid);
        if (!empty($limitnotes)) {
            $data['haslimitnotes'] = true;
            $data['limitnotes'] = array_map(function ($notehtml) {
                return ['html' => $notehtml];
            }, $limitnotes);
        }

        $metricrequirements = self::get_mobile_metric_requirement_items($diary);
        if (!empty($metricrequirements)) {
            $data['hasmetricrequirements'] = true;
            $data['metricrequirementstitle'] = get_string('completionmetricrequirements', 'diary');
            $data['metricrequirements'] = $metricrequirements;
        }

        if ($resolvedpromptid > 0) {
            $prompt = $DB->get_record('diary_prompts', [
                'id' => $resolvedpromptid,
                'diaryid' => $diary->id,
            ]);
            if ($prompt) {
                $prompttext = file_rewrite_pluginfile_urls(
                    (string)$prompt->text,
                    'pluginfile.php',
                    $context->id,
                    'mod_diary',
                    'prompt',
                    $prompt->id
                );
                $data['haspromptsummary'] = true;
                $data['prompttitle'] = trim((string)($prompt->title ?? '')) !== ''
                    ? format_string($prompt->title)
                    : rtrim(get_string('writingpromptlable2', 'diary')) . ' #' . (int)$prompt->id;
                $data['promptbody'] = format_text($prompttext, $prompt->format, ['context' => $context]);

                $dates = [];
                if (!empty($prompt->datestart)) {
                    $dates[] = userdate((int)$prompt->datestart);
                }
                if (!empty($prompt->datestop)) {
                    $dates[] = userdate((int)$prompt->datestop);
                }
                if (!empty($dates)) {
                    $data['promptdates'] = implode(' - ', $dates);
                }
            }
        }

        $data['hasmobilecontext'] = !empty($data['haspromptsummary'])
            || !empty($data['haslimitnotes'])
            || !empty($data['hasmetricrequirements'])
            || !empty($data['promptmode_summary'])
            || !empty($data['promptcount_total']);
    }

    /**
     * Return a human-readable prompt mode summary for mobile output.
     *
     * @param \stdClass $diary Diary record.
     * @return string
     */
    protected static function get_mobile_prompt_mode_label($diary) {
        global $DB;

        $promptmode = prompts::get_prompt_mode($diary);
        $total = (int)$DB->count_records('diary_prompts', ['diaryid' => $diary->id]);
        $required = max(0, min((int)($diary->requiredpromptcount ?? 0), $total));

        if ($promptmode === prompts::PROMPTMODE_CHOICECOMPLETE) {
            return get_string('coursetopiccurrentpromptmodechoicecomplete', 'diary', [
                'required' => $required,
                'total' => $total,
            ]);
        }

        if ($promptmode === prompts::PROMPTMODE_RANDOMCOMPLETE) {
            return get_string('coursetopiccurrentpromptmoderandomcomplete', 'diary', [
                'required' => $required,
                'total' => $total,
            ]);
        }

        $map = [
            prompts::PROMPTMODE_SEQUENTIAL => 'promptmodesequential',
            prompts::PROMPTMODE_CHOICE => 'promptmodechoice',
            prompts::PROMPTMODE_RANDOM => 'promptmoderandom',
            prompts::PROMPTMODE_COMPLETEALL => 'promptmodecompleteall',
        ];
        $modestring = $map[$promptmode] ?? 'promptmodesequential';

        return get_string('coursetopiccurrentpromptmode', 'diary', get_string($modestring, 'diary'));
    }

    /**
     * Build min/max and edit-limit note HTML for mobile pages.
     *
     * @param \stdClass $diary Diary record.
     * @param int $promptid Prompt id, when available.
     * @return string[]
     */
    protected static function get_mobile_limit_notes($diary, $promptid = 0) {
        global $DB;

        $notes = [];
        $prompt = null;
        if (!empty($promptid)) {
            $prompt = $DB->get_record('diary_prompts', ['id' => (int)$promptid, 'diaryid' => $diary->id]);
        }

        $limits = [
            ['field' => 'minchar', 'diaryfield' => 'mincharacterlimit', 'string' => 'mincharacterlimit_desc'],
            ['field' => 'maxchar', 'diaryfield' => 'maxcharacterlimit', 'string' => 'maxcharacterlimit_desc'],
            ['field' => 'minword', 'diaryfield' => 'minwordlimit', 'string' => 'minwordlimit_desc'],
            ['field' => 'maxword', 'diaryfield' => 'maxwordlimit', 'string' => 'maxwordlimit_desc'],
            ['field' => 'minsentence', 'diaryfield' => 'minsentencelimit', 'string' => 'minsentencelimit_desc'],
            ['field' => 'maxsentence', 'diaryfield' => 'maxsentencelimit', 'string' => 'maxsentencelimit_desc'],
            ['field' => 'minparagraph', 'diaryfield' => 'minparagraphlimit', 'string' => 'minparagraphlimit_desc'],
            ['field' => 'maxparagraph', 'diaryfield' => 'maxparagraphlimit', 'string' => 'maxparagraphlimit_desc'],
        ];

        foreach ($limits as $limit) {
            $value = 0;
            if ($prompt && isset($prompt->{$limit['field']}) && (int)$prompt->{$limit['field']} > 0) {
                $value = (int)$prompt->{$limit['field']};
            } else if (isset($diary->{$limit['diaryfield']}) && (int)$diary->{$limit['diaryfield']} > 0) {
                $value = (int)$diary->{$limit['diaryfield']};
            }

            if ($value > 0) {
                $notes[] = get_string($limit['string'], 'diary', $value);
            }
        }

        $editlimitnote = diarystats::get_edit_limit_note_html($diary, (int)$promptid);
        if ($editlimitnote !== '') {
            $notes[] = $editlimitnote;
        }

        return $notes;
    }

    /**
     * Build metric requirement summary items for mobile pages.
     *
     * @param \stdClass $diary Diary record.
     * @return array<int,array<string,string>>
     */
    protected static function get_mobile_metric_requirement_items($diary) {
        $requirements = \diary_get_metric_requirements((int)$diary->id);
        if (empty($requirements)) {
            return [];
        }

        $items = [];
        foreach ($requirements as $metric => $rule) {
            $operator = ((int)$rule['operator'] === 1) ? '<=' : '>=';
            $items[] = [
                'text' => get_string($metric, 'diary') . ' ' . $operator . ' ' . (string)$rule['value'],
            ];
        }

        return $items;
    }

    /**
     * Populate prompt selector data for mobile edit flows that allow choosing.
     *
     * @param array $data Template data.
     * @param \stdClass $diary Diary record.
     * @param int $userid User id.
     * @param \stdClass|null $entry Existing entry when editing.
     * @param int $selectedpromptid Current prompt id.
     * @return void
     */
    protected static function populate_mobile_prompt_selector(array &$data, $diary, $userid, $entry, $selectedpromptid) {
        global $DB;

        if (!empty($entry)) {
            return;
        }

        $promptmode = prompts::get_prompt_mode($diary);
        $supportedmodes = [
            prompts::PROMPTMODE_CHOICE,
            prompts::PROMPTMODE_COMPLETEALL,
            prompts::PROMPTMODE_CHOICECOMPLETE,
        ];
        if (!in_array($promptmode, $supportedmodes)) {
            return;
        }

        $prompts = $DB->get_records('diary_prompts', ['diaryid' => $diary->id], 'datestart ASC, datestop ASC, id ASC');
        if (empty($prompts)) {
            return;
        }

        $completedrecords = $DB->get_records_sql(
            "SELECT DISTINCT promptid
               FROM {diary_entries}
              WHERE diary = :diaryid AND userid = :userid AND promptid > 0",
            ['diaryid' => $diary->id, 'userid' => $userid]
        );
        $completedids = [];
        foreach ($completedrecords as $record) {
            $completedids[] = (int)$record->promptid;
        }

        if ($promptmode === prompts::PROMPTMODE_CHOICE) {
            $heading = get_string('promptmodepickerchoice', 'diary');
        } else if ($promptmode === prompts::PROMPTMODE_COMPLETEALL) {
            $heading = get_string('promptmodepickercompleteall', 'diary');
        } else {
            $required = min((int)($diary->requiredpromptcount ?? 0), count($prompts));
            $remainingrequired = max(0, $required - count($completedids));
            if ($remainingrequired <= 0) {
                return;
            }
            $heading = get_string('promptmodepickerchoicecomplete', 'diary', [
                'remaining' => $remainingrequired,
                'required' => $required,
            ]);
        }

        $options = [];
        foreach ($prompts as $prompt) {
            $promptid = (int)$prompt->id;
            if (self::get_mobile_prompt_date_status($prompt) !== 'open') {
                continue;
            }

            if (
                ($promptmode === prompts::PROMPTMODE_COMPLETEALL || $promptmode === prompts::PROMPTMODE_CHOICECOMPLETE)
                && in_array($promptid, $completedids)
            ) {
                continue;
            }

            $options[] = [
                'value' => $promptid,
                'label' => self::get_mobile_prompt_record_label($prompt),
                'selected' => ($promptid === (int)$selectedpromptid) ? 'selected' : '',
            ];
        }

        if (empty($options)) {
            return;
        }

        $data['haspromptselector'] = true;
        $data['promptselectorlabel'] = $heading;
        $data['promptoptions'] = $options;
    }

    /**
     * Return a compact prompt label for mobile displays.
     *
     * @param \stdClass|null $prompt Prompt record.
     * @return string
     */
    protected static function get_mobile_prompt_record_label($prompt) {
        if (empty($prompt)) {
            return '';
        }

        if (!empty($prompt->title)) {
            return clean_param(trim((string)$prompt->title), PARAM_TEXT);
        }

        $summary = trim(preg_replace('/\s+/', ' ', strip_tags((string)($prompt->text ?? ''))));
        if ($summary === '') {
            return '';
        }

        return shorten_text($summary, 90, true, '...');
    }

    /**
     * Return prompt availability status for mobile selector logic.
     *
     * @param \stdClass $prompt Prompt record.
     * @return string
     */
    protected static function get_mobile_prompt_date_status($prompt) {
        $now = time();
        if (!empty($prompt->datestart) && $now < (int)$prompt->datestart) {
            return 'future';
        }
        if (!empty($prompt->datestop) && $now > (int)$prompt->datestop) {
            return 'closed';
        }
        return 'open';
    }

    /**
     * Build a list of the current user's entries that can be opened for editing.
     *
     * @param \stdClass $diary Diary record.
     * @param int $userid User id.
     * @param int $offset Paging offset.
     * @param int $limit Paging limit.
     * @return array{items: array, total: int, offset: int}
     */
    protected static function build_user_editable_entries($diary, $userid, $offset = 0, $limit = 0) {
        global $DB;

        $offset = max(0, (int)$offset);
        $limit = max(0, (int)$limit);

        $total = (int)$DB->count_records('diary_entries', ['diary' => $diary->id, 'userid' => $userid]);
        if ($total <= 0) {
            return ['items' => [], 'total' => 0, 'offset' => 0];
        }

        if ($offset >= $total) {
            $offset = 0;
        }

        $entries = $DB->get_records_sql(
            "SELECT id, title, text, format, promptid, timecreated, timemodified
               FROM {diary_entries}
              WHERE diary = :diaryid AND userid = :userid
           ORDER BY timemodified DESC",
            ['diaryid' => $diary->id, 'userid' => $userid],
            $offset,
            $limit > 0 ? $limit : 0
        );

        if (empty($entries)) {
            return ['items' => [], 'total' => $total, 'offset' => $offset];
        }

        $promptids = [];
        foreach ($entries as $entry) {
            if (!empty($entry->promptid)) {
                $promptids[] = (int)$entry->promptid;
            }
        }

        $promptrecords = [];
        if (!empty($promptids)) {
            [$insql, $params] = $DB->get_in_or_equal(
                array_values(array_unique($promptids)),
                SQL_PARAMS_NAMED
            );
            $promptrecords = $DB->get_records_select('diary_prompts', 'id ' . $insql, $params, '', 'id,title,text');
        }

        $result = [];
        foreach ($entries as $entry) {
            $title = trim((string)$entry->title);
            if ($title === '') {
                $title = userdate((int)$entry->timemodified ?: (int)$entry->timecreated);
            }
            $title = clean_param($title, PARAM_TEXT);

            $plain = trim(html_to_text((string)$entry->text, 0, false));
            $plain = str_replace("\xc2\xa0", ' ', $plain);
            $plain = clean_param($plain, PARAM_TEXT);
            $plain = str_replace(['{', '}', '<', '>'], '', $plain);
            if (\core_text::strlen($plain) > 120) {
                $plain = \core_text::substr($plain, 0, 120) . '...';
            }

            $promptlabel = self::get_mobile_prompt_record_label($promptrecords[(int)$entry->promptid] ?? null);
            $promptlabel = clean_param((string)$promptlabel, PARAM_TEXT);

            $result[] = [
                'entryid' => (int)$entry->id,
                'title' => $title,
                'preview' => $plain,
                'timemodified' => userdate((int)$entry->timemodified ?: (int)$entry->timecreated),
                'promptlabel' => $promptlabel,
            ];
        }

        return ['items' => $result, 'total' => $total, 'offset' => $offset];
    }

    /**
     * Build teacher submissions list for mobile view.
     *
     * @param \cm_info|\stdClass $cm Course module.
     * @param \context_module $context Module context.
     * @param \renderer_base $output Renderer.
     * @param int $offset Paging offset.
     * @param int $limit Paging limit.
     * @return array{items: array, total: int, offset: int}
     */
    protected static function build_teacher_submissions(
        $cm,
        $context,
        $course,
        $diary,
        $output,
        $selectedgroup = 0,
        $selecteduserid = 0,
        $sortmode = 'currententry',
        $offset = 0,
        $limit = 0
    ) {
        global $DB;

        $submissions = [];
        $usersort = ($sortmode === 'lastnamedesc')
            ? 'u.lastname DESC, u.firstname DESC'
            : 'u.lastname ASC, u.firstname ASC';
        $users = diary_get_users_done($diary, $selectedgroup, $usersort);
        if (!empty($selecteduserid) && !empty($users)) {
            $selecteduserid = (int)$selecteduserid;
            $users = array_filter($users, function ($user) use ($selecteduserid) {
                return ((int)$user->id === $selecteduserid);
            });
        }
        $grades = make_grades_menu($diary->scale);

        $entryorder = 'timemodified DESC';
        switch ($sortmode) {
            case 'firstentry':
                $entryorder = 'timecreated ASC';
                break;
            case 'lowestgradeentry':
                // Show ungraded/low-grade entries first, oldest first for ties.
                $entryorder = 'rating ASC, timemodified ASC';
                break;
            case 'highestgradeentry':
                $entryorder = 'rating DESC, timemodified DESC';
                break;
            case 'latestmodifiedentry':
            case 'currententry':
            default:
                $entryorder = 'timemodified DESC';
                break;
        }

        if (!$users) {
            return ['items' => [], 'total' => 0, 'offset' => 0];
        }

        $users = array_values($users);
        $totalusers = count($users);
        $offset = max(0, (int)$offset);
        $limit = max(0, (int)$limit);
        if ($offset >= $totalusers) {
            $offset = 0;
        }
        if ($limit > 0) {
            $users = array_slice($users, $offset, $limit);
        }

        foreach ($users as $student) {
            $entry = $DB->get_record_sql(
                "SELECT *
                   FROM {diary_entries}
                  WHERE diary = :diaryid AND userid = :userid
                    ORDER BY {$entryorder}",
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
            $entrypreview = trim(html_to_text($entryhtml, 0, false));
            $entrypreview = str_replace("\xc2\xa0", ' ', $entrypreview);
            if (\core_text::strlen($entrypreview) > 2500) {
                $entrypreview = \core_text::substr($entrypreview, 0, 2500) . '...';
            }

            $feedbackplain = trim(html_to_text((string)$entry->entrycomment, 0, false));
            $feedbackplain = str_replace("\xc2\xa0", ' ', $feedbackplain);
            $tempentry = clone $entry;
            $statsdata = diarystats::get_diary_stats($tempentry, $diary);
            $comerrdata = diarystats::get_common_error_stats($tempentry, $diary);
            [$autoratingdata, $currentratingdata] = diarystats::get_auto_rating_stats($tempentry, $diary);

            $resultsblockhtml = trim((string)$statsdata . (string)$comerrdata . (string)$autoratingdata);

            $seedsource = (string)$resultsblockhtml;
            if ($seedsource !== '') {
                // Preserve row/column readability from results tables when inserted into textarea.
                $seedsource = preg_replace('/<\/?tbody\b[^>]*>/i', '', $seedsource);
                $seedsource = preg_replace('/<\/?thead\b[^>]*>/i', '', $seedsource);
                $seedsource = preg_replace('/<\/?table\b[^>]*>/i', '', $seedsource);
                $seedsource = preg_replace('/<\/t[dh]\s*>/i', ' | ', $seedsource);
                $seedsource = preg_replace('/<tr\b[^>]*>/i', "\n", $seedsource);
                $seedsource = preg_replace('/<\/tr\s*>/i', "\n", $seedsource);
                $seedsource = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])\b[^>]*>/i', "\n", $seedsource);
                $seedsource = html_entity_decode(strip_tags((string)$seedsource), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $seedsource = str_replace("\xc2\xa0", ' ', $seedsource);
                $seedsource = preg_replace('/[ \t]*\|[ \t]*/', ' | ', (string)$seedsource);
                $seedsource = preg_replace('/\s+\|\s*$/m', '', (string)$seedsource);
                $seedsource = preg_replace('/\n{3,}/', "\n\n", (string)$seedsource);
            }

            $feedbackseed = trim((string)$seedsource);
            if ($feedbackseed === '' && $resultsblockhtml !== '') {
                $feedbackseed = trim(html_to_text($resultsblockhtml, 0, false));
                $feedbackseed = str_replace("\xc2\xa0", ' ', $feedbackseed);
            }
            if ($feedbackseed === '') {
                $feedbackseed = $feedbackplain;
            }

            $gradeoptions = [];
            foreach ($grades as $value => $label) {
                $gradeoptions[] = [
                    'val' => $value,
                    'label' => $label,
                    'selected' => ((string)$value === (string)$entry->rating) ? 'selected' : '',
                ];
            }

            $autoratingvalue = '';
            if ($currentratingdata !== null && $currentratingdata !== '' && is_numeric($currentratingdata)) {
                $autoratingcandidate = (int)$currentratingdata;
                if (array_key_exists($autoratingcandidate, $grades)) {
                    $autoratingvalue = (string)$autoratingcandidate;
                }
            }

            $averagegradinginfo = grade_get_grades($course->id, 'mod', 'diary', $diary->id, [$student->id]);
            $averagegrade = '';
            if (!empty($averagegradinginfo->items[0]->grades[$student->id]->str_long_grade)) {
                $averagegrade = (string)$averagegradinginfo->items[0]->grades[$student->id]->str_long_grade;
            }

            $submissions[] = [
                'studentid' => $student->id,
                'studentname' => fullname($student),
                'studentpic' => $output->user_picture($student, ['size' => 35]),
                'timemodified' => userdate($entry->timemodified),
                'text' => format_text(s($entrypreview), FORMAT_HTML, ['context' => $context]),
                'hasresultsblock' => ($resultsblockhtml !== ''),
                'resultsblockhtml' => $resultsblockhtml,
                'entryid' => $entry->id,
                'gradeoptions' => $gradeoptions,
                'autorating_value' => $autoratingvalue,
                'rating_minus_one' => ((string)$entry->rating === '-1' || $entry->rating === null) ? 'selected' : '',
                'averagegrade' => $averagegrade,
                'feedback_raw' => (string)$entry->entrycomment,
                'feedback_seed' => $feedbackseed,
                'feedback_seed_html' => $resultsblockhtml,
            ];
        }

        return ['items' => $submissions, 'total' => $totalusers, 'offset' => $offset];
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

        if (!empty($entry->promptid)) {
            $prompt = $DB->get_record('diary_prompts', ['id' => (int)$entry->promptid], 'id,title,text');
            $promptlabel = self::get_mobile_prompt_record_label($prompt);
            if ($promptlabel !== '') {
                $data['entrypromptlabel'] = $promptlabel;
            }
        }

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
