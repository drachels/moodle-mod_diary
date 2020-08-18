<?php
// Check to see how long it takes for a section of code to execute.
                for($i=0, $start = microtime(true); $i < 1000000; $i++) {
                    foreach ($entrys as $firstkey => $firstValue) {
                        break;
                    }
                }
                echo "foreach to get first key and value: " . (microtime(true) - $start) . " seconds <br />";



///////////////////////////////////////////
// This section of code was in edit.php from 0.9.3 on server 10.0.6.241.
// This first if section will need more else if's to take care of other sorts.
// Developed another method that lets me edit any record, but only save to currentrecord.

// My mod. Get all records for current user, instead of just one.
//$entrys = $DB->get_records("diary_entries", array("userid" => $USER->id, "diary" => $diary->id));
if ($action == 'firstentry') {
    echo 'Checking for Action and it is: ';
    print_object($action);    $entrys = $DB->get_records("diary_entries", $parameters, $sort='timecreated DESC');
} else if ($action == 'currententry') {
    echo 'Checking for Action and it is: ';
    print_object($action);
    $entrys = $DB->get_records("diary_entries", $parameters, $sort='timecreated ASC');
}

// If there are no entries for this user, start the first one.
if (! $entrys) {
    $data->entryid = null;
    $data->text = '';
    $data->textformat = FORMAT_HTML;
    $data->timecreated = time();
} else if ($entrys) {
    // Get the latest user entry.
    foreach ($entrys as $entry) {
        $data->entryid = $entry->id;
        $data->tempid = $entry->id;
        $data->text = $entry->text;
        $data->textformat = $entry->format;
        $data->timecreated = $entry->timecreated;
    }

    // If new calendar day, start a new entry.
    if ((strtotime('today midnight') > $entry->timecreated) && ($action == 'currententry')) {
        $entrys = '';
        $data->entryid = null;
        $data->tempid = null;
        $data->text = '';
        $data->textformat = FORMAT_HTML;
        $data->timecreated = time();
    }
}


///////////////////////////////////////////////////











///////////////////////////travis.yml from
// https://github.com/learnweb/moodle-tool_lifecycle/blob/master/.travis.yml
language: php

sudo: false
dist: trusty

cache:
  directories:
    - $HOME/.composer/cache
    - $HOME/.npm

addons:
  postgresql: "9.4"

php:
  - 7.0
  - 7.1

env:
  matrix:
   - DB=pgsql MOODLE_BRANCH=MOODLE_34_STABLE
   - DB=pgsql MOODLE_BRANCH=MOODLE_35_STABLE
   - DB=pgsql MOODLE_BRANCH=MOODLE_36_STABLE
   - DB=pgsql MOODLE_BRANCH=MOODLE_37_STABLE
   - DB=pgsql MOODLE_BRANCH=master
   - DB=mysqli MOODLE_BRANCH=MOODLE_34_STABLE
   - DB=mysqli MOODLE_BRANCH=MOODLE_35_STABLE
   - DB=mysqli MOODLE_BRANCH=MOODLE_36_STABLE
   - DB=mysqli MOODLE_BRANCH=MOODLE_37_STABLE
   - DB=mysqli MOODLE_BRANCH=master

matrix:
 allow_failures:
  - env: DB=pgsql MOODLE_BRANCH=master
  - env: DB=mysqli MOODLE_BRANCH=master
 exclude:
  - php: 7.0
    env: DB=pgsql MOODLE_BRANCH=MOODLE_37_STABLE
  - php: 7.0
    env: DB=mysqli MOODLE_BRANCH=MOODLE_37_STABLE
  - php: 7.0
    env: DB=pgsql MOODLE_BRANCH=master
  - php: 7.0
    env: DB=mysqli MOODLE_BRANCH=master
 fast_finish: true

before_install:
  - phpenv config-rm xdebug.ini
  - nvm install 8.9.4
  - cd ../..
  - composer selfupdate
  - composer create-project -n --no-dev --prefer-dist blackboard-open-source/moodle-plugin-ci ci ^2
  - export PATH="$(cd ci/bin; pwd):$(cd ci/vendor/bin; pwd):$PATH"

