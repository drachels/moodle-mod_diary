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
 * @package   mod_diary
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);                  // course id
$search = trim(optional_param('search', '', PARAM_NOTAGS));  // search string
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page
$showform = optional_param('showform', 0, PARAM_INT);   // Just show the form

$user    = trim(optional_param('user', '', PARAM_NOTAGS));    // Names to search for
$userid  = trim(optional_param('userid', 0, PARAM_INT));      // UserID to search for
$diaryid = trim(optional_param('diaryid', 0, PARAM_INT));      // DiaryID to search for
$subject = trim(optional_param('subject', '', PARAM_NOTAGS)); // Subject
$phrase  = trim(optional_param('phrase', '', PARAM_NOTAGS));  // Phrase
$words   = trim(optional_param('words', '', PARAM_NOTAGS));   // Words
$fullwords = trim(optional_param('fullwords', '', PARAM_NOTAGS)); // Whole words
$notwords = trim(optional_param('notwords', '', PARAM_NOTAGS));   // Words we don't want
$tags = optional_param_array('tags', [], PARAM_TEXT);

$timefromrestrict = optional_param('timefromrestrict', 0, PARAM_INT); // Use starting date
$fromday = optional_param('fromday', 0, PARAM_INT);      // Starting date
$frommonth = optional_param('frommonth', 0, PARAM_INT);      // Starting date
$fromyear = optional_param('fromyear', 0, PARAM_INT);      // Starting date
$fromhour = optional_param('fromhour', 0, PARAM_INT);      // Starting date
$fromminute = optional_param('fromminute', 0, PARAM_INT);      // Starting date
if ($timefromrestrict) {
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $gregorianfrom = $calendartype->convert_to_gregorian($fromyear, $frommonth, $fromday);
    $datefrom = make_timestamp($gregorianfrom['year'], $gregorianfrom['month'], $gregorianfrom['day'], $fromhour, $fromminute);
} else {
    $datefrom = optional_param('datefrom', 0, PARAM_INT);      // Starting date
}

$timetorestrict = optional_param('timetorestrict', 0, PARAM_INT); // Use ending date
$today = optional_param('today', 0, PARAM_INT);      // Ending date
$tomonth = optional_param('tomonth', 0, PARAM_INT);      // Ending date
$toyear = optional_param('toyear', 0, PARAM_INT);      // Ending date
$tohour = optional_param('tohour', 0, PARAM_INT);      // Ending date
$tominute = optional_param('tominute', 0, PARAM_INT);      // Ending date
if ($timetorestrict) {
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $gregorianto = $calendartype->convert_to_gregorian($toyear, $tomonth, $today);
    $dateto = make_timestamp($gregorianto['year'], $gregorianto['month'], $gregorianto['day'], $tohour, $tominute);
} else {
    $dateto = optional_param('dateto', 0, PARAM_INT);      // Ending date
}
$starredonly = optional_param('starredonly', false, PARAM_BOOL); // Include only favourites.

$PAGE->set_pagelayout('standard');
$PAGE->set_url($FULLME); //TODO: this is very sloppy --skodak

if (empty($search)) {   // Check the other parameters instead
    if (!empty($words)) {
        $search .= ' '.$words;
    }
    if (!empty($userid)) {
        $search .= ' userid:'.$userid;
    }
    if (!empty($diaryid)) {
        $search .= ' diaryid:'.$diaryid;
    }
    if (!empty($user)) {
        $search .= ' '.diary_clean_search_terms($user, 'user:');
    }
    if (!empty($subject)) {
        $search .= ' '.diary_clean_search_terms($subject, 'subject:');
    }
    if (!empty($fullwords)) {
        $search .= ' '.diary_clean_search_terms($fullwords, '+');
    }
    if (!empty($notwords)) {
        $search .= ' '.diary_clean_search_terms($notwords, '-');
    }
    if (!empty($phrase)) {
        $search .= ' "'.$phrase.'"';
    }
    if (!empty($datefrom)) {
        $search .= ' datefrom:'.$datefrom;
    }
    if (!empty($dateto)) {
        $search .= ' dateto:'.$dateto;
    }
    if (!empty($tags)) {
        $search .= ' tags:' . implode(',', $tags);
    }
    if (!empty($starredonly)) {
        $search .= ' starredonly:on';
    }
    $individualparams = true;
} else {
    $individualparams = false;
}

if ($search) {
    $search = diary_clean_search_terms($search);
}

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_course_login($course);

