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
defined('MOODLE_INTERNAL') || die(); // @codingStandardsIgnoreLine

/**
 * Pluralise class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pluralise {
    /**
     * Singularising and Pluralising functions from following URL, released
     * under an MIT license and used with thanks:
     * http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
     */
    /** @var array null */
    private static $plural = [
        '/(quiz)$/i' => "$1zes",
        '/^(ox)$/i' => "$1en",
        '/([m|l])ouse$/i' => "$1ice",
        '/(matrix|vertex|index)$/i' => "$1ices",
        '/(x|ch|ss|sh)$/i' => "$1es",
        '/([^aeiouy]|qu)y$/i' => "$1ies",
        '/(hive)$/i' => "$1s",
        '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
        '/(shea|lea|loa|thie)f$/i' => "$1ves",
        '/sis$/i' => "ses",
        '/([ti])um$/i' => "$1a",
        '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
        '/(bu)s$/i' => "$1ses",
        '/(alias)$/i' => "$1es",
        '/(octop)us$/i' => "$1i",
        '/(ax|test)is$/i' => "$1es",
        '/(us)$/i' => "$1es",
        '/s$/i' => "s",
    ];
    /** @var array null */
    private static $singular = [
        '/(quiz)zes$/i' => "$1",
        '/(matr)ices$/i' => "$1ix",
        '/(vert|ind)ices$/i' => "$1ex",
        '/^(ox)en$/i' => "$1",
        '/(alias)es$/i' => "$1",
        '/(octop|vir)i$/i' => "$1us",
        '/(cris|ax|test)es$/i' => "$1is",
        '/(shoe)s$/i' => "$1",
        '/(o)es$/i' => "$1",
        '/(bus)es$/i' => "$1",
        '/([m|l])ice$/i' => "$1ouse",
        '/(x|ch|ss|sh)es$/i' => "$1",
        '/(m)ovies$/i' => "$1ovie",
        '/(s)eries$/i' => "$1eries",
        '/([^aeiouy]|qu)ies$/i' => "$1y",
        '/([lr])ves$/i' => "$1f",
        '/(tive)s$/i' => "$1",
        '/(hive)s$/i' => "$1",
        '/(li|wi|kni)ves$/i' => "$1fe",
        '/(shea|loa|lea|thie)ves$/i' => "$1f",
        '/(^analy)ses$/i' => "$1sis",
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
        '/([ti])a$/i' => "$1um",
        '/(n)ews$/i' => "$1ews",
        '/(h|bl)ouses$/i' => "$1ouse",
        '/(corpse)s$/i' => "$1",
        '/(us)es$/i' => "$1",
        '/s$/i' => "",
    ];
    /** @var array null */
    private static $irregular = [
        'child' => 'children',
        'foot' => 'feet',
        'goose' => 'geese',
        'man' => 'men',
        'mouse' => 'mice',
        'move' => 'moves',
        'person' => 'people',
        'sex' => 'sexes',
        'tooth' => 'teeth',
    ];

    // Some words are only uncountable sometimes. For example, "blues" can be
    // uncountable when referring to music, but countable when referring to
    // multiple colours.
    /** @var array null */
    private static $uncountable = [
        'beef',
        'bison',
        'buffalo',
        'carbon',
        'chemistry',
        'copper',
        'geometry',
        'gold',
        'cs',
        'css',
        'deer',
        'equipment',
        'fish',
        'furniture',
        'information',
        'mathematics',
        'money',
        'moose',
        'nitrogen',
        'oxygen',
        'rice',
        'series',
        'sheep',
        'species',
        'surgery',
        'traffic',
        'water',
    ];

    /**
     * Get the plural of the word passed in.
     * @param  string $string Word to pluralise.
     * @return string Pluralised word.
     */
    public static function get_plural($string) {
        // Save some time in the case that singular and plural are the same.
        if (in_array(strtolower($string), self::$uncountable)) {
            return $string;
        }

        // Check to see if already plural and irregular.
        foreach (self::$irregular as $pattern => $result) {
            $pattern = '/' . $result . '$/i';
            if (preg_match($pattern, $string)) {
                return $string;
            }
        }

        // Check for irregular singular forms.
        foreach (self::$irregular as $pattern => $result) {
            $pattern = '/' . $pattern . '$/i';
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        // Check for matches using regular expressions.
        foreach (self::$plural as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        // No pattern match. Add an "s".
        return $string . 's';
    }

    /**
     * Get the singular of the word passed in.
     * @param  string $string Word to singularise
     * @return string Singularised word
     */
    public static function get_singular($string) {
        // Save some time in the case that singular and plural are the same.
        if (in_array(strtolower($string), self::$uncountable)) {
            return $string;
        }

        // Check to see if already singular and irregular.
        foreach (self::$irregular as $pattern => $result) {
            $pattern = '/' . $pattern . '$/i';
            if (preg_match($pattern, $string)) {
                return $string;
            }
        }

        // Check for irregular plural forms.
        foreach (self::$irregular as $result => $pattern) {
            $pattern = '/' . $pattern . '$/i';
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        // Check for matches using regular expressions.
        foreach (self::$singular as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }
}