jobs:
  include:
    # Prechecks against latest Moodle stable only.
    - stage: static
      php: 7.1
      env: DB=mysqli MOODLE_BRANCH=MOODLE_37_STABLE
      install:
      - moodle-plugin-ci install --no-init
      script:
      - moodle-plugin-ci phplint
      - moodle-plugin-ci phpcpd
      - moodle-plugin-ci phpmd
      - moodle-plugin-ci codechecker
      - moodle-plugin-ci savepoints
      - moodle-plugin-ci mustache
      - moodle-plugin-ci grunt
      - moodle-plugin-ci validate
    # Smaller build matrix for development builds
    - stage: develop
      php: 7.1
      env: DB=mysqli MOODLE_BRANCH=MOODLE_37_STABLE
      install:
      - moodle-plugin-ci install
      script:
      - moodle-plugin-ci phpunit --coverage-clover
      - moodle-plugin-ci behat

# Unit tests and behat tests against full matrix.
install:
  - moodle-plugin-ci install
  -
script:
  - moodle-plugin-ci phpunit --coverage-clover
  - moodle-plugin-ci behat
after_success:
  - bash <(curl -s https://codecov.io/bash)

stages:
  - static
  - name: develop
    if: branch != master AND (type != pull_request OR head_branch != master) AND (tag IS blank)
  - name: test
if: branch = master OR (type = pull_request AND head_branch = master) OR (tag IS present)





/////////////////////////////////////////////////////////////////////////////////////////
// took these out of the function diary_print_user_entry and will try moving
// them into report.php where I get all the diary entries anyway.
//$prevday = '';

    //print_object($entry);
if ($entry) {
    //$entryc = new stdClass();
    $ids = array_keys($eee);

    if ($eee = $DB->get_records("diary_entries", array("diary" => $entry->diary, "userid" => $user->id))) {

    foreach($eee as $eeerow) {
            if ($eeerow->userid == $user->id && array_key_exists($entry->id, $eee)) {
                //echo 'We have a match!';
                //echo $eeerow['id']; // can't do this.
               // print_object($eeerow->id);
                $entryc = $eeerow; // Current entry.
                $ids = array_keys($eee);  // Get the table id's of all entries in $eee for current user.
               // echo 'We have an ids of:';
               // print_object($ids);
                // Search previous entries.
                $currentkey = array_search($entry->id, $ids);  // Pick out the currentkey containing our entry id.
               // echo 'We have a currentkey of:';
               // print_object($currentkey);
                if (array_key_exists($currentkey - 1, $ids)) {
                    $prevday = $eee[$ids[$currentkey - 1]];
                  //  echo 'Our prevday is:';
                   // print_object($prevday);
                } else {
                    $prevday = null;
                   // echo 'Our prevday is null!<br>';
                }
                // Search next day.
                if (array_key_exists($currentkey + 1, $ids)) {
                    $nextday = $eee[$ids[$currentkey + 1]];
                   // echo 'Our nextday is:';
                   // print_object(nextday);
                } else {
                    $nextday = null;
                   // echo 'Our nextday is null!<br>';

                }
                //echo 'We have an entryc of:';
               // print_object($entryc);  // This is the current, or last made, entry.
            }
        }
    }
               // echo 'We have an ids of:';
               // print_object($ids);
                $xxx[$entry->userid] = $ids;
               // print_object($xxx[$entry->userid]);
               // echo 'The following table belongs to user ';
               // print_object($entry->userid);
               // print_object($xxx);

}

/////////////////////////////////////////////////////////////////////
// The code above here is working. BUT, instead of using $eee being 
// brought into the function by item 7, I am probably going to have to use
// the $eee below here that gets records for JUST one user at a time.
/////////////////////////////////////////////////////////////////////

//    if ($eee = $DB->get_records("diary_entries", array("diary" => $entry->diary, "userid" => $user->id))) {
//        print_object($entry->diary);
//        print_object($entry->userid);
//        print_object($user->id);

//    print_object($eee);
    


   // $toolbuttons = array();
   // $entryp = new stdClass();
   // $entryc = '4'; // Entry currently looking at.
   // $entryn = ''; // Date next after entryc.
   // $entryp = '5'; // Date previous to entryc.




//if ($user->id = '2') {
//    print_object($user->id);
//    $entry = $eee[101];
//    print_object($entry);
//}

//print_object($entrybyentry[$user->userid]);
//print_object($eee);
//print_object($user->id);
//foreach($eee as $ee) {
//print_object($ee);

//    if ($key->userid = $user->id) {
       // print_object($key->userid);
      //  print_object($user->id);
//    }
//}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// THIS WORKS
$date1 = new DateTime(date('Y-m-d G:i:s', $entry->timemodified));
print_object($date1);
$date2 = new DateTime(date('Y-m-d G:i:s', $entry->timecreated));
print_object($date2);
$diff = date_diff($date1, $date2);
print_object($diff->i);
///////////////
date(('Y m d'),($entry->timemodified-$entry->timecreated))
(date('Y m d h', ($entry->timemodified+86400))- date('Y m d h', ($entry->timecreated)))

date_diff(('Y m d h', $entry->timemodified), ('Y m d h', $entry->timecreated))

    /**
     * Return the toolbar
     *
     * @param bool $shownew whether show "New round" button
     * return alist of links
     */
    function toolbar($shownew = true) {
        $output = '';
        $toolbuttons = array();
        $datep = new stdClass();
        $datec = '';
        $daten = '';
        $datep = '';


        // Print prev/next datec toolbuttons.
        if ($diary->get_preventry() != null) {
            $datep = $diary->get_preventry()->id;
            $roundn = '';

            $url = new moodle_url('/mod/diary/xreport.php', array('id' => $diary->cm->id, 'datec' => $datep));
            $toolbuttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/collapsed_rtl'
                , get_string('previousround', 'diary')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $OUTPUT->pix_icon('t/collapsed_empty_rtl', '')
                , array('class' => 'dis_toolbutton'));
        }
        if ($diary->get_nextentry() != null) {
            $roundn = $diary->get_nextentry()->id;
            $datep = '';

            $url = new moodle_url('/mod/diary/view.php', array('id' => $diary->cm->id, 'datec' => $roundn));
            $toolbuttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/collapsed'
                , get_string('nextround', 'diary')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $OUTPUT->pix_icon('t/collapsed_empty', ''), array('class' => 'dis_toolbutton'));
        }



        // Add refresh toolbutton.
        $options{'action'} = 'refresh';
        $url = new moodle_url('/mod/diary/xreport.php', $options);
        $tools[] = html_writer::link($url, $OUTPUT->pix_icon('t/reload'
                       , get_string('reload'))
                       , array('class' => 'toolbutton'));

        echo 'Download or refresh page toolbar: ';
        echo $output = html_writer::alist($tools, array('id' => 'toolbar'));





        // Print refresh toolbutton.
        $url = new moodle_url('/mod/diary/view.php', array('id' => $this->diary->cm->id));
        $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/reload', get_string('reload')), array('class' => 'toolbutton'));

        // Return all available toolbuttons.
        $output .= html_writer::alist($toolbuttons, array('id' => 'toolbar'));
        return $output;
    }



