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
 * @copyright based on work by 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die();

use mod_diary\local\diarystats;
use stdClass;
use core_text;

require_once($CFG->dirroot .'/question/engine/lib.php');


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


    public function update_current_response($response, $displayoptions=null) {
        global $CFG, $PAGE, $USER;

print_object('In the update_current_response function at line 48.');

        /*
        if (empty($CFG->enableplagiarism)) {
            $enableplagiarism = false;
            $plagiarismparams = array();
            $context = null;
            $course = null;
            $cm = null;
        } else {
            $enableplagiarism = true;
            require_once($CFG->dirroot.'/lib/plagiarismlib.php');

            list($context, $course, $cm) = get_context_info_array($PAGE->context->id);
            $plagiarismparams = array(
                'userid' => $USER->id
            );
            if ($course) {
                $plagiarismparams['course'] = $course->id;
            }
            if ($cm) {
                $plagiarismparams['cmid'] = $cm->id;
                $plagiarismparams[$cm->modname] = $cm->instance;
            }
        }
       */


        // Initialize data about this $response
        $count = 0;
        $bands = array();
        $phrases = array();
        $myphrases = array();
        $plagiarism = array();
        $breaks = 0;
        $rawpercent = 0;
        $rawfraction = 0.0;
        $autopercent = 0;
        $autofraction = 0.0;
        $currentcount = 0;
        $currentpercent = 0;
        $partialcount = 0;
        $partialpercent = 0;
        $completecount = 0;
        $completepercent = 0;

        if ($text = self::get_response_answer_text($response)) {
            self::set_template_and_sample_text();
            if (self::is_similar_text($text, self::responsetemplatetext)) {
                $text = '';
            } else if (self::is_similar_text($text, self::responsesampletext)) {
                $text = '';
            }
        }
        if ($enableplagiarism) {
            $plagiarism[] = plagiarism_get_links($plagiarismparams + array('content' => $text));
        }

        if (empty($response) || empty($response['attachments'])) {
            $files = array();
        } else {
            $files = $response['attachments']->get_files();
        }
        if ($enableplagiarism) {
            foreach ($files as $file) {
                $plagiarism[] = plagiarism_get_links($plagiarismparams + array('file' => $file));
            }
        }

        // detect common errors
        list($errors, $errorpercent) = self::get_common_errors($text);

        // Get stats for this $text.
        $stats = self::get_stats($text, $files, $errors);

        // Count items in $text.
        switch (self::itemtype) {
            case self::plugin_constant('ITEM_TYPE_CHARS'): $count = $stats->chars; break;
            case self::plugin_constant('ITEM_TYPE_WORDS'): $count = $stats->words; break;
            case self::plugin_constant('ITEM_TYPE_SENTENCES'): $count = $stats->sentences; break;
            case self::plugin_constant('ITEM_TYPE_PARAGRAPHS'): $count = $stats->paragraphs; break;
            case self::plugin_constant('ITEM_TYPE_FILES'): $count = $stats->files; break;
        }

        // Get records from "question_answers" table.
        $answers = self::get_answers();

        if (empty($answers) || (self::itemtype == self::plugin_constant('ITEM_TYPE_FILES'))) {

            // Set fractional grade from number of items.
            if (empty(self::itemcount)) {
                $rawfraction = 0.0;
            } else {
                $rawfraction = ($count / self::itemcount);
            }

        } else {

            // Cache plugin constants.
            $ANSWER_TYPE_BAND = self::plugin_constant('ANSWER_TYPE_BAND');
            $ANSWER_TYPE_PHRASE = self::plugin_constant('ANSWER_TYPE_PHRASE');

            // override "addpartialgrades" with incoming form data, if necessary
            $addpartialgrades = self::addpartialgrades;
            $addpartialgrades = optional_param('addpartialgrades', $addpartialgrades, PARAM_INT);

            // set fractional grade from item count and target phrases
            $rawfraction = 0.0;
            $checkbands = true;
            foreach ($answers as $answer) {
                switch ($answer->type) {

                    case $ANSWER_TYPE_BAND:
                        if ($checkbands) {
                            if ($answer->answer > $count) {
                                $checkbands = false;
                            }
                            // update band counts and percents
                            $completecount   = $currentcount;
                            $completepercent = $currentpercent;
                            $currentcount    = $answer->answer;
                            $currentpercent  = $answer->answerformat;
                        }
                        $bands[$answer->answer] = $answer->answerformat;
                        break;

                    case $ANSWER_TYPE_PHRASE:
                        if ($search = trim($answer->feedback)) {
                            if ($match = self::search_text($search, $text, $answer->fullmatch, $answer->casesensitive, $answer->ignorebreaks)) {
                                $rawfraction += ($answer->feedbackformat / 100);
                                $myphrases[$match] = $search;
                            } else if (empty($answer->ignorebreaks) && preg_match('/\\bAND|ANY\\b/', $search) && preg_match("/[\r\n]/us", $text)) {
                                $breaks++;
                            }
                            $phrases[$search] = $answer->feedbackformat;
                        }
                        break;
                }
            }

            // update band counts for top grade band, if necessary
            if ($checkbands) {
                $completecount = $currentcount;
                $completepercent = $currentpercent;
            }

            // set the item width of the current band
            // and the percentage width of the current band
            $currentcount = ($currentcount - $completecount);
            $currentpercent = ($currentpercent - $completepercent);

            // set the number of items to be graded by the current band
            // and thus calculate the percent awarded by the current band
            if ($addpartialgrades && $currentcount) {
                $partialcount = ($count - $completecount);
                $partialpercent = round(($partialcount / $currentcount) * $currentpercent);
            } else {
                $partialcount = 0;
                $partialpercent = 0;
            }

            $rawfraction += (($completepercent + $partialpercent) / 100);

        }

        // deduct penalties for common errors
        $rawfraction -= ($errorpercent / 100);

        // make sure $autofraction is in range 0.0 - 1.0
        $autofraction = min(1.0, max(0.0, $rawfraction));

        // we can now set $autopercent and $rawpercent
        $rawpercent = round($rawfraction * 100);
        $autopercent = round($autofraction * 100);

        // Store this information, in case it is needed elswhere.
        self::save_current_response('text', $text);
        self::save_current_response('stats', $stats);
        self::save_current_response('count', $count);
        self::save_current_response('bands', $bands);
        self::save_current_response('phrases', $phrases);
        self::save_current_response('myphrases', $myphrases);
        self::save_current_response('plagiarism', $plagiarism);
        self::save_current_response('breaks', $breaks);
        self::save_current_response('rawpercent', $rawpercent);
        self::save_current_response('rawfraction', $rawfraction);
        self::save_current_response('autopercent', $autopercent);
        self::save_current_response('autofraction', $autofraction);
        self::save_current_response('partialcount', $partialcount);
        self::save_current_response('partialpercent', $partialpercent);
        self::save_current_response('completecount', $completecount);
        self::save_current_response('completepercent', $completepercent);
        self::save_current_response('displayoptions', $displayoptions);
        self::save_current_response('errors', $errors);
        self::save_current_response('errorpercent', $errorpercent);
    }

