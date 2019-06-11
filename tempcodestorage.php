<?php
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