/**
 * Prints the currently selected diary entry of student identified as $user, on the report page.
 *
 * @param integer $course
 * @param integer $user
 * @param integer $entry
 * @param integer $teachers
 * @param integer $grades
 */
function diary_print_user_entry($course, $diary, $user, $entry, $teachers, $grades) {

    global $USER, $OUTPUT, $DB, $CFG;

    require_once($CFG->dirroot.'/lib/gradelib.php');
    $dcolor3 = get_config('mod_diary', 'entrybgc');
    $dcolor4 = get_config('mod_diary', 'entrytextbgc');

//////////////////////////////////////////////////////////////////////
// Add the user entry.

    echo '<div id="entry-'.$user->id.'" class="diaryuserentry" style="font-size:1em; padding: 5px;
                    font-weight:bold; background: '.$dcolor3.';
                    border:1px solid black;
                    -webkit-border-radius:16px;
                    -moz-border-radius:16px;
                    border-radius:16px;">';
    echo $OUTPUT->heading(get_string('entry', 'diary').' - '
        .date(get_config('mod_diary', 'dateformat'), $entry->timecreated));
    // User info, time created, and last edited time.
    echo '<div class="userpix" rowspan="2">';
    echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
    echo '<span class="userfullname">'.fullname($user);
    if ($entry) {
        echo ' --   <span class="lastedit" style="font-weight:normal; font-size:.7em;">'.get_string("timecreated", 'diary').':  '.userdate($entry->timecreated).' '.get_string("lastedited").': '.userdate($entry->timemodified).' </span>';
    }
    echo '</span></div>';

    // If there is a user entry, format it and show it.
    echo '<div class="row">';
    echo '<div class="element1 col-md-1">test</div>';
    echo '<div class="element2 col-md-10" 
        style="background-color: '.$dcolor4.';
            border:1px solid black;
            -webkit-border-radius:16px;
            -moz-border-radius:16px;
            border-radius:16px;"><span class="entrycontent" style="font-weight:normal;">';
    if ($entry) {
        echo diary_format_entry_text($entry, $course);
    } else {
        echo print_string("noentry", "diary");
    }
    echo '</span></div></div>';


///////////////////////////////////////////////////////////////////////
// Add the feedback for the current entry.
    // If there is a user entry, add a teacher feedback area for grade and comments. Add previous grades and comments, if available.
    if ($entry) {
        //echo '<tr>';
        echo '<div class="userpix" rowspan="2">';
        if (!$entry->teacher) {
            $entry->teacher = $USER->id;
        }
        if (empty($teachers[$entry->teacher])) {
            $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
        }
        echo '<span>';
        echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id, 'alttext' => true));

        echo get_string("feedback").':  ';
        //echo '</span></div>';
        $attrs = array();
        $hiddengradestr = '';
        $gradebookgradestr = '';
        $feedbackdisabledstr = '';
        $feedbacktext = $entry->entrycomment;

        // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));

        if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
            if ($gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked || $gradinginfo->items[0]->grades[$user->id]->overridden) {
                $attrs['disabled'] = 'disabled';
                $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
                $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
                $gradebooklink .= $gradinginfo->items[0]->grades[$user->id]->str_long_grade.'</a>';
                $gradebookgradestr = '<br/>'.get_string("gradeingradebook", "diary").':&nbsp;'.$gradebooklink;

                $feedbackdisabledstr = 'disabled="disabled"';
                $feedbacktext = $gradinginfo->items[0]->grades[$user->id]->str_feedback;
            }
        }

        // Grade selector.
        $attrs['id'] = 'r' . $entry->id;

        echo html_writer::label(fullname($user)." ".get_string('grade'), 'r'.$entry->id, true, array('class' => 'accesshide'));
        if ($diary->assessed > 0){
            echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
        }
        echo $hiddengradestr;
        // Rewrote next three lines to show entry needs to be regraded due to resubmission.
        if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
            echo ' <span class="needsedit">'.get_string("needsregrade", "diary"). ' </span>';
        } else if ($entry->timemarked) {
            echo ' <span class="lastedit">'.userdate($entry->timemarked).' </span>';
        }
        echo $gradebookgradestr;

        echo '</span></div>';

        // Feedback text.
        echo html_writer::label(fullname($user)." ".get_string('feedback'), 'c'.$entry->id, true, array('class' => 'accesshide'));
        echo '<p><textarea id="c'.$entry->id.'" name="c'.$entry->id.'" rows="12" cols="60" $feedbackdisabledstr>';
        echo p($feedbacktext);
        echo '</textarea></p>';

        if ($feedbackdisabledstr != '') {
            echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
        }
       // echo '</span></div>';
    }
        echo '</span></div>';

       // echo '</div>';

    //echo '</table>';


