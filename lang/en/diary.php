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
 * English strings for diary plugin.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['eventdiarycreated'] = 'Diary created';
$string['eventdiaryviewed'] = 'Diary viewed';
$string['evententriesviewed'] = 'Diary entries viewed';
$string['eventdiarydeleted'] = 'Diary deleted';
$string['eventdownloadentriess'] = 'Download entries';
$string['evententryupdated'] = 'Diary entry updated';
$string['evententrycreated'] = 'Diary entry created';
$string['eventfeedbackupdated'] = 'Diary feedback updated';
$string['eventinvalidentryattempt'] = 'Diary invalid entry attempt';

$string['accessdenied'] = 'Access denied';
$string['alwaysopen'] = 'Always open';
$string['alias'] = 'Keyword';
$string['aliases'] = 'Keyword(s)';
$string['aliases_help'] = 'Each diary entry can have an associated list of keywords (or aliases).

Enter each keyword on a new line (not separated by commas).';
$string['and'] = ' and ';
$string['attachment'] = 'Attachment';
$string['attachment_help'] = 'You can optionally attach one or more files to a diary entry.';
$string['blankentry'] = 'Blank entry';
$string['calendarend'] = '{$a} closes';
$string['calendarstart'] = '{$a} opens';
$string['configdateformat'] = 'This defines how dates are shown in diary reports. The default value, "M d, Y G:i" is Month, day, year and 24 hour format time. Refer to Date in the PHP manual for more examples and predefined date constants.';
$string['created'] = 'Created {$a->one} days and {$a->two} hours ago.';
$string['csvexport'] = 'Export to .csv';
$string['currententry'] = 'Current diary entries:';
$string['daysavailable'] = 'Days available';
$string['daysavailable_help'] = 'If using Weekly format, you can set how many days the diary is open for use.';
$string['deadline'] = 'Days Open';
$string['dateformat'] = 'Default date format';
$string['details'] = 'Details: ';
$string['diaryclosetime'] = 'Close time';
$string['diaryclosetime_help'] = 'If enabled, you can set a date for the diary to be closed and no longer open for use.';
$string['diaryentrydate'] = 'Set date for this entry';
$string['diaryopentime'] = 'Open time';
$string['diaryopentime_help'] = 'If enabled, you can set a date for the diary to be opened for use.';
$string['edittopoflist'] = 'Edit top of the list';
$string['editall'] = 'Edit all entries';
$string['editall_help'] = 'When enabled, users can edit any entry.';
$string['editdates'] = 'Edit entry dates';
$string['editdates_help'] = 'When enabled, users can edit the date of any entry.';
$string['editingended'] = 'Editing period has ended';
$string['editingends'] = 'Editing period ends';
$string['editthisentry'] = 'Edit this entry';
$string['entries'] = 'Entries';
$string['entry'] = 'Entry';
$string['entrybgc_title'] = 'Diary entry/feedback background color';
$string['entrybgc_descr'] = 'This sets the background color of a diary entry/feedback.';
$string['entrybgc_colour'] = '#93FC84';
$string['entrycomment'] = 'Entry comment';
$string['entrytextbgc_title'] = 'Diary text background color';
$string['entrytextbgc_descr'] = 'This sets the background color of the text in a diary entry.';
$string['entrytextbgc_colour'] = '#EEFC84';
$string['exportfilename'] = 'entries.csv';
$string['exportfilenamep1'] = 'All_Site';
$string['exportfilenamep2'] = '_Diary_Entries_Exported_On_';
$string['feedbackupdated'] = 'Feedback updated for {$a} entries';
$string['firstentry'] = 'First diary entries:';
$string['gradeingradebook'] = 'Current rating in gradebook';
$string['diary:addentries'] = 'Add diary entries';
$string['diary:addinstance'] = 'Add diary instances';
$string['diary:manageentries'] = 'Manage diary entries';
$string['diary:rate'] = 'Rate diary entries';
$string['diarymail'] = 'Greetings {$a->user},
{$a->teacher} has posted some feedback on your diary entry for \'{$a->diary}\'.

You can see it appended to your diary entry:

    {$a->url}';