// Currently, this following function breaks things.
    /**
     * Store information about latest response to this question.
     *
     * @param  string $name
     * @param  string $value
     * @return void, but will update currentresponse property of this object
     */
    //public function save_current_response($name, $value) {
 //   public function save_current_response($name) {
//print_object('In the save_current_response function at line 253.');

 //       if (self::currentresponse===null) {
 //           self::currentresponse = new stdClass();
 //       }
//        self::currentresponse->$name = $value;
 //   }

    /**
     * glossary_entry_search_text
     *
     * @param object $entry
     * @param string $match
     * @param string $text
     * @return string The matching substring in $text or "".
     */
    protected function glossary_entry_search_text($entry, $search, $text) {
        return self::search_text($search, $text, $entry->fullmatch, $entry->casesensitive);
    }


    /**
     * set_template_and_sample_text
     */
    protected function set_template_and_sample_text() {
        if (self::responsetemplatetext === null) {
            $responsetemplatetext = self::to_plain_text(
                self::responsetemplate, self::responsetemplateformat
            );
        }
        if (self::responsesampletext === null) {
            $responsesampletext = self::to_plain_text(
                self::responsesample, self::responsesampleformat
            );
        }
    }

    /**
     * get_response_answer_text($response)
     */
    protected function get_response_answer_text($response) {
        if (empty($response) || empty($response['answer'])) {
            return '';
        }
        return self::to_plain_text($response['answer'], $response['answerformat']);
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Convert current entry to_plain_text.
     *
     * @param $text The current Diary entry.
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
     * @param Stext string The current diary entry.
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
     * search_text
     *
     * @param string $match
     * @param string $text
     * @return boolean TRUE if $text mattches the $match; otherwise FALSE;
     */
    public static function search_text($search, $text, $fullmatch=false, $casesensitive=false, $ignorebreaks=true) {

        $text = trim($text);
        if ($text=='') {
            return false; // unexpected ?!
        }

        $search = trim($search);
        if ($search=='') {
            return false; // shouldn't happen !!
        }

        if (self::$aliases===null) {
            // human readable aliases for regexp strings
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

            // allowable regexp strings and their internal aliases
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
            $regexp .= 'i';
        }
        if ($ignorebreaks) {
            $regexp .= 's';
        }
        if (preg_match($regexp, $text, $match)) {
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
    public static function get_diary_stats($entry, $format) {
        $precision = 1;

        // Temporary error fix.
        $errors = array();
        //$plaintext = question_utils::to_plain_text($text, $format, array('para' => false));
        //$plaintext = to_plain_text($text, $format, array('para' => false));
        //$plaintext = self::standardize_white_space($plaintext);

        //$temp = htmlspecialchars(trim(strip_tags($entry)));
        $text = self::to_plain_text($entry, $format);

//print_object('In the get_diary_stats function printing plaintext.');
//print_object($plaintext);

        $diarystats = (object)array('words' => self::get_stats_words($text),
                                    'chars' => self::get_stats_chars($text),
                                    'sentences' => self::get_stats_sentences($text),
                                    'paragraphs' => self::get_stats_paragraphs($text),
                                    'uniquewords' => self::get_stats_uniquewords($text),
                                    'longwords' => self::get_stats_longwords($text),
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0,
                                    'totalsyllabels' =>  self::get_stats_totalsyllables($text),
                                    'fkgrade' => 0,
                                    'fkreadease' => 0);

        if ($diarystats->words) {
            $diarystats->lexicaldensity = round(($diarystats->uniquewords / $diarystats->words) * 100, 0).'%';
        }
        if ($diarystats->sentences) {
            $diarystats->charspersentence = round($diarystats->chars / $diarystats->sentences, $precision);
            $diarystats->wordspersentence = round($diarystats->words / $diarystats->sentences, $precision);
            $diarystats->longwordspersentence = round($diarystats->longwords / $diarystats->sentences, $precision);
            $diarystats->fkgrade = round(0.39 * ($diarystats->words / $diarystats->sentences) + 11.8 * ($diarystats->totalsyllabels / $diarystats->words) - 15.59, $precision);
            $diarystats->fkreadease = round(206.835 - 1.015 * ($diarystats->words / $diarystats->sentences) - 84.6 * ($diarystats->totalsyllabels / $diarystats->words), $precision);
        }
        if ($diarystats->wordspersentence) {
            $diarystats->fogindex = ($diarystats->wordspersentence + $diarystats->longwordspersentence);
            $diarystats->fogindex = round($diarystats->fogindex * 0.4, $precision);
        }
        if ($diarystats->paragraphs) {
            $diarystats->sentencesperparagraph = round($diarystats->sentences / $diarystats->paragraphs, $precision);
        }

        return $diarystats;
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
        // Need to move this  and use for all the stats.
        //$text = self::to_plain_text($entry, $format);
        //print_object($entry);
        //print_object($text);
        //$items = explode("\n", $entry);
        //$items = explode("\r\n", $entry);
        // this works $items = explode("<p", $entry);
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
        // @codingStandardsIgnoreLine
        // $items = strtolower($entry);
        $items = str_word_count($items, 1);
        $items = array_unique($items);
        return count($items);
    }

    /**
     * Update the diary long word count statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @return int The number of long words.
     */
    public static function get_stats_longwords($entry) {
        $count = 0;
        $items = core_text::strtolower($entry);
        $items = str_word_count($items, 1);
        $items = array_unique($items);
        foreach ($items as $item) {
            if (self::count_syllables($item) > 2) {
                $count++;
            }
        }
        return $count;
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
        $str = strtoupper($word);
        $oldlen = strlen($str);
        if ($oldlen < 2) {
            $count = 1;
        } else {
            $count = 0;

            // detect syllables for double-vowels
            $vowels = array('AA','AE','AI','AO','AU',
                            'EA','EE','EI','EO','EU',
                            'IA','IE','II','IO','IU',
                            'OA','OE','OI','OO','OU',
                            'UA','UE','UI','UO','UU');
            $str = str_replace($vowels, '', $str);
            $newlen = strlen($str);
            $count += (($oldlen - $newlen) / 2);

            // detect syllables for single-vowels
            $vowels = array('A','E','I','O','U');
            $str = str_replace($vowels, '', $str);
            $oldlen = $newlen;
            $newlen = strlen($str);
            $count += ($oldlen - $newlen);

            // adjust count for special last char
            switch (substr($str, -1)) {
                case 'E': $count--; break;
                case 'Y': $count++; break;
            };
        }
        return $count;
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
            $totalsyllabels += self::count_syllables($item);
        }
        return $totalsyllabels;
    }
}
