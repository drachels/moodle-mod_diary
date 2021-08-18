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
 * Diary stats utilities for Diary.
 *
 * 2020071700 Moved these functions from lib.php to here.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @copyright based on work by 2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright based on work by 2014 Dave Child 
 * @copyright based on work by 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die();

use mod_diary\local\diarystats;
use mod_diary\local\pluralise;
use mod_diary\local\syllables;
use stdClass;
use core_text;
use moodle_url;
use html_writer;

//require_once($CFG->dirroot .'/question/engine/lib.php');


/**
 * Utility class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diarystats {
    /** Information about the latest response */
    protected $currentresponse = null;
    /**
     * These variables are only used if needed
     * to detect patterns in a student response
     */
    private static $aliases = null;
    private static $metachars = null;
    private static $flipmetachars = null;

    /**
     * Update the list of Common errors.
     *
     * @param $text The cleaned plain text to search for errors.
     * @param array $diary The settings for this diary activity.
     * @return array($errors, count($errors) * $percent) An array of the common errors and percentage.
     */
    public static function get_common_errors($text, $diary) {
        global $DB;

        $errors = array();
        $debug = array();
                
        if (empty($diary->errorcmid)) {
            $cm = null;

        } else {
            $cm = get_coursemodule_from_id('', $diary->errorcmid);
        }

         if (empty($diary->errorpercent)) {
            $percent = 0;
        } else {
            $percent = $diary->errorpercent;
        }
//$debug['Tracking get_common_errors problem cp 1 showing percent '] = $percent;

        if ($cm) {
            $entryids = array();
            if ($entries = $DB->get_records('glossary_entries', array('glossaryid' => $cm->instance), 'concept')) {
//$tempcounter = 0;

                foreach ($entries as $entry) {
                    if ($match = self::glossary_diaryentry_search_text($entry, $entry->concept, $text)) {
//$tempcounter++;

//$debug['Tracking get_common_errors problem cp 2 showing match '.$tempcounter] = $match;

                        $errors[$match] = self::glossary_entry_link($cm->name, $entry, $match);
                    } else {
                        $entryids[] = $entry->id;
                    }
                }
            }

            if (count($entryids)) {
                list($select, $params) = $DB->get_in_or_equal($entryids);
                if ($aliases = $DB->get_records_select('glossary_alias', "entryid $select", $params)) {
                    foreach ($aliases as $alias) {
                        $entry = $entries[$alias->entryid];
                        if ($match = self::glossary_diaryentry_search_text($entry, $alias->alias, $text)) {
                            $errors[$match] = self::glossary_entry_link($cm->name, $entry, $match);
                        }
                    }
                }
            }

            // Sort the matching errors by length (longest to shortest).
            // https://stackoverflow.com/questions/3955536/php-sort-hash-array-by-key-length
            $matches = array_keys($errors);
            $keys = array_map('core_text::strlen', $matches);
            array_multisort($keys, SORT_DESC, $matches);
            
            // Remove matches that are substrings of longer matches.
            $keys = array();
            foreach ($matches as $match) {
                $search = '/^'.preg_quote($match, '/').'.+/iu';
                $search = preg_grep($search, $matches);
                if (count($search)) {
                    unset($errors[$match]);
                } else {
                    $keys[] = $match;
                }
            }
        }
//print_object($debug);
        return array($errors, count($errors) * $percent);
    }

    /**
     * glossary_diaryentry_search_text
     *
     * @param object $entry
     * @param string $match
     * @param string $text
     * @return string the matching substring in $text or ""
     */
    public static function glossary_diaryentry_search_text($entry, $search, $text) {
//print_object('In the function glossary_diaryentry_search_text and printing $entry, $search, $text.');
//print_object($entry);
//print_object($search);
//print_object($text);
        return self::search_text($search, $text, $entry->fullmatch, $entry->casesensitive);
    }

 
    /**
     * Store information about latest response to this entry.
     *
     * @param  string $name
     * @param  string $value
     * @return void, but will update currentresponse property of this object
     */
    public static function glossary_entry_link($glossaryname, $entry, $text) {
//print_object('In the function glossary_entry_link and printing $glossaryname, $entry, $text.');
//print_object($glossaryname);
//print_object($entry);
//print_object($text);
        $params = array('eid' => $entry->id,
                        'displayformat' => 'dictionary');
        $url = new moodle_url('/mod/glossary/showentry.php', $params);

        $params = array('target' => '_blank',
                        'title' => $glossaryname.': '.$entry->concept,
                        'class' => 'glossary autolink concept glossaryid'.$entry->glossaryid);
        return html_writer::link($url, $text, $params);
    }









