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

defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

use mod_diary\local\diarystats;
use mod_diary\local\pluralise;
use mod_diary\local\syllables;
use stdClass;
use core_text;
use moodle_url;
use html_writer;

/**
 * Utility class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diarystats {
    /** @var Information about the latest response */
    protected $currentresponse = null;
    /**
     * These variables are only used if needed
     * to detect patterns in a student response.
     */
    /** @var array null */
    private static $aliases = null;
    /** @var array null */
    private static $metachars = null;
    /** @var array null */
    private static $flipmetachars = null;
    /** @var array null */
    private static $autoratepenaltys = null;

    /**
     * Update the list of Common errors.
     *
     * @param string $text The cleaned plain text to search for errors.
     * @param array $diary The settings for this diary activity.
     * @return array($errors, count($errors) * $percent) An array of the common errors and percentage.
     */
    public static function get_common_errors($text, $diary) {
        global $DB;

        $errors = array();
        $matches = array();

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

        if ($cm) {
            $entryids = array();
            if ($entries = $DB->get_records('glossary_entries', array('glossaryid' => $cm->instance), 'concept')) {
                foreach ($entries as $entry) {
                    if ($match = self::glossary_diaryentry_search_text($entry, $entry->concept, $text)) {
                        list($pos, $length, $match) = $match;
                        $errors[$match] = self::glossary_entry_link($cm->name, $entry, $match);
                        $matches[$pos] = (object)array('pos' => $pos, 'length' => $length, 'match' => $match);
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
                            list($pos, $length, $match) = $match;
                            $errors[$match] = self::glossary_entry_link($cm->name, $entry, $match);
                            $matches[$pos] = (object)array('pos' => $pos, 'length' => $length, 'match' => $match);
                        }
                    }
                }
            }

        }

        $errortext = $text;
        if (count($matches)) {
            // Sort error $matches from last position to first position.
            krsort($matches);
            foreach ($matches as $match) {
                $pos = $match->pos;
                $length = $match->length;
                $match = $errors[$match->match]; // A link to the glossary.
                $errortext = substr_replace($errortext, $match, $pos, $length);
            }
        }

        if (count($errors)) {
            // Sort the matching errors by length (longest to shortest).
            // From https://stackoverflow.com/questions/3955536/php-sort-hash-array-by-key-length.
            $matches = array_keys($errors);
            $lengths = array_map('core_text::strlen', $matches);
            array_multisort($lengths, SORT_DESC, $matches);

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
        return array($errors, $errortext, count($errors) * $percent);
    }

    /**
     * glossary_diaryentry_search_text
     *
     * @param object $entry
     * @param string $search
     * @param string $text
     * @return string the matching substring in $text or ""
     */
    public static function glossary_diaryentry_search_text($entry, $search, $text) {
        return self::search_text($search, $text, $entry->fullmatch, $entry->casesensitive);
    }

    /**
     * Store information about latest response to this entry.
     *
     * @param  string $glossaryname
     * @param  string $entry
     * @param  string $text
     * @return void, but will update currentresponse property of this object.
     */
    public static function glossary_entry_link($glossaryname, $entry, $text) {
        $params = array('eid' => $entry->id,
                        'displayformat' => 'dictionary');
        $url = new moodle_url('/mod/glossary/showentry.php', $params);
        $params = array('target' => '_blank',
                        'title' => $glossaryname.': '.$entry->concept,
                        'class' => 'glossary autolink concept glossaryid'.$entry->glossaryid);
        return html_writer::link($url, $text, $params);
    }

    /**
     * Convert current entry to_plain_text.
     *
     * @param string $text The current text of this Diary entry.
     * @param string $format
     * @param array $options
     */
    public static function to_plain_text($text, $format, $options = array('noclean' => 'true')) {
        if (empty($text)) {
            return '';
        }
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
     * @param string $text string The text of the current diary entry.
     * @return string $text
     */
    public static function standardize_white_space($text) {
        // Standardize white space in $text.
        // Html-entity for non-breaking space, $nbsp;,
        // is converted to a unicode character, "\xc2\xa0",
        // that can be simulated by two ascii chars (194,160).
        $text = str_replace(chr(194).chr(160), ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', trim($text));
        $text = preg_replace('/( *[\x0A-\x0D]+ *)+/s', "\n", $text);
        return $text;
    }

    /**
     * Execute search_text once for each glossary entry.
     *
     * @param string $search Glossary entry to search for in $text.
     * @param string $text Complete text of the current entry.
     * @param int $fullmatch
     * @param int $casesensitive
     * @param int $ignorebreaks
     * @return boolean TRUE if $text matches the $match; otherwise FALSE;
     */
    public static function search_text($search, $text, $fullmatch=false, $casesensitive=false, $ignorebreaks=true) {
        $text = trim($text);
        if ($text == '') {
            return false; // Unexpected?
        }
        $search = trim($search);
        if ($search == '') {
            return false; // Should not happen!
        }

        if (self::$aliases === null) {
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
        $regexp = "/$regexp/u"; // Unicode match.
        if (empty($casesensitive)) {
            $regexp .= 'i';
        }
        if ($ignorebreaks) {
            $regexp .= 's';
        }
        // I think this is a problem with common errors as it only counts ONE error although there may be many.
        if (preg_match($regexp, $text, $match, PREG_OFFSET_CAPTURE)) {
            list($match, $offset) = $match[0];
            $length = strlen($match);
            if (core_text::strlen($search) < core_text::strlen($match[0])) {
                $match = $search;
            }
            return array($offset, $length, $match);
        } else {
            return ''; // No matches.
        }
    }

    /**
     * Update the diary statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @param array $diary The diary info for this entry.
     * @return string $currentstats String with table of current statistics.
     */
    public static function get_diary_stats($entry, $diary) {
        global $CFG, $OUTPUT;
        $precision = 1;

        // Temporary error fix.
        $errors = array();

        $temp = array();
        $text = self::to_plain_text($entry->text, $entry->format);
        list($errors, $errortext, $erropercent) = self::get_common_errors($text, $diary);
        $diarystats = (object)array('words' => self::get_stats_words($text),
                                    'characters' => self::get_stats_chars($text),
                                    'sentences' => self::get_stats_sentences($text),
                                    'paragraphs' => self::get_stats_paragraphs($text),
                                    'uniquewords' => self::get_stats_uniquewords($text),
                                    'minmaxpercent' => 0,
                                    'shortwords' => 0,
                                    'mediumwords' => 0,
                                    'longwords' => 0,
                                    'item' => 0,
                                    'itempercent' => 0,
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'commonpercent' => 0,
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0,
                                    'totalsyllabels' => 0,
                                    'newtotalsyllabels' => 0,
                                    'fkgrade' => 0,
                                    'freadease' => 0);

        if ($diarystats->words) {
            $diarystats->lexicaldensity = round(($diarystats->uniquewords / $diarystats->words) * 100, 0).'%';
            list($diarystats->shortwords,
                 $diarystats->mediumwords,
                 $diarystats->longwords,
                 $diarystats->totalsyllabels)
                 = self::get_stats_longwords($text);

        }
        if ($diarystats->sentences) {
            $diarystats->charspersentence = round($diarystats->characters / $diarystats->sentences, $precision);
            $diarystats->wordspersentence = round($diarystats->words / $diarystats->sentences, $precision);
            $diarystats->longwordspersentence = round($diarystats->longwords / $diarystats->sentences, $precision);
            $diarystats->fkgrade = max(round(0.39 *
                                  ($diarystats->words / $diarystats->sentences) + 11.8 *
                                  ($diarystats->totalsyllabels / max($diarystats->words, 1)) - 15.59, $precision), 0);
            $diarystats->freadease = round(206.835 - 1.015 *
                                    ($diarystats->words / $diarystats->sentences) - 84.6 *
                                    ($diarystats->totalsyllabels / max($diarystats->words, 1)), $precision);
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
            // Can try e/help or f/help-32.
            $itemp = $OUTPUT->image_icon('a/help', get_string('popoverhelp', 'diary'));
        } else {
            $itemp = $OUTPUT->pix_icon('a/help', get_string('popoverhelp', 'diary'));
        }

        // 20210812 Show/hide statistics for each entry. 20220903 Total re-write of code.
        if ($diary->enablestats) {
            // 20220904 Code for auto-rating calculation w/regard min/max character limits.
            $tempminc = $diary->mincharacterlimit.get_string('min', 'diary');
            $tempmaxc = $diary->maxcharacterlimit.get_string('max', 'diary');
            $autocharacters = '';
            $item = 'characters';
            if ($diary->enableautorating && $diary->mincharacterlimit > 0 && $diarystats->characters) {
                if (((max($diary->mincharacterlimit - $diarystats->characters, 0)) * $diary->minmaxcharpercent) <> 0) {
                    $autocharacters = '<span style="background-color:yellow">'.get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->mincharacterlimit,
                        'two' => $item,
                        'three' => $diary->minmaxcharpercent,
                        'four' => $diarystats->characters,
                        'five' => (max($diary->mincharacterlimit - $diarystats->characters, 0)),
                        'six' => ((max($diary->mincharacterlimit - $diarystats->characters, 0)) * $diary->minmaxcharpercent)])
                        .'</span>';
                } else {
                    $autocharacters = get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->mincharacterlimit,
                        'two' => $item,
                        'three' => $diary->minmaxcharpercent,
                        'four' => $diarystats->characters,
                        'five' => (max($diary->mincharacterlimit - $diarystats->characters, 0)),
                        'six' => ((max($diary->mincharacterlimit - $diarystats->characters, 0)) * $diary->minmaxcharpercent)]);
                }
            }
            if ($diary->enableautorating && $diary->maxcharacterlimit > 0 && $diarystats->characters > $diary->maxcharacterlimit) {
                $autocharacters = '<span style="background-color:yellow">'.get_string('autoratingovermaxitemdetails', 'diary',
                    ['one' => $diary->maxcharacterlimit,
                    'two' => $item,
                    'three' => $diary->minmaxcharpercent,
                    'four' => $diarystats->characters,
                    'five' => (max($diarystats->characters - $diary->maxcharacterlimit, 0)),
                    'six' => ((max($diarystats->characters - $diary->maxcharacterlimit, 0)) * $diary->minmaxcharpercent)])
                    .'</span>';
            }

            // 20220904 Code for auto-rating calculation w/regard min/max word limits.
            $tempminw = $diary->minwordlimit.get_string('min', 'diary');
            $tempmaxw = $diary->maxwordlimit.get_string('max', 'diary');
            $autowords = '';
            $item = 'words';
            if ($diary->enableautorating && $diary->minwordlimit > 0 && $diarystats->words) {
                if (((max($diary->minwordlimit - $diarystats->words, 0)) * $diary->minmaxwordpercent) <> 0) {
                    $autowords = '<span style="background-color:yellow">'.get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minwordlimit,
                        'two' => $item,
                        'three' => $diary->minmaxwordpercent,
                        'four' => $diarystats->words,
                        'five' => (max($diary->minwordlimit - $diarystats->words, 0)),
                        'six' => ((max($diary->minwordlimit - $diarystats->words, 0)) * $diary->minmaxwordpercent)])
                        .'</span>';
                } else {
                    $autowords = get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minwordlimit,
                        'two' => $item,
                        'three' => $diary->minmaxwordpercent,
                        'four' => $diarystats->words,
                        'five' => (max($diary->minwordlimit - $diarystats->words, 0)),
                        'six' => ((max($diary->minwordlimit - $diarystats->words, 0)) * $diary->minmaxwordpercent)]);
                }
            }
            if ($diary->enableautorating && $diary->maxwordlimit > 0 && $diarystats->words > $diary->maxwordlimit) {
                $autowords = '<span style="background-color:yellow">'.get_string('autoratingovermaxitemdetails', 'diary',
                    ['one' => $diary->maxwordlimit,
                    'two' => $item,
                    'three' => $diary->minmaxwordpercent,
                    'four' => $diarystats->words,
                    'five' => (max($diarystats->words - $diary->maxwordlimit, 0)),
                    'six' => ((max($diarystats->words - $diary->maxwordlimit, 0)) * $diary->minmaxwordpercent)])
                    .'</span>';
            }

            // 20220904 Code for auto-rating calculation w/regard min/max sentence limits.
            $tempmins = $diary->minsentencelimit.get_string('min', 'diary');
            $tempmaxs = $diary->maxsentencelimit.get_string('max', 'diary');
            $autosentences = '';
            $item = 'sentences';
            if ($diary->enableautorating && $diary->minsentencelimit > 0 && $diarystats->sentences) {
                if (((max($diary->minsentencelimit - $diarystats->sentences, 0)) * $diary->minmaxsentpercent) <> 0) {
                    $autosentences = '<span style="background-color:yellow">'.get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minsentencelimit,
                        'two' => $item,
                        'three' => $diary->minmaxsentpercent,
                        'four' => $diarystats->sentences,
                        'five' => (max($diary->minsentencelimit - $diarystats->sentences, 0)),
                        'six' => ((max($diary->minsentencelimit - $diarystats->sentences, 0)) * $diary->minmaxsentpercent)])
                        .'</span>';
                } else {
                    $autosentences = get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minsentencelimit,
                        'two' => $item,
                        'three' => $diary->minmaxsentpercent,
                        'four' => $diarystats->sentences,
                        'five' => (max($diary->minsentencelimit - $diarystats->sentences, 0)),
                        'six' => ((max($diary->minsentencelimit - $diarystats->sentences, 0)) * $diary->minmaxsentpercent)]);
                }
            }
            if ($diary->enableautorating
                && $diary->maxsentencelimit > 0
                && $diarystats->sentences > $diary->maxsentencelimit) {
                $autosentences = '<span style="background-color:yellow">'.get_string('autoratingovermaxitemdetails', 'diary',
                    ['one' => $diary->maxsentencelimit,
                    'two' => $item,
                    'three' => $diary->minmaxsentpercent,
                    'four' => $diarystats->sentences,
                    'five' => (max($diarystats->sentences - $diary->maxsentencelimit, 0)),
                    'six' => ((max($diarystats->sentences - $diary->maxsentencelimit, 0)) * $diary->minmaxsentpercent)]).'</span>';
            }

            // 20220904 Code for auto-rating calculation w/regard min/max paragraph limits.
            $tempminp = $diary->minparagraphlimit.get_string('min', 'diary');
            $tempmaxp = $diary->maxparagraphlimit.get_string('max', 'diary');
            $autoparagraphs = '';
            $item = 'paragraphs';
            if ($diary->enableautorating && $diary->minparagraphlimit > 0 && $diarystats->paragraphs) {
                if (((max($diary->minparagraphlimit - $diarystats->paragraphs, 0)) * $diary->minmaxparapercent) <> 0) {
                    $autoparagraphs = '<span style="background-color:yellow">'.get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minparagraphlimit,
                        'two' => $item,
                        'three' => $diary->minmaxparapercent,
                        'four' => $diarystats->paragraphs,
                        'five' => (max($diary->minparagraphlimit - $diarystats->paragraphs, 0)),
                        'six' => ((max($diary->minparagraphlimit - $diarystats->paragraphs, 0)) * $diary->minmaxparapercent)])
                        .'</span>';
                } else {
                    $autoparagraphs = get_string('autoratingbelowmaxitemdetails', 'diary',
                        ['one' => $diary->minparagraphlimit,
                        'two' => $item,
                        'three' => $diary->minmaxparapercent,
                        'four' => $diarystats->paragraphs,
                        'five' => (max($diary->minparagraphlimit - $diarystats->paragraphs, 0)),
                        'six' => ((max($diary->minparagraphlimit - $diarystats->paragraphs, 0)) * $diary->minmaxparapercent)]);
                }
            }
            if ($diary->enableautorating
                && $diary->maxparagraphlimit > 0
                && $diarystats->paragraphs > $diary->maxparagraphlimit) {
                $autoparagraphs = '<span style="background-color:yellow">'.get_string('autoratingovermaxitemdetails', 'diary',
                    ['one' => $diary->maxparagraphlimit,
                    'two' => $item,
                    'three' => $diary->minmaxparapercent,
                    'four' => $diarystats->paragraphs,
                    'five' => (max($diarystats->paragraphs - $diary->maxparagraphlimit, 0)),
                    'six' => ((max($diarystats->paragraphs - $diary->maxparagraphlimit, 0)) * $diary->minmaxparapercent)])
                    .'</span>';
            }

            // 20210703 Consolidated the table here so using one instance instead of two.
            $currentstats = '<table class="generaltable">'
                .'<tr><td style="width: 25%">'.get_string('timecreated', 'diary').' '.userdate($entry->timecreated).'</td>'
                    .'<td style="width: 25%">'.get_string('lastedited').' '.userdate($entry->timemodified).'</td>'
                    .'<td style="width: 25%">'.get_string('autoratingitempercentset', 'diary', (
                        '<br>C '.$diary->minmaxcharpercent
                        .'%, W '.$diary->minmaxwordpercent
                        .'%, S '.$diary->minmaxsentpercent
                        .'%, P '.$diary->minmaxparapercent)).' </td>'
                    .'<td style="width: 25%">'.get_string('commonerrorpercentset', 'diary', (
                        '<br> Ce '.$diary->errorpercent)).' </td></tr>';

            // 20211007 An output experiment check to see if there is any text.
            if ($diarystats->uniquewords > 0) {
                $currentstats .= '<tr><td>'.get_string('chars', 'diary').' '
                        .$tempminc.'/'.$diarystats->characters.'/'.$tempmaxc.'<br>'.$autocharacters.'</td>'
                    .'<td>'.get_string('words', 'diary').' '
                        .$tempminw.'/'.$diarystats->words.'/'.$tempmaxw.'<br>'.$autowords.'</td>'
                    .'<td>'.get_string('sentences', 'diary')
                        .' '.$tempmins.'/'.$diarystats->sentences.'/'.$tempmaxs.'<br>'.$autosentences.'</td>'
                    .'<td>'.get_string('paragraphs', 'diary')
                        .' '.$tempminp.'/'.$diarystats->paragraphs.'/'.$tempmaxp.'<br>'.$autoparagraphs.'</td></tr>'

                .'<tr><td>'.get_string('uniquewords', 'diary').' '.$diarystats->uniquewords.'</td>'
                    .'<td>'.get_string('shortwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('shortwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->shortwords
                         .' ('.number_format($diarystats->shortwords / $diarystats->uniquewords * (100), 2, '.', '')
                         .'%)</td>'
                    .'<td>'.get_string('mediumwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('mediumwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->mediumwords
                         .' ('.number_format($diarystats->mediumwords / $diarystats->uniquewords * (100), 2, '.', '')
                         .'%)</td>'
                    .'<td>'.get_string('longwords', 'diary')
                         .' <a href="#" data-toggle="popover" data-content="'
                         .get_string('longwords_help', 'diary').'">'.$itemp.'</a> '
                         .$diarystats->longwords
                         .' ('.number_format($diarystats->longwords / $diarystats->uniquewords * (100), 2, '.', '')
                         .'%)</td>'

                .'<tr><td>'.get_string('charspersentence', 'diary').' '.$diarystats->charspersentence.'</td>'
                    .'<td>'.get_string('sentencesperparagraph', 'diary').' '.$diarystats->sentencesperparagraph.'</td>'
                    .'<td>'.get_string('wordspersentence', 'diary').' '.$diarystats->wordspersentence.'</td>'
                    .'<td>'.get_string('longwordspersentence', 'diary').' '.$diarystats->longwordspersentence.'</td></tr>'

                .'<tr><td>'.get_string('totalsyllables', 'diary', ($diarystats->totalsyllabels)).' </td>'
                    .'<td>'.get_string('avgsylperword', 'diary',
                           (number_format($diarystats->totalsyllabels / $diarystats->uniquewords, 2, '.', ''))).'</td>'
                    .'<td>'.get_string('avgwordlenchar', 'diary',
                           (number_format($diarystats->characters / $diarystats->words, 2, '.', ''))).'</td>'
                    .'<td>'.get_string('avgwordpara', 'diary',
                           (number_format($diarystats->words / $diarystats->paragraphs, 1, '.', ''))).' </td></tr>'

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
                        .$diarystats->fogindex.'</td></tr>';

                // 20211224 Moved return to prevent undefined variable: currentstats warning.
                return $currentstats;
            } else {
                $currentstats = '<table class="generaltable">';
                $currentstats .= '<tr><td>'.get_string('notextdetected', 'diary').'</td><td> </td><td> </td><td> </td></tr>';
                return $currentstats;

            }
            // 20211212 Moved the echo's to results file so they can be used by the new, Add to feedback, button.
        } else {
            // 20211230 If enablestats is off but autorating is on, we still need to define the start of the table.
            $currentstats = '<table class="generaltable">';
            return $currentstats;
        }
    }

    /**
     * Update the common error statistics, if any, for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @param array $diary The diary info for this entry.
     * @return string $usercommonerrors
     */
    public static function get_common_error_stats($entry, $diary) {
        global $CFG, $OUTPUT;
        $precision = 1;
        // Temporary error fix.
        $errors = array();

        $temp = array();
        $text = self::to_plain_text($entry->text, $entry->format);
        list($errors, $errortext, $erropercent) = self::get_common_errors($text, $diary);
        $diarystats = (object)array('words' => self::get_stats_words($text),
                                    'characters' => self::get_stats_chars($text),
                                    'sentences' => self::get_stats_sentences($text),
                                    'paragraphs' => self::get_stats_paragraphs($text),
                                    'uniquewords' => self::get_stats_uniquewords($text),
                                    'minmaxpercent' => 0,
                                    'shortwords' => 0,
                                    'mediumwords' => 0,
                                    'longwords' => 0,
                                    'item' => 0,
                                    'itempercent' => 0,
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'commonpercent' => 0,
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0,
                                    'totalsyllabels' => 0,
                                    'newtotalsyllabels' => 0,
                                    'fkgrade' => 0,
                                    'freadease' => 0);
        // 20210704 If common errors from the glossary are detected, list them here.
        if ($errors) {
            $x = 1;
            $temp = '';
            foreach ($errors as $error) {
                $temp .= $x.'. '.$error.' ';
                ++$x;
            }

            // 20211028 Put the info in a variable for later use. 20211208 Converted from hardcoded text to string.
            $usercommonerrors = '<tr class="table-warning"><td colspan="4">'
                                .get_string('detectcommonerror', 'diary',
                                ['one' => $diarystats->commonerrors,
                                'two' => get_string('commonerrors', 'diary'),
                                'three' => $temp]).'</td></tr>';
        } else {
            $usercommonerrors = '';
        }

        return $usercommonerrors;
    }

    /**
     * Update the auto rating info, if any, for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @param array $diary The diary info for this entry.
     * @return string $autoratingdata String with table of current autorating data.
     */
    public static function get_auto_rating_stats($entry, $diary) {
        global $CFG, $OUTPUT;
        $precision = 1;
        // Temporary error fix.
        $errors = array();

        $temp = array();
        $text = self::to_plain_text($entry->text, $entry->format);
        list($errors, $errortext, $erropercent) = self::get_common_errors($text, $diary);
        $diarystats = (object)array('words' => self::get_stats_words($text),
                                    'characters' => self::get_stats_chars($text),
                                    'sentences' => self::get_stats_sentences($text),
                                    'paragraphs' => self::get_stats_paragraphs($text),
                                    'uniquewords' => self::get_stats_uniquewords($text),
                                    'minmaxpercent' => 0,
                                    'shortwords' => 0,
                                    'mediumwords' => 0,
                                    'longwords' => 0,
                                    'item' => 0,
                                    'itempercent' => 0,
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'commonpercent' => 0,
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0,
                                    'totalsyllabels' => 0,
                                    'newtotalsyllabels' => 0,
                                    'fkgrade' => 0,
                                    'freadease' => 0);
        // 20210711 Added potential auto rating penalty info. 20211205 Changed from hardcoded text to string.
        $autoratingdata = '<tr class="table-primary"><td colspan="4">'
            .get_string('maxpossrating', 'diary',
            ($diary->scale)).'</td></tr>';
        $currentratingdata = '';

        // 20210814 Show rating info only if enabled and item to rate is NOT = None.
        // if ($diary->enableautorating && $diary->itemtype <> 0) {
        if ($diary->enableautorating
            && ($diary->mincharacterlimit > 0
            || $diary->minwordlimit > 0
            || $diary->minsentencelimit > 0
            || $diary->minparagraphlimit > 0)) {

            // 20220206 Added these two due to string changes.
            $diarystats->commonpercent = $diarystats->commonerrors * $diary->errorpercent;
            $commonerrorrating = $diarystats->commonpercent;

            // 20220904 Character potential auto-rating.
            $autoratecharacters = 0;
            if ($diary->enableautorating
                && $diary->mincharacterlimit > 0
                && $diarystats->characters) {
                $autoratecharacters = ((max($diary->mincharacterlimit - $diarystats->characters, 0)) * $diary->minmaxcharpercent);
            }
            if ($diary->enableautorating
                && $diary->maxcharacterlimit > 0
                && $diarystats->characters > $diary->maxcharacterlimit) {
                $autoratecharacters = ((max($diarystats->characters - $diary->maxcharacterlimit, 0)) * $diary->minmaxcharpercent);
            }

            // 20220904 Word potential auto-rating.
            $autoratewords = 0;
            if ($diary->enableautorating
                && $diary->minwordlimit > 0
                && $diarystats->words) {
                $autoratewords = ((max($diary->minwordlimit - $diarystats->words, 0)) * $diary->minmaxwordpercent);
            }
            if ($diary->enableautorating
                && $diary->maxwordlimit > 0
                && $diarystats->words > $diary->maxwordlimit) {
                $autoratewords = ((max($diarystats->words - $diary->maxwordlimit, 0)) * $diary->minmaxwordpercent);
            }

            // 20220904 Sentence potential auto-rating.
            $autoratesentences = 0;
            if ($diary->enableautorating
                && $diary->minsentencelimit > 0
                && $diarystats->sentences) {
                $autoratesentences = ((max($diary->minsentencelimit - $diarystats->sentences, 0)) * $diary->minmaxsentpercent);
            }
            if ($diary->enableautorating
                && $diary->maxsentencelimit > 0
                && $diarystats->sentences > $diary->maxsentencelimit) {
                $autoratesentences = ((max($diarystats->sentences - $diary->maxsentencelimit, 0)) * $diary->minmaxsentpercent);
            }

            // 20220904 Paragraph potential auto-rating.
            $autorateparagraphs = 0;
            if ($diary->enableautorating
                && $diary->minparagraphlimit > 0
                && $diarystats->paragraphs) {
                $autorateparagraphs = ((max($diary->minparagraphlimit - $diarystats->paragraphs, 0)) * $diary->minmaxparapercent);
            }
            if ($diary->enableautorating
                && $diary->maxparagraphlimit > 0
                && $diarystats->paragraphs > $diary->maxparagraphlimit) {
                $autorateparagraphs = ((max($diarystats->paragraphs - $diary->maxparagraphlimit, 0)) * $diary->minmaxparapercent);
            }

            $potentialratingdisp = $autoratecharacters.' - '
                                   .$autoratewords.' - '
                                   .$autoratesentences.' - '
                                   .$autorateparagraphs.' = '
                                   .($autoratecharacters + $autoratewords + $autoratesentences + $autorateparagraphs);

            $currentratingdisp = $diary->scale.' - '
                                 .$autoratecharacters.' - '
                                 .$autoratewords.' - '
                                 .$autoratesentences.' - '
                                 .$autorateparagraphs.' - '
                                 .$commonerrorrating. ' = '
                                 .($diary->scale - $autoratecharacters
                                                 - $autoratewords
                                                 - $autoratesentences
                                                  - $autorateparagraphs
                                                  - $commonerrorrating);

            $autoratingdata .= '<tr><td colspan="4" class="table-danger">'
                .get_string('potautoratingerrpen', 'diary',
                ['one' => $potentialratingdisp,
                'two' => ($autoratecharacters + $autoratewords + $autoratesentences + $autorateparagraphs - $commonerrorrating)])
                .'</td></tr>';

            // Show possible Glossary of common errors penalty. 20211208 Converted hardcoded text to string using {$a}.
            $autoratingdata .= '<tr><td colspan="4" class="table-danger">'
                 .get_string('potcommerrpen', 'diary',
                 ['one' => $diarystats->commonerrors,
                 'two' => $diary->errorpercent,
                 'three' => $diarystats->commonpercent,
                 'four' => $commonerrorrating]).'</td></tr>';

            // 20211007 Calculate and show the possible overall rating. Modified 20211119. Modified 20220904.
            $autoratingdata .= '<tr><td colspan="4" class="table-danger">'
                            .get_string('currpotrating', 'diary',
                            ['one' => $currentratingdisp,
                            'two' => (max($diary->scale - $autoratecharacters
                                                        - $autoratewords
                                                         - $autoratesentences
                                                         - $autorateparagraphs
                                                         - $commonerrorrating, 0))])
                            .'</td></tr>';

            $currentratingdata = (max($diary->scale - $autoratecharacters
                                                    - $autoratewords
                                                     - $autoratesentences
                                                     - $autorateparagraphs
                                                     - $commonerrorrating, 0));
        }
        // 20211208 Cannot add buttons here because they will also show to everyone on the view page.
        $autoratingdata .= '</table>';

        // 20211230 Return autoratingdata only if autorating is enabled.
        if ($diary->enableautorating) {
            // 20211212 Return list of data to results.
            return array($autoratingdata, $currentratingdata);
        } else {
            return;
        }
    }

    /**
     * Add statistics to the feedback for this diary entry.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of characters.
     */
    public static function add_stats($entry) {
        $entry->entrycomment = $currentstats;
        $entry->entrycomment .= $usercommonerrors;
        return;
    }

    /**
     * Update the diary character count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @ return int The number of characters.
     */
    public static function get_stats_chars($entry) {
        return strlen($entry);
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
     * @param string $text The text for this entry.
     * @ return int $items The number of paragraphs.
     */
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
    public static function multipleexplode($delimiters, $entry) {
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
            $syllable = syllables::syllable_count($item, $strencoding = '');

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
            $totalsyllabels += syllables::syllable_count($item, $strencoding = '');
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
        // $itemtypes['5'] = get_string('files', 'diary'); // @codingStandardsIgnoreLine
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
        for ($i = 0; $i <= 100; $i++) {
            $options[$i] = get_string('percentofentryrating', $plugin, $i);
        }
        return $options;
    }

    /**
     * Get array of show/hide options
     * Called from mod_form.php.
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
     * Update the list of item min/maxes in this activities intro/desciption.
     *
     * @param string $diary The diary containing the min/maxes.
     * @return nothing
     */
    public static function get_minmaxes($diary) {
        // 20210710 Add checks and description additions for mins and maxes.
        // This is temporary and probably needs to be moved to somewhere else so
        // it can be shown on the edit.php page, too. Maybe move to results.php.
        if ($diary->mincharacterlimit > 0) {
            $diary->intro .= '<br>'.get_string('mincharacterlimit_desc', 'diary', ($diary->mincharacterlimit)).'<br>';
        }
        if ($diary->maxcharacterlimit > 0) {
            $diary->intro .= get_string('maxcharacterlimit_desc', 'diary', ($diary->maxcharacterlimit)).'<br>';
        }
        if ($diary->minwordlimit > 0) {
            $diary->intro .= get_string('minwordlimit_desc', 'diary', ($diary->minwordlimit)).'<br>';
        }
        if ($diary->maxwordlimit > 0) {
            $diary->intro .= get_string('maxwordlimit_desc', 'diary', ($diary->maxwordlimit)).'<br>';
        }
        if ($diary->minsentencelimit > 0) {
            $diary->intro .= get_string('minsentencelimit_desc', 'diary', ($diary->minsentencelimit)).'<br>';
        }
        if ($diary->maxsentencelimit > 0) {
            $diary->intro .= get_string('maxsentencelimit_desc', 'diary', ($diary->maxsentencelimit)).'<br>';
        }
        if ($diary->minparagraphlimit > 0) {
            $diary->intro .= get_string('minparagraphlimit_desc', 'diary', ($diary->minparagraphlimit)).'<br>';
        }
        if ($diary->maxparagraphlimit > 0) {
            $diary->intro .= get_string('maxparagraphlimit_desc', 'diary', ($diary->maxparagraphlimit)).'<br>';
        }
        return;
    }
}
