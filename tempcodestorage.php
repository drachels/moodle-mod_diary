<?php
// Check to see how long it takes for a section of code to execute.
                for($i=0, $start = microtime(true); $i < 1000000; $i++) {
                    foreach ($entrys as $firstkey => $firstValue) {
                        break;
                    }
                }
                echo "foreach to get first key and value: " . (microtime(true) - $start) . " seconds <br />";



///////////////////////////////////////////
// THis section of code was in edit.php from 0.9.3 on server 10.0.6.241.
// This fir if section will need more else if's to take care of other sorts.
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