$string['diarymailhtml'] = 'Greetings {$a->user},
{$a->teacher} has posted some feedback on your
diary entry for \'<i>{$a->diary}</i>\'.<br /><br />
You can see it appended to your <a href="{$a->url}">diary entry</a>.';
$string['diaryname'] = 'Diary name';
$string['diarydescription'] = 'Diary description';
$string['format'] = 'Format';
$string['generalerror'] = 'There has been an error.';
$string['generalerrorupdate'] = 'Could not update your diary.';
$string['generalerrorinsert'] = 'Could not insert a new diary entry.';
$string['highestgradeentry'] = 'Highest rated entries:';
$string['incorrectcourseid'] = 'Course ID is incorrect';
$string['incorrectmodule'] = 'Course Module ID was incorrect';
$string['invalidaccess'] = 'Invalid access';
$string['invalidaccessexp'] = 'You do not have permission to view the page you attempted to access! The attempt was logged!';
$string['invalidtimechange'] = 'An invalid attempt to change this entry\'s, Time created, has been detected. ';
$string['invalidtimechangeoriginal'] = 'The original time was: {$a->one}. ';
$string['invalidtimechangenewtime'] = 'The changed time was: {$a->one}. ';
$string['invalidtimeresettime'] = 'The time was reset to the original time of: {$a->one}.';

$string['lastnameasc'] = 'Last name ascending:';
$string['lastnamedesc'] = 'Last name descending:';
$string['latestmodifiedentry'] = 'Most recently modified entries:';
$string['lowestgradeentry'] = 'Lowest rated entries:';
$string['mailed'] = 'Mailed';
$string['mailsubject'] = 'Diary feedback';
$string['modulename'] = 'Diary';
$string['modulename_help'] = 'The diary activity enables teachers to obtain students feedback
 over a period of time.';
$string['modulenameplural'] = 'Diarys';
$string['needsgrading'] = ' This entry has not been given feedback or rated yet.';
$string['needsregrade'] = 'This entry has changed since feedback or a rating was given.';
$string['newdiaryentries'] = 'New diary entries';
$string['nextentry'] = 'Next entry';
$string['nodeadline'] = 'Always open';
$string['noentriesmanagers'] = 'There are no teachers';
$string['noentry'] = 'No entry';
$string['noratinggiven'] = 'No rating given';
$string['notopenuntil'] = 'This diary won\'t be open until';
$string['numwordsraw'] = '{$a->one} raw text words using  {$a->two} characters, including {$a->three} spaces. ';
$string['numwordscln'] = '{$a->one} clean text words using {$a->two} characters, NOT including {$a->three} spaces. ';
$string['numwordsstd'] = '{$a->one} standardized words using {$a->two} characters, including {$a->three} spaces. ';

$string['numwordsnew'] = 'New calculation: {$a->one} raw text words using {$a->two} characters, in {$a->three} sentences, in {$a->four} paragraphs. ';


$string['notstarted'] = 'You have not started this diary yet';
$string['outof'] = ' out of {$a} entries.';
$string['overallrating'] = 'Overall rating';
$string['pagesize'] = 'Entries per page';
$string['pluginadministration'] = 'Diary module administration';
$string['pluginname'] = 'Diary';
$string['previousentry'] = 'Previous entry';
$string['rate'] = 'Rate';
$string['rating'] = 'Rating for this entry';
$string['reload'] = 'Reload and show from current to oldest diary entry';
$string['removeentries'] = 'Remove all entries';
$string['removemessages'] = 'Remove all Diary entries';
$string['reportsingle'] = 'Get all Diary entries for this user.';
$string['reportsingleallentries'] = 'All Diary entries for this user.';
$string['returnto'] = 'Return to {$a}';
$string['returntoreport'] = 'Return to report page for - {$a}';
$string['saveallfeedback'] = 'Save all my feedback';
$string['savesettings'] = 'Save settings';
$string['search'] = 'Search';
$string['search:entry'] = 'Diary - entries';
$string['search:entrycomment'] = 'Diary - entry comment';
$string['search:activity'] = 'Diary - activity information';
$string['selectentry'] = 'Select entry for marking';
$string['showrecentactivity'] = 'Show recent activity';
$string['showoverview'] = 'Show diarys overview on my moodle';
$string['sortorder'] = 'Sort order is: ';
$string['sortcurrententry'] = 'From current diary entry to the first entry.';
$string['sortfirstentry'] = 'From first diary entry to the latest entry.';
$string['sortlowestentry'] = 'From lowest rated diary entry to the highest entry.';
$string['sorthighestentry'] = 'From highest rated diary entry to the lowest rated entry.';
$string['sortlastentry'] = 'From latest modified diary entry to the oldest modified entry.';
$string['sortoptions'] = ' Sort options: ';
$string['startnewentry'] = 'Start new entry';
$string['startoredit'] = 'Start new or edit today\'s entry';
$string['teacher'] = 'Teacher';
$string['text'] = 'Text';
$string['timecreated'] = 'Time created';
$string['timemarked'] = 'Time marked';
$string['timemodified'] = 'Time modified';
$string['toolbar'] = 'Toolbar:';
$string['userid'] = 'User id';
$string['usertoolbar'] = 'User toolbar:';
$string['viewalldiaries'] = 'View all course diaries';
$string['viewallentries'] = 'View {$a} diary entries';
$string['viewentries'] = 'View entries';
