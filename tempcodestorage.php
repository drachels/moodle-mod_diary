<?php

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