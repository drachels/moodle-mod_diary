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
 * From https://github.com/DaveChild/Text-Statistics (English only).
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

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
    /** @var array */
    static public $arrproblemwords =
        [
            'abalone' => 4,
            'abare' => 3,
            'abed' => 2,
            'abruzzese' => 4,
            'abbruzzese' => 4,
            'aborigine' => 5,
            'acreage' => 3,
            'adame' => 3,
            'adieu' => 2,
            'adobe' => 3,
            'anemone' => 4,
            'apache' => 3,
            'aphrodite' => 4,
            'apostrophe' => 4,
            'ariadne' => 4,
            'cafe' => 2,
            'calliope' => 4,
            'catastrophe' => 4,
            'chile' => 2,
            'chloe' => 2,
            'circe' => 2,
            'coyote' => 3,
            'doing' => 2,
            'epitome' => 4,
            'forever' => 3,
            'gethsemane' => 4,
            'going' => 2,
            'guacamole' => 4,
            'hyperbole' => 4,
            'jesse' => 2,
            'jukebox' => 2,
            'karate' => 3,
            'machete' => 3,
            'maybe' => 2,
            'people' => 2,
            'recipe' => 3,
            'sesame' => 3,
            'shoreline' => 2,
            'sided' => 2,
            'simile' => 3,
            'squelches' => 2,
            'syncope' => 3,
            'tamale' => 3,
            'yosemite' => 4,
            'daphne' => 2,
            'eurydice' => 4,
            'euterpe' => 3,
            'hermione' => 4,
            'penelope' => 4,
            'persephone' => 4,
            'phoebe' => 2,
            'zoe' => 2,
            'version' => 2,
        ];

    // These syllables would be counted as two but should be one.
    /** @var array */
    static public $arrsubsyllables =
        [
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
            '^busi$',
        ];

    // These syllables would be counted as one but should be two.
    /** @var array */
    static public $arraddsyllables =
        [
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
            'uen', // ...influence, affluence.
        ];

    // Single syllable prefixes and suffixes.
    /** @var array */
    static public $arraffix =
        [
            '`^un`',        // @codingStandardsIgnoreLine
            '`^fore`',      // @codingStandardsIgnoreLine
            '`^ware`',      // @codingStandardsIgnoreLine
            '`^none?`',     // @codingStandardsIgnoreLine
            '`^out`',       // @codingStandardsIgnoreLine
            '`^post`',      // @codingStandardsIgnoreLine
            '`^sub`',       // @codingStandardsIgnoreLine
            '`^pre`',       // @codingStandardsIgnoreLine
            '`^pro`',       // @codingStandardsIgnoreLine
            '`^dis`',       // @codingStandardsIgnoreLine
            '`^side`',      // @codingStandardsIgnoreLine
            '`ly$`',        // @codingStandardsIgnoreLine
            '`less$`',      // @codingStandardsIgnoreLine
            '`some$`',      // @codingStandardsIgnoreLine
            '`ful$`',       // @codingStandardsIgnoreLine
            '`ers?$`',      // @codingStandardsIgnoreLine
            '`ness$`',      // @codingStandardsIgnoreLine
            '`cians?$`',    // @codingStandardsIgnoreLine
            '`ments?$`',    // @codingStandardsIgnoreLine
            '`ettes?$`',    // @codingStandardsIgnoreLine
            '`villes?$`',   // @codingStandardsIgnoreLine
            '`ships?$`',    // @codingStandardsIgnoreLine
            '`sides?$`',    // @codingStandardsIgnoreLine
            '`ports?$`',    // @codingStandardsIgnoreLine
            '`shires?$`',   // @codingStandardsIgnoreLine
            '`tion(ed)?$`', // @codingStandardsIgnoreLine
        ];

    // Double syllable prefixes and suffixes.
    /** @var array */
    static public $arrdoubleaffix =
        [
            '`^above`',     // @codingStandardsIgnoreLine
            '`^ant[ie]`',   // @codingStandardsIgnoreLine
            '`^counter`',   // @codingStandardsIgnoreLine
            '`^hyper`',     // @codingStandardsIgnoreLine
            '`^afore`',     // @codingStandardsIgnoreLine
            '`^agri`',      // @codingStandardsIgnoreLine
            '`^in[ft]ra`',  // @codingStandardsIgnoreLine
            '`^inter`',     // @codingStandardsIgnoreLine
            '`^over`',      // @codingStandardsIgnoreLine
            '`^semi`',      // @codingStandardsIgnoreLine
            '`^ultra`',     // @codingStandardsIgnoreLine
            '`^under`',     // @codingStandardsIgnoreLine
            '`^extra`',     // @codingStandardsIgnoreLine
            '`^dia`',       // @codingStandardsIgnoreLine
            '`^micro`',     // @codingStandardsIgnoreLine
            '`^mega`',      // @codingStandardsIgnoreLine
            '`^kilo`',      // @codingStandardsIgnoreLine
            '`^pico`',      // @codingStandardsIgnoreLine
            '`^nano`',      // @codingStandardsIgnoreLine
            '`^macro`',     // @codingStandardsIgnoreLine
            '`berry$`',     // @codingStandardsIgnoreLine
            '`woman$`',     // @codingStandardsIgnoreLine
            '`women$`',     // @codingStandardsIgnoreLine
        ];

    // Triple syllable prefixes and suffixes.
    /** @var array */
    static public $arrtripleaffix =
        [
            '`ology$`',    // @codingStandardsIgnoreLine
            '`ologist$`',  // @codingStandardsIgnoreLine
            '`onomy$`',    // @codingStandardsIgnoreLine
            '`onomist$`',   // @codingStandardsIgnoreLine
        ];

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

        // Should be no non-alpha characters and lower case.
        $strword = preg_replace('`[^A-Za-z]`', '', $strword);  // @codingStandardsIgnoreLine
        $strword = strtolower($strword);

        // Check for problem words.
        if (isset(self::$arrproblemwords[$strword])) {
            return self::$arrproblemwords[$strword];
        }
        // Try singular.
        $singularword = pluralise::get_singular($strword);
        if ($singularword != $strword) {
            if (isset(self::$arrproblemwords[$singularword])) {
                return self::$arrproblemwords[$singularword];
            }
        }

        // Remove prefixes and suffixes and count how many were taken.
        $strword = preg_replace(self::$arraffix, '', $strword, -1, $intaffixcount);
        $strword = preg_replace(self::$arrdoubleaffix, '', $strword, -1, $intdoubleaffixcount);
        $strword = preg_replace(self::$arrtripleaffix, '', $strword, -1, $inttripleaffixcount);

        // Removed non-word characters from word.
        $arrwordparts = preg_split('`[^aeiouy]+`', $strword); // @codingStandardsIgnoreLine
        $intwordpartcount = 0;
        foreach ($arrwordparts as $strwordpart) {
            if ($strwordpart <> '') {
                $intwordpartcount++;
            }
        }

        // Some syllables do not follow normal rules - check for them.
        // Thanks to Joe Kovar for correcting a bug in the following lines.
        $intsyllablecount = $intwordpartcount + $intaffixcount + (2 * $intdoubleaffixcount) + (3 * $inttripleaffixcount);
        foreach (self::$arrsubsyllables as $strsyllable) {
            $intsyllablecounttemp = $intsyllablecount;
            $intsyllablecount -= preg_match('`'.$strsyllable.'`', $strword); // @codingStandardsIgnoreLine
        }
        foreach (self::$arraddsyllables as $strsyllable) {
            $intsyllablecounttemp = $intsyllablecount;
            $intsyllablecount += preg_match('`' . $strsyllable . '`', $strword); // @codingStandardsIgnoreLine
        }
        $intsyllablecount = ($intsyllablecount == 0) ? 1 : $intsyllablecount;
        return $intsyllablecount;
    }
}
