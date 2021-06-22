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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die();

use mod_diary\local\diarystats;
use stdClass;
use core_text;

/**
 * Utility class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diarystats {

    /**
     * Update the diary statistics for this diary activity.
     *
     * @param string $entry The text for this entry.
     * @return bool
     */
    public static function get_diary_stats($entry) {
    // @codingStandardsIgnoreLine
    // public function get_diary_stats($entry) {
        // global $DB, $CFG;
        $precision = 1;

/*
        $diarystats = array();
        $diarystats['words'] = self::get_stats_words($entry);
        $diarystats['chars'] = self::get_stats_chars($entry);
        $diarystats['sentences'] = self::get_stats_sentences($entry);
        $diarystats['paragraphs'] = self::get_stats_paragraphs($entry);
        $diarystats['uniquewords'] = self::get_stats_uniquewords($entry);
        $diarystats['longwords'] = self::get_stats_longwords($entry);
                    'fogindex' => 0,
                    'commonerrors' => count($errors),
                    'lexicaldensity' => 0,
                    'charspersentence' => 0,
                    'wordspersentence' => 0,
                    'longwordspersentence' => 0,
                    'sentencesperparagraph' => 0);
*/
        // @codingStandardsIgnoreLine
        // print_object('This is the $diarystats array.');
        // print_object($diarystats);

        $diarystats = (object)array('words' => self::get_stats_words($entry),
                                    'chars' => self::get_stats_chars($entry),
                                    'sentences'] => self::get_stats_sentences($entry),
                                    'paragraphs'] => self::get_stats_paragraphs($entry),
                                    'uniquewords'] => self::get_stats_uniquewords($entry),
                                    'longwords'] => self::get_stats_longwords($entry),
                                    'fogindex' => 0,
                                    'commonerrors' => count($errors),
                                    'lexicaldensity' => 0,
                                    'charspersentence' => 0,
                                    'wordspersentence' => 0,
                                    'longwordspersentence' => 0,
                                    'sentencesperparagraph' => 0);

        if ($diarystats->words) {
            $diarystats->lexicaldensity = round(($diarystats->uniquewords / $diarystats->words) * 100, 0).'%';
        }
        if ($diarystats->sentences) {
            $diarystats->charspersentence = round($diarystats->chars / $diarystats->sentences, $precision);
            $diarystats->wordspersentence = round($diarystats->words / $diarystats->sentences, $precision);
            $diarystats->longwordspersentence = round($diarystats->longwords / $diarystats->sentences, $precision);
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
        return count_words($entry);
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
    public static function get_stats_paragraphs($entry) {
        $items = explode("\n", $entry);
        $items = array_filter($items);
        return count($items);
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
            if ($this->count_syllables($item) > 2) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Update the number of syllables per word.
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
}