//////////////////////////////////////////////////////////////////////////
/*
    echo '<br><table class="diaryuserentry" id="entry-'.$user->id.'" bgcolor="'.$dcolor4.'">';
    echo '<tr>';
    echo '<td class="userpix" rowspan="2">';
    echo $OUTPUT->user_picture($user, array('courseid' => $course->id, 'alttext' => true));
    echo '</td>';
    echo '<td class="userfullname">'.fullname($user);
    if ($entry) {
        echo ' <span class="lastedit">'.get_string("timecreated", 'diary').':  '.userdate($entry->timecreated).' '.get_string("lastedited").': '.userdate($entry->timemodified).' </span>';
    }
    echo '</td>';
    echo '</tr>';

    echo '<tr><td>';
    // If there is a user entry, format it and show it.
    if ($entry) {
        echo diary_format_entry_text($entry, $course);
    } else {
        print_string("noentry", "diary");
    }
    echo '</td></tr>';

    // If there is a user entry, add a teacher feedback area for grade and comments. Add previous grades and comments, if available.
    if ($entry) {
        echo '<tr>';
        echo '<td class="userpix">';
        if (!$entry->teacher) {
            $entry->teacher = $USER->id;
        }
        if (empty($teachers[$entry->teacher])) {
            $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
        }
        echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id, 'alttext' => true));
        echo '</td>';
        echo '<td>'.get_string("feedback").':';

        $attrs = array();
        $hiddengradestr = '';
        $gradebookgradestr = '';
        $feedbackdisabledstr = '';
        $feedbacktext = $entry->entrycomment;

        // If the grade was modified from the gradebook disable edition also skip if diary is not graded.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'diary', $entry->diary, array($user->id));

        if (!empty($gradinginfo->items[0]->grades[$entry->userid]->str_long_grade)) {
            if ($gradingdisabled = $gradinginfo->items[0]->grades[$user->id]->locked || $gradinginfo->items[0]->grades[$user->id]->overridden) {
                $attrs['disabled'] = 'disabled';
                $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
                $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
                $gradebooklink .= $gradinginfo->items[0]->grades[$user->id]->str_long_grade.'</a>';
                $gradebookgradestr = '<br/>'.get_string("gradeingradebook", "diary").':&nbsp;'.$gradebooklink;

                $feedbackdisabledstr = 'disabled="disabled"';
                $feedbacktext = $gradinginfo->items[0]->grades[$user->id]->str_feedback;
            }
        }

        // Grade selector.
        $attrs['id'] = 'r' . $entry->id;

        echo html_writer::label(fullname($user)." ".get_string('grade'), 'r'.$entry->id, true, array('class' => 'accesshide'));
        if ($diary->assessed > 0){
            echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string("nograde").'...', $attrs);
        }
        echo $hiddengradestr;
        // Rewrote next three lines to show entry needs to be regraded due to resubmission.
        if (!empty($entry->timemarked) && $entry->timemodified > $entry->timemarked) {
            echo ' <span class="needsedit">'.get_string("needsregrade", "diary"). ' </span>';
        } else if ($entry->timemarked) {
            echo ' <span class="lastedit">'.userdate($entry->timemarked).' </span>';
        }
        echo $gradebookgradestr;


        // Feedback text.
        echo html_writer::label(fullname($user)." ".get_string('feedback'), 'c'.$entry->id, true, array('class' => 'accesshide'));
        echo '<p><textarea id="c'.$entry->id.'" name="c'.$entry->id.'" rows="12" cols="60" $feedbackdisabledstr>';
        echo p($feedbacktext);
        echo '</textarea></p>';

        if ($feedbackdisabledstr != '') {
            echo '<input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
*/
}

