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
 * @copyright based on work by 2014 Dave Child
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Syllables class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllables {

    // Specific common exceptions that don't follow the rule set below are handled individually
    // array of problem words (with word as key, syllable count as value).
    // Common reasons we need to override some words:
    // - Trailing 'e' is pronounced.
    // - Portmanteaus.
    static public $arrproblemwords = array(
        'abalone'     => 4,
        'abare'       => 3,
        'abed'        => 2,
        'abruzzese'   => 4,
        'abbruzzese'  => 4,
        'aborigine'   => 5,
        'acreage'     => 3,
        'adame'       => 3,
        'adieu'       => 2,
        'adobe'       => 3,
        'anemone'     => 4,
        'apache'      => 3,
        'aphrodite'   => 4,
        'apostrophe'  => 4,
        'ariadne'     => 4,
        'cafe'        => 2,
        'calliope'    => 4,
        'catastrophe' => 4,
        'chile'       => 2,
        'chloe'       => 2,
        'circe'       => 2,
        'coyote'      => 3,
        'doing'       => 2,
        'epitome'     => 4,
        'forever'     => 3,
        'gethsemane'  => 4,
        'going'       => 2,
        'guacamole'   => 4,
        'hyperbole'   => 4,
        'jesse'       => 2,
        'jukebox'     => 2,
        'karate'      => 3,
        'machete'     => 3,
        'maybe'       => 2,
        'people'      => 2,
        'recipe'      => 3,
        'sesame'      => 3,
        'shoreline'   => 2,
        'sided'       => 2,
        'simile'      => 3,
        'squelches'   => 2,
        'syncope'     => 3,
        'tamale'      => 3,
        'yosemite'    => 4,
        'daphne'      => 2,
        'eurydice'    => 4,
        'euterpe'     => 3,
        'hermione'    => 4,
        'penelope'    => 4,
        'persephone'  => 4,
        'phoebe'      => 2,
        'zoe'         => 2,
        'version'     => 2
        );

    // These syllables would be counted as two but should be one.
    static public $arrsubsyllables = array(
            'cia(l|$)', // Use in words such as glacial, acacia.
            'tia',
            'cius',
            'cious',
            '[^aeiou]giu',
            '[aeiouy][^aeiouy]ion',
            'iou',
            'sia$',
            'eous$',
            '[oa]gue$',
            '.[^aeiuoycgltdb]{2,}ed$',
            '.ely$',
            /*
            // ...'[cg]h?ed?$',.
            // ...'rved?$',.
            // ...'[aeiouy][dt]es?$',.
            // ...'^[dr]e[aeiou][^aeiou]+$', // Sorts out deal, deign etc.
            // ...'[aeiouy]rse$', // Purse, hearse.
            */
            '^jua',
            /*
            // ...'nne[ds]?$', // ...canadienne.
            */
            'uai', // ...acquainted.
            'eau', // ...champeau.
            /*
            // 'pagne[ds]?$', // champagne
            // '[aeiouy][^aeiuoytdbcgrnzs]h?e[rsd]?$',
            // The following detects words ending with a soft e ending. Don't
            // mess with it unless you absolutely have to! The following
            // is a list of words you can use to test a new version of
            // this rule (add 'r', 's' and 'd' where possible to test
            // fully):
            // - absolve
            // - acquiesce
            // - audience
            // - ache
            // - acquire
            // - brunelle
            // - byrne
            // - canadienne
            // - coughed
            // - curved
            // - champagne
            // - designate
            // - force
            // - lace
            // - late
            // - lathe
            // - make
            // - relayed
            // - scrounge
            // - side
            // - sideline
            // - some
            // - wide
            // - taste
            */
           '[aeiouy](b|c|ch|d|dg|f|g|gh|gn|k|l|ll|lv|m|mm|n|nc|ng|nn|p|r|rc|rn|rs|rv|s|sc|sk|sl|squ|ss|st|t|th|v|y|z)e$',
            /*
            // For soft e endings with a "d". Test words:
            // - crunched
            // - forced
            // - hated
            // - sided
            // - sidelined
            // - unexploded
            // - unexplored
            // - scrounged
            // - squelched
            // - forced
            */
            '[aeiouy](b|c|ch|dg|f|g|gh|gn|k|l|lch|ll|lv|m|mm|n|nc|ng|nch|nn|p|r|rc|rn|rs|rv|s|sc|sk|sl|squ|ss|th|v|y|z)ed$',
            /*
            // For soft e endings with a "s". Test words:
            // - absences
            // - accomplices
            // - acknowledges
            // - advantages
            // - byrnes
            // - crunches
            // - forces
            // - scrounges
            // - squelches
            */
            '[aeiouy](b|ch|d|f|gh|gn|k|l|lch|ll|lv|m|mm|n|nch|nn|p|r|rn|rs|rv|s|sc|sk|sl|squ|ss|st|t|th|v|y)es$',
            '^busi$'
        );

    // These syllables would be counted as one but should be two.
    static public $arraddsyllables = array(
        '([^s]|^)ia',
        'riet',
        'dien', // ...audience.
        'iu',
        'io',
        'eo($|[b-df-hj-np-tv-z])',
        'ii',
        '[ou]a$',
        '[aeiouym]bl$',
        '[aeiou]{3}',
        '[aeiou]y[aeiou]',
        '^mc',
        'ism$',
        'asm$',
        'thm$',
        '([^aeiouy])\1l$',
        '[^l]lien',
        '^coa[dglx].',
        '[^gq]ua[^auieo]',
        'dnt$',
        'uity$',
        '[^aeiouy]ie(r|st|t)$',
        'eings?$',
        '[aeiouy]sh?e[rsd]$',
        'iell',
        'dea$',
        'real', // ...real, cereal.
        '[^aeiou]y[ae]', // ...bryan, byerley.
        'gean$', // ...aegean.
        'uen' // ...influence, affluence.
    );

    // Single syllable prefixes and suffixes.
    static public $arraffix = array(
        '`^un`',
        '`^fore`',
        '`^ware`',
        '`^none?`',
        '`^out`',
        '`^post`',
        '`^sub`',
        '`^pre`',
        '`^pro`',
        '`^dis`',
        '`^side`',
        '`ly$`',
        '`less$`',
        '`some$`',
        '`ful$`',
        '`ers?$`',
        '`ness$`',
        '`cians?$`',
        '`ments?$`',
        '`ettes?$`',
        '`villes?$`',
        '`ships?$`',
        '`sides?$`',
        '`ports?$`',
        '`shires?$`',
        '`tion(ed)?$`'
    );

    // Double syllable prefixes and suffixes.
    static public $arrdoubleaffix = array(
        '`^above`',
        '`^ant[ie]`',
        '`^counter`',
        '`^hyper`',
        '`^afore`',
        '`^agri`',
        '`^in[ft]ra`',
        '`^inter`',
        '`^over`',
        '`^semi`',
        '`^ultra`',
        '`^under`',
        '`^extra`',
        '`^dia`',
        '`^micro`',
        '`^mega`',
        '`^kilo`',
        '`^pico`',
        '`^nano`',
        '`^macro`',
        '`berry$`',
        '`woman$`',
        '`women$`'
    );

    // Triple syllable prefixes and suffixes.
    static public $arrtripleaffix = array(
         '`ology$`'
        , '`ologist$`'
        , '`onomy$`'
        , '`onomist$`'
    );

    /**
     * Returns the number of syllables in the word.
     * Called from dairystats.php about line 789.
     * Based in part on Greg Fast's Perl module Lingua::EN::Syllables.
     * @param   string  $strword      Word to be measured.
     * @param   string  $strencoding  Encoding of text.
     * @return  int
     */
    public static function syllable_count($strword, $strencoding = '') {

        // Trim whitespace.
        $strword = trim($strword);

        // Check we have some letters.
        /*
        if (Text::letterCount(trim($strword), $strencoding) == 0) {
            return 0;
        }
        */

        // The variable $debug is an array containing the basic syllable counting steps for this word.
       // $debug = array();
       // $debug['CP1-0 Just entered syllable_count function and checking this word: '] = $strword;

        // Should be no non-alpha characters and lower case.
        $strword = preg_replace('`[^A-Za-z]`', '', $strword);
        $strword = strtolower($strword);

        // Check for problem words.
        if (isset(self::$arrproblemwords[$strword])) {
           // $debug['CP1-1a Found a problem word '] = $strword;
           // $debug['CP1-1b It has a defined syllable count of '] = self::$arrproblemwords[$strword];
            // print_object($debug);
            return self::$arrproblemwords[$strword];
        }
        // Try singular.
        $singularword = pluralise::get_singular($strword);
        if ($singularword != $strword) {
            if (isset(self::$arrproblemwords[$singularword])) {
               // $debug['CP1-2a Found a plural problem word'] = $strword;
               // $debug['CP1-2a It has a defined syllable count of'] = self::$arrproblemwords[$singularword];
                // print_object($debug);
                return self::$arrproblemwords[$singularword];
            }
        }

       // $debug['CP1-3 After cleaning, lcase'] = $strword;

        // Remove prefixes and suffixes and count how many were taken.
        $strword = preg_replace(self::$arraffix, '', $strword, -1, $intaffixcount);
        $strword = preg_replace(self::$arrdoubleaffix, '', $strword, -1, $intdoubleaffixcount);
        $strword = preg_replace(self::$arrtripleaffix, '', $strword, -1, $inttripleaffixcount);

        if (($intaffixcount + $intdoubleaffixcount + $inttripleaffixcount) > 0) {
           // $debug['CP1-4a After Prefix and Suffix Removal'] = $strword;
           // $debug['CP1-4b Prefix and suffix counts'] = $intaffixcount.' * 1 syllable, '.$intdoubleaffixcount.' * 2 syllables, '.$inttripleaffixcount.' * 3 syllables';
        }

        // Removed non-word characters from word
        $arrwordparts = preg_split('`[^aeiouy]+`', $strword);
        $intwordpartcount = 0;
        foreach ($arrwordparts as $strwordpart) {
            if ($strwordpart <> '') {
               // $debug['CP1-5 Counting (' . $intwordpartcount . ')'] = $strwordpart;
                $intwordpartcount++;
            }
        }

        // Some syllables do not follow normal rules - check for them
        // Thanks to Joe Kovar for correcting a bug in the following lines
        $intsyllablecount = $intwordpartcount + $intaffixcount + (2 * $intdoubleaffixcount) + (3 * $inttripleaffixcount);
       // $debug['CP1-6 Syllables by Vowel Count'] = $intsyllablecount;

        foreach (self::$arrsubsyllables as $strsyllable) {
            $_intsyllablecount = $intsyllablecount;
            $intsyllablecount -= preg_match('`' . $strsyllable . '`', $strword);
            if ($_intsyllablecount != $intsyllablecount) {
               // $debug['CP1-7 Subtracting (' . $strsyllable . ')'] = $strsyllable;
            }
        }
        foreach (self::$arraddsyllables as $strsyllable) {
            $_intsyllablecount = $intsyllablecount;
            $intsyllablecount += preg_match('`' . $strsyllable . '`', $strword);
            if ($_intsyllablecount != $intsyllablecount) {
               // $debug['CP1-8 Adding (' . $strsyllable . ')'] = $strsyllable;
            }
        }
        $intsyllablecount = ($intsyllablecount == 0) ? 1 : $intsyllablecount;

        // print_object($debug);

        return $intsyllablecount;
    }

    /**
     * Returns total syllable count for text.
     * @param   string  $strtext      Text to be measured.
     * @param   string  $strencoding  Encoding of text.
     * @return  int
     */
    public static function total_syllables($strtext, $strencoding = '') {
        // The variable $debug is an array containing the basic syllable counting steps for this word.
       // $debug = array();
       // $debug['CP2-0 Just entered total_syllables function and checking $strtext: '] = $strtext;

        // Removed the extra code from diarystats line 355 to run this.
        $intsyllablecount = 0;
        $arrwords = explode(' ', $strtext);
        $intwordcount = count($arrwords);
        for ($i = 0; $i < $intwordcount; $i++) {
            $intsyllablecount += self::syllable_count($arrwords[$i], $strencoding);
        }
        // print_object($debug);

        return $intsyllablecount;
    }

    /**
     * Returns average syllables per word for text.
     * @param   string  $strtext      Text to be measured.
     * @param   string  $strencoding  Encoding of text.
     * @return  int|float
     */
    public static function average_syllables_per_word($strtext, $strencoding = '') {
       // $debug = array();
       // $debug['CP3-0 Just entered average_syllables_per_word function and checking $strtext: '] = $strtext;

        $intsyllablecount = 0;
        $intwordcount = text::word_count($strtext, $strencoding);
        $arrwords = explode(' ', $strtext);
        for ($i = 0; $i < $intwordcount; $i++) {
            $intsyllablecount += self::syllable_count($arrwords[$i], $strencoding);
        }
        $averagesyllables = (maths::bc_calc($intsyllablecount, '/', $intwordcount));
        // print_object($debug);

        return $averagesyllables;
    }

    /**
     * Returns the number of words with more than three syllables.
     * @param   string  $strtext                  Text to be measured.
     * @param   bool    $blncountpropernouns      Boolean - should proper nouns be included in words count.
     * @param   string  $strencoding  Encoding of text.
     * @return  int
     */
    public static function words_with_three_syllables($strtext, $blncountpropernouns = true, $strencoding = '') {
       // $debug = array();
       // $debug['CP4-0 Just entered words_with_three_syllables function and checking $strtext: '] = $strtext;

        $intlongwordcount = 0;
        $intwordcount = text::word_count($strtext, $strencoding);
        $arrwords = explode(' ', $strtext);
        for ($i = 0; $i < $intwordcount; $i++) {
            if (Syllables::syllableCount($arrwords[$i], $strencoding) > 2) {
                if ($blncountpropernouns) {
                    $intlongwordcount++;
                } else {
                    $strfirstletter = text::substring($arrwords[$i], 0, 1, $strencoding);
                    if ($strfirstletter !== text::upper_case($strfirstletter, $strencoding)) {
                        // First letter is lower case. Count it.
                        $intlongwordcount++;
                    }
                }
            }
        }
        // print_object($debug);
        return $intlongwordcount;
    }

    /**
     * Not currently used. Verified by no errors and not using Text:: and Maths::.
     * Returns the percentage of words with more than three syllables.
     * @param   string  $strtext      Text to be measured.
     * @param   bool    $blncountpropernouns      Boolean - should proper nouns be included in words count.
     * @return  int|float
     */
    public static function percentage_words_with_three_syllables($strtext, $blncountpropernouns = true, $strencoding = '') {
       // $debug = array();
       // $debug['CP4-0 Just entered percentage_words_with_three_syllables function and checking $strtext: '] = $strtext;

        $intwordcount = text::word_count($strtext, $strencoding);
        $intlongwordcount = self::words_with_three_syllables($strtext, $blncountpropernouns, $strencoding);
        $intpercentage = maths::bcCalc(maths::bc_calc($intlongwordcount, '/', $intwordcount), '*', 100);
        // print_object($debug);

        return $intpercentage;
    }
}