/////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Convert current entry to_plain_text.
     *
     * @param $text The current text of this Diary entry.
     * @param $format
     * @param $options
     */
    public static function to_plain_text($text, $format, $options = array('noclean' => 'true')) {
        if (empty($text)) {
            return '';
        }
        //$plaintext = question_utils::to_plain_text($text, $format, array('para' => false));
        //$plaintext = self::standardize_white_space($plaintext);


        // The following call to html_to_text uses the option that strips out
        // all URLs, but format_text complains if it finds @@PLUGINFILE@@ tokens.
        // So, we need to replace @@PLUGINFILE@@ with a real URL, but it doesn't
        // matter what. We use http://example.com/.
        $text = str_replace('@@PLUGINFILE@@/', 'http://example.com/', $text);
        $plaintext = html_to_text(format_text($text, $format, $options), 0, false);
        $plaintext = self::standardize_white_space($plaintext);
        return $plaintext;
    }

    /**
     * Convert non-breaking spaces to standardize_white_space($text).
     *
     * @param Stext string The text of the current diary entry.
     */
        public static function standardize_white_space($text) {
        // Standardize white space in $text.
        // Html-entity for non-breaking space, $nbsp;,
        // is converted to a unicode character, "\xc2\xa0",
        // that can be simulated by two ascii chars (194,160)
        $text = str_replace(chr(194).chr(160), ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', trim($text));
        $text = preg_replace('/( *[\x0A-\x0D]+ *)+/s', "\n", $text);
        return $text;
    }

    /**
     * Execute search_text once for each glossary entry.
     *
     * @param string $match
     * @param string $text Complete text of the current entry.
     * @param string $search Glossary entry to search for in $text.
     * @return boolean TRUE if $text matches the $match; otherwise FALSE;
     */
    public static function search_text($search, $text, $fullmatch=false, $casesensitive=false, $ignorebreaks=true) {

        $text = trim($text);
        if ($text=='') {
            return false; // Unexpected?!
        }
        $search = trim($search);
        if ($search=='') {
            return false; // Should not happen!
        }

        if (self::$aliases===null) {
            // Human readable aliases for regexp strings.
            self::$aliases = array(' OR '  => '|',
                                   ' OR'   => '|',
                                   'OR '   => '|',
                                   ' , '   => '|',
                                   ' ,'    => '|',
                                   ', '    => '|',
                                   ','     => '|',
                                   ' AND ' => '\\b.*\\b',
                                   ' AND'  => '\\b.*\\b',
                                   'AND '  => '\\b.*\\b',
                                   ' ANY ' => '\\b.*\\b',
                                   ' ANY'  => '\\b.*\\b',
                                   'ANY '  => '\\b.*\\b');

            // Allowable regexp strings and their internal aliases.
            self::$metachars = array('^' => 'CARET',
                                     '$' => 'DOLLAR',
                                     '.' => 'DOT',
                                     '?' => 'QUESTION_MARK',
                                     '*' => 'ASTERISK',
                                     '+' => 'PLUS_SIGN',
                                     '|' => 'VERTICAL_BAR',
                                     '-' => 'HYPHEN',
                                     ':' => 'COLON',
                                     '!' => 'EXCLAMATION_MARK',
                                     '=' => 'EQUALS_SIGN',
                                     '(' => 'OPEN_ROUND',
                                     ')' => 'CLOSE_ROUND',
                                     '[' => 'OPEN_SQUARE',
                                     ']' => 'CLOSE_SQUARE',
                                     '{' => 'OPEN_CURLY',
                                     '}' => 'CLOSE_CURLY',
                                     '<' => 'OPEN_ANGLE',
                                     '>' => 'CLOSE_ANGLE',
                                     '\\' => 'BACKSLASH');
            self::$flipmetachars = array_flip(self::$metachars);
        }

        $regexp = strtr($search, self::$aliases);
        $regexp = strtr($regexp, self::$metachars);
        $regexp = preg_quote($regexp, '/');
        $regexp = strtr($regexp, self::$flipmetachars);

        if ($fullmatch) {
            $regexp = "\\b$regexp\\b";
        }
        $regexp = "/$regexp/u"; // unicode match
        if (empty($casesensitive)) {
//print_object('I found an entry that is NOT case sensitive.');
            $regexp .= 'i';
        }
        if ($ignorebreaks) {
            $regexp .= 's';
        }

        // I think this is a problem with common errors as it only counts ONE error although there may be many.
        // The second test, preg_match_all, gets the correct count when there are duplicate errors.
        if (preg_match($regexp, $text, $match)) {
        $temp1 = (preg_match_all($regexp, $text, $temp2));
//print_object($temp1);

            if (core_text::strlen($search) < core_text::strlen($match[0])) {
                return $search;
            }
            return $match[0];
        } else {
            return ''; // no matches
        }
    }

    /**
     * Update the diary statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @return bool
     */
    //public static function get_diary_stats($entry, $format) {
    public static function get_diary_stats($entry, $diary) {
        global $CFG, $OUTPUT;
        $precision = 1;

        // Temporary error fix.
        $errors = array();

        $temp = array();
        $text = self::to_plain_text($entry->text, $entry->format);
        list($errors, $erropercent) = self::get_common_errors($text, $diary);
//print_object($errors);
        $diarystats = (object)array('words' => self::get_stats_words($text),
                                    'chars' => self::get_stats_chars($text),
                                    'minmaxpercent' => 0,
                                    'sentences' => self::get_stats_sentences($text),
                                    'paragraphs' => self::get_stats_paragraphs($text),
                                    'uniquewords' => self::get_stats_uniquewords($text),
                                    'shortwords' => 0,
                                    'mediumwords' => 0,
                                    'longwords' => 0,
                                    'itempercent' => 0,
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'commonpercent' => 0,
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0,
                                    /*'totalsyllabels' => self::get_stats_totalsyllables($text),*/
                                    'totalsyllabels' => 0,
                                    'newtotalsyllabels' => 0,
                                    'fkgrade' => 0,
                                    'freadease' => 0);

        if ($diarystats->words) {
            $diarystats->lexicaldensity = round(($diarystats->uniquewords / $diarystats->words) * 100, 0).'%';
            //$diarystats->mediumwords = ($diarystats->shortwords - self::get_stats_mediumwords($text));
            list($diarystats->shortwords, $diarystats->mediumwords, $diarystats->longwords, $diarystats->totalsyllabels) = self::get_stats_longwords($text);


/*
print_object('Printing shortwords: '.$diarystats->shortwords);
print_object('Printing mediumwords: '.$diarystats->mediumwords);
print_object('Printing longwords: '.$diarystats->longwords);
*/
        }
        if ($diarystats->sentences) {
            $diarystats->charspersentence = round($diarystats->chars / $diarystats->sentences, $precision);
            $diarystats->wordspersentence = round($diarystats->words / $diarystats->sentences, $precision);
            $diarystats->longwordspersentence = round($diarystats->longwords / $diarystats->sentences, $precision);
            $diarystats->fkgrade = round(0.39 * ($diarystats->words / $diarystats->sentences) + 11.8 * ($diarystats->totalsyllabels / $diarystats->words) - 15.59, $precision);
            $diarystats->freadease = round(206.835 - 1.015 * ($diarystats->words / $diarystats->sentences) - 84.6 * ($diarystats->totalsyllabels / $diarystats->words), $precision);
        }
        if ($diarystats->wordspersentence) {
            $diarystats->fogindex = ($diarystats->wordspersentence + $diarystats->longwordspersentence);
            $diarystats->fogindex = round($diarystats->fogindex * 0.4, $precision);
        }
        if ($diarystats->paragraphs) {
            $diarystats->sentencesperparagraph = round($diarystats->sentences / $diarystats->paragraphs, $precision);
        }
        if ($diarystats->commonerrors) {
            $diarystats->commonpercent = $diarystats->commonerrors * $diary->errorpercent;
        }

        if ($CFG->branch > 32) {
            // Can try e/help or f/help-32
            $itemp = $OUTPUT->image_icon('a/help', 'click for info');
        } else {
            $itemp = $OUTPUT->pix_icon('a/help', 'click for info');
        }
        //if ($diary->enableautorating) {
        // 20210812 Show/hide statistics for each entry.
        if ($diary->enablestats) {
            // 20210815 Temporary code for developing calculation w/regard min/max character limits.
            $tempminc = $diary->mincharlimit.' min';
            $tempmaxc = $diary->maxcharlimit.' max';
            // 20210710 Temporary code for developing calculation w/regard min/max word limits.
            $tempminw = $diary->minwordlimit.' min';
            $tempmaxw = $diary->maxwordlimit.' max';
            // Auto entry into the feedback entry comment field CANNOT be done here as
            // you will get multiple duplicate entries as soon as the teacher saves one.
            
            // I THINK I WANT TO DO THE CALCULATIONS AND PLACE THEM INTO $diarystats!
            
            //$entry->entrycomment .= 'The current minimum word limit is: '.$tempminw.'.<br>';
            //$entry->entrycomment .= 'The current maximum word limit is: '.$tempmaxw.'.<br>';
            
            //print_object($diarystats);
            
            // 20210703 Consolidated the table here so using one instance instead of two.
            echo '<table class="generaltable">'
                .'<tr><td style="width: 25%">'.get_string('timecreated', 'diary').' '.userdate($entry->timecreated).'</td>'
                    .'<td style="width: 25%">'.get_string('lastedited').' '.userdate($entry->timemodified).'</td>'
                    /*.'<td style="width: 25%">'.get_string('created', 'diary', ['one' => $diff->days, 'two' => $diff->h]).'</td>'*/
                    .'<td style="width: 25%">Item percent setting '.$diary->itempercent.'% </td>'
                    .'<td style="width: 25%">Error percent setting '.$diary->errorpercent.'% </td>'
                .'<tr><td>'.get_string('chars', 'diary').' '.$tempminc.'/'.$diarystats->chars.'/'.$tempmaxc.'</td>'
                    .'<td>'.get_string('words', 'diary').' '.$tempminw.'/'.$diarystats->words.'/'.$tempmaxw.'</td>'
                    .'<td>'.get_string('sentences', 'diary').' '.$diarystats->sentences.'</td>'
                    .'<td>'.get_string('paragraphs', 'diary').' '.$diarystats->paragraphs.'</td></tr>'
                    
                .'<tr><td>'.get_string('uniquewords', 'diary').' '.$diarystats->uniquewords.'</td>'
                    .'<td>'.get_string('shortwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('shortwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->shortwords.'</td>'
                    .'<td>'.get_string('mediumwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('mediumwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->mediumwords.'</td>'
                    .'<td>'.get_string('longwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('longwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->longwords.'</td></tr>'
                    
                .'<tr><td>'.get_string('charspersentence', 'diary').' '.$diarystats->charspersentence.'</td>'
                    .'<td>'.get_string('sentencesperparagraph', 'diary').' '.$diarystats->sentencesperparagraph.'</td>'
                    .'<td>'.get_string('wordspersentence', 'diary').' '.$diarystats->wordspersentence.'</td>'
                    .'<td>'.get_string('longwordspersentence', 'diary').' '.$diarystats->longwordspersentence.'</td></tr>'

                .'<tr><td>'.get_string('lexicaldensity', 'diary')
                    .' <a href="#" data-toggle="popover" data-content="'
                    .get_string('lexicaldensity_help', 'diary').'">'.$itemp.'</a> '
                    .$diarystats->lexicaldensity.'</td>'
                    .'<td>'.get_string('fkgrade', 'diary')
                        .' <a href="#" data-toggle="popover" data-content="'
                        .get_string('fkgrade_help', 'diary').'">'.$itemp.'</a> '
                        .$diarystats->fkgrade.' </td>'
                    .'<td>'.get_string('freadingease', 'diary')
                        .' <a href="#" data-toggle="popover" data-content="'
                        .get_string('freadingease_help', 'diary').'">'.$itemp.'</a> '
                        .$diarystats->freadease.' </td>'
                    .'<td>'.get_string('fogindex', 'diary')
                        .' <a href="#" data-toggle="popover" data-content="'
                        .get_string('fogindex_help', 'diary').'">'.$itemp.'</a> '
                        .$diarystats->fogindex.'</td>'

                .'<tr><td>'.get_string('totalsyllables', 'diary').' '.$diarystats->totalsyllabels.' </td>'
                    .'<td> </td>'
                    .'<td> </td>'
                    .'<td> </td></tr>';

            //20210704 If common errors from the glossary are detected, list them here.
            if ($errors) {
                $x = 1;
                $temp ='';
                foreach ($errors as $error) {
                    $temp .= $x.'. '.$error.' ';
                    ++$x;
                }
                echo '<tr class="table-warning"><td colspan="4">Detected at least '.$diarystats->commonerrors.', '.get_string('commonerrors', 'diary').'. They are: '.$temp.'<br> If allowed, you should fix them and re-submit.</td></tr>';
            }




///////////////////////////////////////////////////////////////////////////////////////////////////////////
            // 20210814 Show rating info only if enabled. NEEDS LOTS OF TLC!
            if ($diary->enableautorating) {
                // 20210711 Added potential auto rating penalty info.
                 echo '<tr class="table-primary"><td colspan="4"> The rating for this entry is '.$diary->scale.' points.</td></tr>';

                // 20210713 Need the item type and how many of them must be used in this diary entry.
                $itemtypes = array();
                $itemtypes = diarystats::get_item_types($itemtypes);
                if (($diary->itemtype > 0) && ($diary->itemcount > 0)) {
                    $diary->intro .= get_string('itemtype_desc', 'diary', ['one' => $itemtypes[$diary->itemtype], 'two' => $diary->itemcount]).'<br>';
                }


                echo '<tr class="table-info"><td colspan="4"> The item selected for automatic rating check is: '.$diary->itemcount.' or more '.$itemtypes[$diary->itemtype].' with a possible '.$diary->itempercent.'% penalty for each of the missing, '.$itemtypes[$diary->itemtype].'.</td></tr>';

                // $debug is an array containing the basic syllable counting steps for the current word.
                $debug = array();
                
                $item = strtolower($itemtypes[$diary->itemtype]);
                $debug['Tracking problem with $item for auto-rating cp 1 '] = $item;

/////$item is set to chars all the time, seems like.
                if ($item == 'characters') {
                    $item = 'chars';
                } else {
                    $item = strtolower($itemtypes[$diary->itemtype]);
                }

                $debug['Tracking problem with $item for auto-rating cp 2 '] = $item;

                $itemrating = ($diary->itemcount - $diarystats->$item) * $diary->itempercent;
                //print_object($itemrating);
                //print_object($diarystats);
                //print_object($diarystats->$item);
//print_object($debug);
            
                // The $minmaxrating needs to take into acount the min and max for characters and for words, if they are set!
                $minmaxrating = $diarystats->commonerrors * $diary->minmaxpercent;
                $commonerrorrating = $diarystats->commonpercent;
            
                echo '<tr><td colspan="4" class="table-success"> The item being used in the auto-rating is: '.$item.' and you are short '.$diary->itemcount.' - '.$diarystats->$item.' = '.($diary->itemcount - $diarystats->$item).'.</td></tr>';
            
                echo '<tr><td colspan="4" class="table-success"> The $itemrating is: ('.$diary->itemcount.' - '.$diarystats->$item.') * '.$diary->itempercent.' = '.$itemrating.' .</td></tr>';
            
            
            
                echo '<tr><td colspan="4" class="table-danger"> Potential Auto-rating penalty: '.$diarystats->commonerrors * $diary->itempercent.'% or '.$itemrating.' points off.</td></tr>';
                echo '<tr><td colspan="4" class="table-danger"> Potential Min/Max counts penalty: '.$diarystats->commonerrors * $diary->minmaxpercent.'%</td></tr>';
                echo '<tr><td colspan="4" class="table-danger"> Potential Common error penalty: '.$diarystats->commonpercent.'%</td></tr>';
                $currentrating = $diary->scale - $itemrating - $minmaxrating - $commonerrorrating;
                echo '<tr><td colspan="4" class="table-danger"> Your current potential rating is: '.$currentrating.'%</td></tr>';
            }

            echo '</table>';
            //return $diarystats;
        }
        return;
    }

    /**
     * Update the diary character count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of characters.
     */
    public static function get_stats_chars($entry) {
        return core_text::strlen($entry);
        // @codingStandardsIgnoreLine
        // return strlen($entry);
    }

    /**
     * Update the diary word count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of words.
     */
    public static function get_stats_words($entry) {
        return str_word_count($entry, 0);
    }

    /**
     * Update the diary sentence count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of sentences.
     */
    public static function get_stats_sentences($entry) {
        $items = preg_split('/[!?.]+(?![0-9])/', $entry);
        $items = array_filter($items);
        return count($items);
    }

    /**
     * Update the diary paragraph count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of paragraphs.
     */
    //public static function get_stats_paragraphs($entry, $format) {
    public static function get_stats_paragraphs($text) {
        $items = self::multipleexplode(array("<p", "<p>", "\n", "\r\n"), $text);
        $items = array_filter($items);
        return count($items);
    }
    /**
     * Process the multiple delimiters used for checking paragraphs.
     *
     * @param array $delimiters An array of paragraph delimeters.
     * @param string $entry The text to examine for paragraphs.
     * @return array $processed An array containing the separated pargraphs.
     */
    public static function multipleexplode($delimiters,$entry) {
        $phase = str_replace($delimiters, $delimiters[0], $entry);
        $processed = explode($delimiters[0], $phase);
        return  $processed;
    }

    /**
     * Update the diary unique word count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @return int The number of unique words.
     */
    public static function get_stats_uniquewords($entry) {
        $items = core_text::strtolower($entry);
        $items = str_word_count($items, 1);
        $items = array_unique($items);
        return count($items);
    }

    /**
     * Update the diary long word count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @return array The number of one, two, and three or more syllable words and total number of syllables.
     */
    public static function get_stats_longwords($entry) {
        $swcount = 0;
        $mwcount = 0;
        $lwcount = 0;
        $totalsyllables = 0;
        $items = core_text::strtolower($entry);
        $items = str_word_count($items, 1);
        $items = array_unique($items);
        // Get the unicode word count for one, two, and three or more syllables.
        foreach ($items as $item) {
            $syllable = self::count_syllables($item);
            // Keep track of the total number of syllables used.
            $totalsyllables += $syllable;
            if ($syllable > 2) {
                $lwcount++;
            } else if ($syllable < 2) {
                 $swcount++;
            } else {
                $mwcount++;
            }
        }
        return array($swcount, $mwcount, $lwcount, $totalsyllables);
    }

    /**
     * Update the number of syllables per word statistics for this diary activity.
     *
     * based on: https://github.com/e-rasvet/sassessment/blob/master/lib.php
     * @param string $word The word to check for number of syllables.
     * @return int The number of syllables in the current word.
     */
    public static function count_syllables($word) {
        // https://github.com/vanderlee/phpSyllable (multilang)
        // https://github.com/DaveChild/Text-Statistics (English only)
        // https://pear.php.net/manual/en/package.text.text-statistics.intro.php
        // https://pear.php.net/package/Text_Statistics/docs/latest/__filesource/fsource_Text_Statistics__Text_Statistics-1.0.1TextWord.php.html

        // $debug is an array containing the basic syllable counting steps for the current word.
        $debug = array();
        $debug['Counting syllables for version 1'] = $word;
        
        $str = strtoupper($word);
        if (strlen($str) < 2) {
            $count = 1;
        } else {
            $count = 0;

            // Detect syllables for double-vowels.
            $vowelcount = 0;
            $vowels = array('AA','AE','AI','AO','AU','AY',
                            'EA','EE','EI','EO','EU','EY',
                            'IA','IE','II','IO','IU','IY',
                            'OA','OE','OI','OO','OU','OY',
                            'UA','UE','UI','UO','UU','UY',
                            'YA','YE','YI','YO','YU','YY');
            $str = str_replace($vowels, '', $str, $vowelcount);
            $count += $vowelcount;
            
            $debug['Just finished processing double-vowels'] = $str;
            //$debug['The oldlen is'] = $oldlen;
            //$debug['The newlen is'] = $newlen;
            $debug['After double-vowel the count is'] = $count;


            // Cache the final letter, in case it is an "e"
            $finalletter = substr($str, -1);

            // detect syllables for single-vowels
            $vowelcount = 0;
            $vowels = array('A','E','I','O','U','Y');
            $str = str_replace($vowels, '', $str, $vowelcount);
            $count += $vowelcount;

            $debug['Just finished processing single-vowels'] = $str;
            //print_object('print str '.$str);
            //print_object('print str_replace($vowels, null, $str) '.str_replace($vowels, '', $str));
            //$debug['The oldlen is'] = $oldlen;
            //$debug['The newlen is'] = $newlen;
            $debug['After single-vowel the count is'] = $count;


            // adjust the count for words that end in "e"
            // and have at least one other vowel
            if ($count > 1 && $finalletter == 'E') {
                $count--;
                $debug['We had a final letter E and the count is now'] = $count;

            }
        }
        $newsyllablecount = syllables::syllable_count($word,  $strEncoding = '');

/*
        if ($count <> $newsyllablecount) {
            print_object('Gordon Bateson syllable counter: '.$word.' '.$count);
            print_object('Dave Child syllable counter: '.$word.' '.$newsyllablecount);
            //print_object($debug);
            //print_object('Done==========================');
        }
*/
        //return $count;
        return $newsyllablecount;
    }

        
////////////////////////////   MY COUNT_SYLLABLES ROUTINE    ///////////////////////////////
        
        // https://github.com/vanderlee/phpSyllable (multilang)
        // https://github.com/DaveChild/Text-Statistics (English only)
        // https://pear.php.net/manual/en/package.text.text-statistics.intro.php
        // https://pear.php.net/package/Text_Statistics/docs/latest/__filesource/fsource_Text_Statistics__Text_Statistics-1.0.1TextWord.php.html
/*
        // $debug is an array containing the basic syllable counting steps for this word.
        $debug = array();
        $debug['Counting syllables for'] = $word;

        //$str = strtoupper($word);
        //$debug['Convert word to uppercase str'] = $str;

        $str = strtolower($word);
        $debug['Convert word to lowercase varible str'] = $str;

        // Check for problem words
        if ($count = self::problem_words($word)) {
            $debug['We have found a problem word'] = $word;
            $debug['We returning a problem word count of'] = $count;
            print_object($debug);
            // If we found a problem word, then we are done getting count_syllables value.
            return $count;
        }

        // Try singular
        $singularword = pluralise::get_singular($word);
        if ($singularword != $word) {
            if ($count = self::problem_words($singularword)) {
                $debug['Found a singularized problem word'] = $singularword;
                $debug['Returning a singularized problem word count of'] = $count;
                print_object($debug);

                return  $count;
            }
        }

        $oldlen = strlen($str);
        if ($oldlen < 2) {
            $count = 1;
        } else {
            $count = 0;
            // Adjust count for special last character.
            switch (substr($str, -1)) {
                case 'E': $count--;
                    $debug['We have found a trailing E'] = $str;
                    $debug['We have a count of'] = $count;
                    break;
                case 'Y': $count--;
                    $count = max($count, 0);
                    $debug['We have found a trailing Y'] = $str;
                    $debug['We have a count of'] = $count;
                    break;
            };
            
            // Detect syllables for double-vowels.
            $vowels = array('AA','AE','AI','AO','AU','AY',
                            'EA','EE','EI','EO','EU','EY',
                            'IA','IE','II','IO','IU','IY',
                            'OA','OE','OI','OO','OU','OY',
                            'UA','UE','UI','UO','UU','UY',
                            'YA','YE','YI','YO','YU','YY');
            $str = str_replace($vowels, '', $str);
            $newlen = strlen($str);
            $count += (($oldlen - $newlen) / 2);
            $debug['Just finished processing double-vowels'] = $str;
            $debug['The oldlen is'] = $oldlen;
            $debug['The newlen is'] = $newlen;
            $debug['After double-vowel the count is'] = $count;

            // Detect syllables for single-vowels.
            $vowels = array('A','E','I','O','U','Y');
            $str = str_replace($vowels, '', $str);
            $oldlen = $newlen;
            $newlen = strlen($str);
            $count += ($oldlen - $newlen);
            $debug['Just finished processing single-vowels'] = $str;
            print_object('print str '.$str);
            print_object('print str_replace($vowels, null, $str) '.str_replace($vowels, '', $str));
            $debug['The oldlen is'] = $oldlen;
            $debug['The newlen is'] = $newlen;
            $debug['After double-vowel the count is'] = $count;
        }
        $count = max($count, 1);
$debug['We have finished and our syllable count is'] = $count;
print_object($debug);

        return $count;
    }
*/



    /**
     * Update the total number of syllables statistics for this diary activity.
     *
     * @param string $entry The text to check for number of syllables.
     * @return int The total number of syllables in the current diary entry.
     */
    public static function get_stats_totalsyllables($entry) {
        // Break entry into words and get the syllable count for each word.
        $totalsyllabels = 0;
        $items = core_text::strtolower($entry);
        $items = str_word_count($items, 1);
        foreach ($items as $item) {
            $totalsyllabels += self::count_syllables($item);
        }
        return $totalsyllabels;
    }

    /**
     * Update the list of item types that can be rated in this activity.
     *
     * @param string $itemtypes The array of item types.
     * @return array An array of the item types available for auto-rating.
     */
    public static function get_item_types($itemtypes) {
        // 20210710 Add check and description addition for type of countable items.
        $itemtypes['0'] = get_string('none');
        $itemtypes['1'] = get_string('chars', 'diary');
        $itemtypes['2'] = get_string('words', 'diary');
        $itemtypes['3'] = get_string('sentences', 'diary');
        $itemtypes['4'] = get_string('paragraphs', 'diary');
        $itemtypes['5'] = get_string('files', 'diary');
        return $itemtypes;
    }

    /**
     * Get array of rating options.
     *
     * @param string $plugin name
     * @return array(rating => description)
     */
    public static function get_rating_options($plugin) {
        $options = array();
        for ($i=0; $i<=100; $i++) {
            $options[$i] = get_string('percentofentryrating', $plugin, $i);
        }
        return $options;
    }

    /**
     * Get array of show/hide options
     *
     * @param string $plugin name
     * @return array(type => description)
     */
    public static function get_showhide_options($plugin) {
        $options['0'] = get_string('shownone', $plugin);
        $options['1'] = get_string('showstudentsonly', $plugin);
        $options['2'] = get_string('showteachersonly', $plugin);
        $options['3'] = get_string('showteacherandstudents', $plugin);
        return $options;
    }

    /**
     * Get array of countable item types.
     *
     * @return array(type => description)
     */
    public static function get_textstatitems_options($returntext=true) {
        $options = array('chars', 'words',
                         'sentences', 'paragraphs',
                         'uniquewords', 'longwords',
                         'charspersentence', 'wordspersentence',
                         'longwordspersentence', 'sentencesperparagraph',
                         'lexicaldensity', 'fogindex',
                         'commonerrors', 'files');
        if ($returntext) {
            $plugin = 'mod_diary'; // $this->plugin_name();
            $options = array_flip($options);
            foreach (array_keys($options) as $option) {
                $options[$option] = get_string($option, $plugin);
            }
        }
        return $options;
    }

    /**
     * Update the list of item min/maxes in this activity.
     *
     * @param string $diary The diary containing the min/maxes.
     * @return nothing
     */
    public static function get_minmaxes($diary) {
        // 20210710 Add checks and description additions for mins and maxes. This is temporary and probably needs to be moved to somewhere else so it can be shown on the edit.php page, too. Maybe move to results.php.
        if ($diary->mincharlimit > 0) {
            $diary->intro .= '<br>'.get_string('mincharlimit_desc', 'diary', ($diary->mincharlimit)).'<br>';
        }
        if ($diary->maxcharlimit > 0) {
            $diary->intro .= get_string('maxcharlimit_desc', 'diary', ($diary->maxcharlimit)).'<br>';
        }
        if ($diary->minwordlimit > 0) {
            $diary->intro .= get_string('minwordlimit_desc', 'diary', ($diary->minwordlimit)).'<br>';
        }
        if ($diary->maxwordlimit > 0) {
            $diary->intro .= get_string('maxwordlimit_desc', 'diary', ($diary->maxwordlimit)).'<br>';
        }
        return;
    }
}