$params = array(
    'context' => $PAGE->context,
    'other' => array('searchterm' => $search)
);

$event = \mod_diary\event\course_searched::create($params);
$event->trigger();

$strdiaries = get_string("modulenameplural", "diary");
$strsearch = get_string("search", "diary");
$strsearchresults = get_string("searchresults", "diary");
$strpage = get_string("page");

if (!$search || $showform) {

    $PAGE->navbar->add($strdiaries, new moodle_url('/mod/diary/index.php', array('id'=>$course->id)));
    $PAGE->navbar->add(get_string('advancedsearch', 'diary'));

    $PAGE->set_title($strsearch);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();

    diary_print_big_search_form($course);
    echo $OUTPUT->footer();
    exit;
}

/// We need to do a search now and print results

$searchterms = str_replace('diaryid:', 'instance:', $search);
$searchterms = explode(' ', $searchterms);

$searchform = diary_search_form($course, $search);

$PAGE->navbar->add($strsearch, new moodle_url('/mod/diary/search.php', array('id'=>$course->id)));
$PAGE->navbar->add($strsearchresults);
if (!$entries = diary_search_entries($searchterms, $course->id, $page*$perpage, $perpage, $totalcount)) {
    $PAGE->set_title($strsearchresults);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdiaries, 2);
    echo $OUTPUT->heading($strsearchresults, 3);
    echo $OUTPUT->heading(get_string("noentries", "diary"), 4);

    if (!$individualparams) {
        $words = $search;
    }

    diary_print_big_search_form($course);

    echo $OUTPUT->footer();
    exit;
}

// Including this here to prevent it being included if there are no search results.
require_once($CFG->dirroot.'/rating/lib.php');

// Set up the ratings information that will be the same for all entries.
$ratingoptions = new stdClass();
$ratingoptions->component = 'mod_diary';
$ratingoptions->ratingarea = 'entry';
$ratingoptions->userid = $USER->id;
$ratingoptions->returnurl = $PAGE->url->out(false);
$rm = new rating_manager();

$PAGE->set_title($strsearchresults);
$PAGE->set_heading($course->fullname);
$PAGE->set_button($searchform);
echo $OUTPUT->header();
echo '<div class="reportlink">';

$params = [
    'id'        => $course->id,
    'user'      => $user,
    'userid'    => $userid,
    'diaryid'   => $diaryid,
    'subject'   => $subject,
    'phrase'    => $phrase,
    'words'     => $words,
    'fullwords' => $fullwords,
    'notwords'  => $notwords,
    'dateto'    => $dateto,
    'datefrom'  => $datefrom,
    'showform'  => 1,
    'starredonly' => $starredonly
];
$url    = new moodle_url("/mod/diary/search.php", $params);
foreach ($tags as $tag) {
    $url .= "&tags[]=$tag";
}
echo html_writer::link($url, get_string('advancedsearch', 'diary').'...');

echo '</div>';

echo $OUTPUT->heading($strdiaries, 2);
echo $OUTPUT->heading("$strsearchresults: $totalcount", 3);