///////button work
    echo '<tr><td  style="width:35px;"></td><td><b>';
    if ($entry) {
        //echo $OUTPUT->heading(get_string('entry', 'diary').' - '
        echo get_string('entry', 'diary').' - '
            //.date(get_config('mod_diary', 'dateformat'), $entry->timecreated);
            .date('M d, Y', $entry->timecreated);

$perpage =4;
            $pagesizes = array(2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                       20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
            // This creates the dropdown list for how many entries to show on the page.
            $selection = html_writer::select($pagesizes, 'perpage', $perpage, false, array('id' => 'pref_perpage', 'class' => 'custom-select'));

        echo '</b> '.get_string('selectentry', 'diary').': <select onchange="this.form.submit()" name="currententry">';
            echo '<option selected="true" value="'.$selection.'</option>';
            echo '</select>';
    }
    echo '</td></tr>';



//////////////////////////////////////
    echo '<tr><td  style="width:35px;"></td><td><b>';
    if ($entry) {
         //echo $OUTPUT->heading(get_string('entry', 'diary').' - '
         echo get_string('entry', 'diary').' - '
         //.date(get_config('mod_diary', 'dateformat'), $entry->timecreated);
         .date('M d, Y', $entry->timecreated);
        //print_object($diary);
        //print_object($entry);

        // echo '<div class="dropdown">';
        // echo '  <a class="btn btn-danger dropdown-toggle"
        //    href="#" role="button" id="dropdownMenuLink"
        //    data-toggle="dropdown" aria-haspopup="true"
        //    aria-expanded="false">';
        // Will need to use all of this users entries ($eee) and use $entry->timecreated to make the selection list out of them. 
        //echo get_string('entry', 'diary').' - '
        //    .date('M d, Y', $entry->timecreated).' test '.$user->id;
        //echo '  </a>';

        //echo '  <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
        //    <a class="dropdown-item" href="#">Action</a>
        //    <a class="dropdown-item" href="#">Another action</a>
        //    <a class="dropdown-item" href="#">Something else here</a>
        //</div>';
        // echo '</div>';

        // https://docs.moodle.org/dev/Data_manipulation_API#get_records_select
        // Try using get records menu or get records select menu.
        //$ceee = count($DB->get_records("diary_entries", array("diary" => $diary->id), $sort = 'userid'));
        //print_object($ceee);
        //echo ' Current user record is: '.$entry->id;
        //echo '</div></td><td></td></tr>';
        echo '<td></td></tr>';
    }

////////////////////////////////