$url = new moodle_url('search.php', array('search' => $search, 'id' => $course->id, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

//added to implement highlighting of search terms found only in HTML markup
//fiedorow - 9/2/2005
$strippedsearch = str_replace('user:','',$search);
$strippedsearch = str_replace('subject:','',$strippedsearch);
$strippedsearch = str_replace('&quot;','',$strippedsearch);
$searchterms = explode(' ', $strippedsearch);    // Search for words independently
foreach ($searchterms as $key => $searchterm) {
    if (preg_match('/^\-/',$searchterm)) {
        unset($searchterms[$key]);
    } else {
        $searchterms[$key] = preg_replace('/^\+/','',$searchterm);
    }
}
$strippedsearch = implode(' ', $searchterms);    // Rebuild the string
$entityfactory = mod_diary\local\container::get_entity_factory();
$vaultfactory = mod_diary\local\container::get_vault_factory();
$rendererfactory = mod_diary\local\container::get_renderer_factory();
$managerfactory = mod_diary\local\container::get_manager_factory();
$legacydatamapperfactory = mod_diary\local\container::get_legacy_data_mapper_factory();
$diarydatamapper = $legacydatamapperfactory->get_diary_data_mapper();

$discussionvault = $vaultfactory->get_discussion_vault();
$discussionids = array_keys(array_reduce($entries, function($carry, $entry) {
    $carry[$entry->discussion] = true;
    return $carry;
}, []));
$discussions = $discussionvault->get_from_ids($discussionids);
$discussionsbyid = array_reduce($discussions, function($carry, $discussion) {
    $carry[$discussion->get_id()] = $discussion;
    return $carry;
}, []);

$diaryvault = $vaultfactory->get_diary_vault();
$diaryids = array_keys(array_reduce($discussions, function($carry, $discussion) {
    $carry[$discussion->get_diary_id()] = true;
    return $carry;
}, []));
$diaries = $diaryvault->get_from_ids($diaryids);
$diariesbyid = array_reduce($diaries, function($carry, $diary) {
    $carry[$diary->get_id()] = $diary;
    return $carry;
}, []);

$entryids = array_map(function($entry) {
    return $entry->id;
}, $entries);

$entriestorender = [];

foreach ($entries as $entry) {

    // Replace the simple subject with the three items diary name -> thread name -> subject
    // (if all three are appropriate) each as a link.
    if (!isset($discussionsbyid[$entry->discussion])) {
        print_error('invaliddiscussionid', 'diary');
    }

    $discussion = $discussionsbyid[$entry->discussion];
    if (!isset($diariesbyid[$discussion->get_diary_id()])) {
        print_error('invaliddiaryid', 'diary');
    }

    $diary = $diariesbyid[$discussion->get_diary_id()];
    $capabilitymanager = $managerfactory->get_capability_manager($diary);
    $entryentity = $entityfactory->get_entry_from_stdclass($entry);

    if (!$capabilitymanager->can_view_entry($USER, $discussion, $entryentity)) {
        // Don't render entries that the user can't view.
        continue;
    }

    if ($entryentity->is_deleted()) {
        // Don't render deleted entries.
        continue;
    }

    $entriestorender[] = $entryentity;
}

$renderer = $rendererfactory->get_entries_search_results_renderer($searchterms);
echo $renderer->render(
    $USER,
    $diariesbyid,
    $discussionsbyid,
    $entriestorender
);

echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

echo $OUTPUT->footer();


 /**
  * Print a full-sized search form for the specified course.
  *
  * @param stdClass $course The Course that will be searched.
  * @return void The function prints the form.
  */
function diary_print_big_search_form($course) {
    global $PAGE, $words, $subject, $phrase, $user, $fullwords, $notwords, $datefrom,
           $dateto, $diaryid, $tags, $starredonly;

    $renderable = new \mod_diary\output\big_search_form($course, $user);
    $renderable->set_words($words);
    $renderable->set_phrase($phrase);
    $renderable->set_notwords($notwords);
    $renderable->set_fullwords($fullwords);
    $renderable->set_datefrom($datefrom);
    $renderable->set_dateto($dateto);
    $renderable->set_subject($subject);
    $renderable->set_user($user);
    $renderable->set_diaryid($diaryid);
    $renderable->set_tags($tags);
    $renderable->set_starredonly($starredonly);

    $output = $PAGE->get_renderer('mod_diary');
    echo $output->render($renderable);
}

/**
 * This function takes each word out of the search string, makes sure they are at least
 * two characters long and returns an string of the space-separated search
 * terms.
 *
 * @param string $words String containing space-separated strings to search for.
 * @param string $prefix String to prepend to the each token taken out of $words.
 * @return string The filtered search terms, separated by spaces.
 * @todo Take the hardcoded limit out of this function and put it into a user-specified parameter.
 */
function diary_clean_search_terms($words, $prefix='') {
    $searchterms = explode(' ', $words);
    foreach ($searchterms as $key => $searchterm) {
        if (strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        } else if ($prefix) {
            $searchterms[$key] = $prefix.$searchterm;
        }
    }
    return trim(implode(' ', $searchterms));
}

 /**
  * Retrieve a list of the diaries that this user can view.
  *
  * @param stdClass $course The Course to use.
  * @return array A set of formatted diary names stored against the diary id.
  */
function diary_menu_list($course)  {
    $menu = array();

    $modinfo = get_fast_modinfo($course);
    if (empty($modinfo->instances['diary'])) {
        return $menu;
    }

    foreach ($modinfo->instances['diary'] as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        $context = context_module::instance($cm->id);
        //if (!has_capability('mod/diary:viewdiscussion', $context)) {
        if (!has_capability('mod/diary:addentries', $context)) {
            continue;
        }
        $menu[$cm->instance] = format_string($cm->name);
    }

    return $menu;
}
