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

$string['accessdenied'] = 'Access denied';
$string['additionallinks'] = 'Additional links for this activity and course:';
$string['addtofeedback'] = 'Add to feedback';
$string['alias'] = 'Keyword';
$string['aliases_help'] = 'Each diary entry can have an associated list of keywords (or aliases).

Enter each keyword on a new line (not separated by commas).';
$string['aliases'] = 'Keyword(s)';
$string['alwaysopen'] = 'Always open';
$string['alwaysshowdescription_help'] = 'If disabled, the diary description above will only become visible to students on the "Open time" date.';
$string['alwaysshowdescription'] = 'Always show description';
$string['and'] = ' and ';
$string['attachment_help'] = 'You can optionally attach one or more files to a diary entry.';
$string['attachment'] = 'Attachment';
$string['autorating_descr'] = 'If enabled, the rating for an entry will be automatically calculated based on the Min/Max counts settings.';
$string['autorating_help'] = 'This setting along with Min/Max counts define the defaults for autorating in all new diarys.';
$string['autorating_title'] = 'Auto-rating enable';
$string['autorating'] = 'Auto-rating';
$string['autoratingbelowmaxitemdetails'] = 'Auto-rating requires {$a->one} or more {$a->two} with a possible {$a->three}% penalty for each missing one.<br>You have {$a->four}. You need to come up with {$a->five}. Possible penalty is {$a->six} points.';
$string['autoratingitempenaltymath'] = 'The automatic item rating penalty math is (max({$a->one} - {$a->two}, 0)) * {$a->three} =  {$a->four}.<br> Note: max prevents negative numbers caused by having more than is required.';
$string['autoratingitempercentset'] = 'Auto-rating percent settings: {$a}%';
$string['autoratingovermaxitemdetails'] = 'Auto-rating limit is a maximum of {$a->one} {$a->two} with a possible {$a->three}% penalty for each extra one.<br>You have {$a->four}, which is {$a->five} too many. Possible penalty is {$a->six} points.';
$string['availabilityhdr'] = 'Availability';
$string['avgsylperword'] = 'Average syllables per word {$a}';
$string['avgwordlenchar'] = 'Average word length {$a} characters';
$string['avgwordpara'] = 'Average words per paragraph {$a}';
$string['blankentry'] = 'Blank entry';
$string['calendarend'] = '{$a} closes';
$string['calendarstart'] = '{$a} opens';
$string['cancel'] = 'Cancel transfer';
$string['chars'] = 'Characters:';
$string['charspersentence'] = 'Characters per sentence';
$string['clearfeedback'] = 'Clear feedback';
$string['commonerrorpercentset'] = 'Common error percent setting {$a}%';
$string['commonerrors_help'] = 'The common errors are defined in the "Glossary of errors" associated with this question.';
$string['commonerrors'] = 'Common Errors';
$string['configdateformat'] = 'This defines how dates are shown in diary reports. The default value, "M d, Y G:i" is Month, day, year and 24 hour format time. Refer to Date in the PHP manual for more examples and predefined date constants.';
$string['createnewprompt'] = 'Create new prompt';
$string['created'] = 'Created {$a->one} days and {$a->two} hours ago.';
$string['crontask'] = 'Background processing for Diary module';
$string['csvexport'] = 'Export to .csv';
$string['currententry'] = 'Current diary entries:';
$string['currpotrating'] = 'Your current potential rating is: {$a->one} points, or {$a->two}%.';
$string['datechanged'] = 'Date changed';
$string['dateformat'] = 'Default date format';
$string['datestart'] = 'Set date to start using prompt ID {$a}:';
$string['datestop'] = 'Set date to stop using prompt ID {$a}:';
$string['daysavailable_help'] = 'If using Weekly format, you can set how many days the diary is open for use.';
$string['daysavailable'] = 'Days available';
$string['deadline'] = 'Days Open';
$string['delete'] = 'Delete';
$string['deleteallratings'] = 'Delete all ratings';
$string['deletenotenrolled'] = 'Delete entries by users not enrolled';
$string['deleteexconfirm'] = 'Confirm you are about to delete writing prompt ID ';
$string['details'] = 'Details: ';
$string['detectcommonerror'] = 'Detected at least {$a->one}, {$a->two}. They are: {$a->three}
<br>If allowed, you should fix and re-submit.';
$string['diary:addentries'] = 'Add diary entries';
$string['diary:addinstance'] = 'Add diary instances';
$string['diary:manageentries'] = 'Manage diary entries';
$string['diary:rate'] = 'Rate diary entries';
$string['diaryclosetime_help'] = 'If enabled, you can set a date for the diary to be closed and no longer open for use.';
$string['diaryclosetime'] = 'Close time';
$string['diarydescription'] = 'Diary description';
$string['diaryentrydate'] = 'Set date for this entry';
$string['diaryid'] = 'diaryid to transfer to';
$string['diarymail'] = 'Greetings {$a->user},
{$a->teacher} has posted some feedback on your diary entry for \'{$a->diary}\'.

You can see it appended to your diary entry:

    {$a->url}';
$string['diarymailhtml'] = 'Greetings {$a->user},<br>
{$a->teacher} has posted some feedback on your
diary entry for \'<i>{$a->diary}</i>\'.<br /><br />
You can see it appended to your <a href="{$a->url}">diary entry</a>.';
$string['diarymailhtmluser'] = 'has posted a diary entry for \'<i>{$a->diary}</i>\'<br /><br />
You may view the <a href="{$a->url}">diary entry here</a>.<br /><br />Note: You may need to provide feedback or update the status of the entry in order for the activity to be set to complete.';
$string['diarymailuser'] = 'has posted a diary entry for \'{$a->diary}\'

You may view the entry here:

    {$a->url}

Note: You may need to provide feedback or update the status of the entry in order for the activity to be set to complete.';
$string['diaryname'] = 'Diary name';
$string['diaryopentime_help'] = 'If enabled, you can set a date for the diary to be opened for use.';
$string['diaryopentime'] = 'Open time';
$string['diarytitle'] = 'Title';
$string['diarytitle_help'] = 'You can add an optional title/description.';
$string['editall_help'] = 'When enabled, users can edit any entry.';
$string['editall'] = 'Edit all entries';
$string['editdates_help'] = 'When enabled, users can edit the date of any entry.';
$string['editdates'] = 'Edit entry dates';
$string['editingended'] = 'Editing period has ended';
$string['editingends'] = 'Editing period ends';
$string['editthisentry'] = 'Edit this entry';
$string['edittopoflist'] = 'Edit top of the list';
$string['eeditlabel'] = 'Edit';
$string['emailpreference'] = 'Toggle emails';
$string['emailnow'] = 'Email now';
$string['emaillater'] = 'Email later';
$string['enableautorating_help'] = 'Enable, or disable, automatic ratings';
$string['enableautorating'] = 'Enable automatic rating';
$string['enablestats_descr'] = 'If enabled, the statistics for each entry will be shown.';
$string['enablestats_help'] = 'Enable, or disable, viewing statistics for each entry.';
$string['enablestats_title'] = 'Enable statistics';
$string['enablestats'] = 'Enable statistics';

$string['enabletitles_descr'] = 'If enabled, the titles for each entry will be required and shown.';
$string['enabletitles_help'] = 'Enable, or disable, requiring titles for each entry.';
$string['enabletitles_title'] = 'Enable titles';
$string['enabletitles'] = 'Enable titles';

$string['entries'] = 'Entries';
$string['entry'] = 'Entry';
$string['entrybgc_colour'] = '#93FC84';
$string['entrybgc_descr'] = 'This sets the background color of a diary entry/feedback.';
$string['entrybgc_help'] = 'This sets the overall background color of each diary entry and feedback.';
$string['entrybgc_title'] = 'Diary entry/feedback background color';
$string['entrybgc'] = 'Diary entry/feedback background color';
$string['entrycomment'] = 'Entry comment';
$string['entrysuccess'] = 'Your entry has been saved! It may need to be reviewed or rated before activity is set to complete.';
$string['entrytextbgc_colour'] = '#EEFC84';
$string['entrytextbgc_descr'] = 'This sets the background color of the text in a diary entry.';
$string['entrytextbgc_help'] = 'This sets the background color of the text in a diary entry.';
$string['entrytextbgc_title'] = 'Diary text background color';
$string['entrytextbgc'] = 'Diary text background color';
$string['errorbehavior_help'] = 'These settings refine the matching behavior for entries in the Glossary of common errors.';
$string['errorbehavior'] = 'Error matching behavior';
$string['errorcmid_help'] = 'Choose the Glossary that contains a list of common errors. Each time one of the errors is found in the essay response, the specified penalty will be deducted from the student\'s rating for this entry.';
$string['errorcmid'] = 'Glossary of errors';
$string['errorfullmatch'] = 'Error matching behavior';
$string['errorfullmatch_help'] = 'This setting refines the matching behavior for entries in the Glossary of Common Errors.';
$string['errorpercent_help'] = 'Select the percentage of total rating that should be deducted for each error that is found in the response.';
$string['errorpercent'] = 'Penalty per error';
$string['errp'] = ' Err %: ';
$string['eventdiarycreated'] = 'Diary created';
$string['eventdiarydeleted'] = 'Diary deleted';
$string['eventdiaryviewed'] = 'Diary viewed';
$string['eventdownloadentriess'] = 'Download entries';
$string['evententriesviewed'] = 'Diary entries viewed';
$string['evententrycreated'] = 'Diary entry created';
$string['evententryupdated'] = 'Diary entry updated';
$string['eventfeedbackupdated'] = 'Diary feedback updated';
$string['eventinvalidentryattempt'] = 'Diary invalid entry attempt';
$string['eventpromptcreated'] = 'Prompt created';
$string['eventpromptedited'] = 'Prompt edited';
$string['eventpromptinuse'] = 'Prompt delete prevented';
$string['eventpromptremoved'] = 'Prompt removed';
$string['eventpromptsviewed'] = 'Prompts viewed';
$string['eventxfrentries'] = 'Journal to Diary entry transfer';
$string['exportfilename'] = 'entries.csv';
$string['exportfilenamep1'] = 'All_Site';
$string['exportfilenamep2'] = '_Diary_Entries_Exported_On_';
$string['feedbackupdated'] = 'Feedback updated for {$a} entries';
$string['files'] = 'Files';
$string['firstentry'] = 'First diary entries:';
$string['fkgrade_help'] = 'Flesch Kincaid grade level indicates the number of years of education generally required to understand this text. Try to aim for a grade level below 10.';
$string['fkgrade'] = 'FK Grade';
$string['fogindex_help'] = 'The Gunning fog index is a measure of readability. It is calculated using the following formula.

 ((words per sentence) + (long words per sentence)) x 0.4

Try to aim for a grade level below 10. For more information see: <https://en.wikipedia.org/wiki/Gunning_fog_index>';
$string['fogindex'] = 'Fog index';
$string['for'] = ' for site: ';
$string['format'] = 'Format';
$string['freadingease_help'] = 'Flesch reading ease: high scores indicate your text is easier to read while lower scores indicate your text is more difficult to read. Try to aim for a reading ease over 60.';
$string['freadingease'] = 'Flesch reading ease';
$string['generalerror'] = 'There has been an error.';
$string['generalerrorinsert'] = 'Could not insert a new diary entry.';
$string['generalerrorupdate'] = 'Could not update your diary.';
$string['gradeingradebook'] = 'Current rating in gradebook';
$string['highestgradeentry'] = 'Highest rated entries:';
$string['idlable'] = ' (ID: {$a})';
$string['incorrectcourseid'] = 'Course ID is incorrect';
$string['incorrectmodule'] = 'Course Module ID was incorrect';
$string['invalidaccess'] = 'Invalid access';
$string['invalidaccessexp'] = 'You do not have permission to view the page you attempted to access! The attempt was logged!';
$string['invalidtimechange'] = 'An invalid attempt to change this entry\'s, Time created, has been detected. ';
$string['invalidtimechangenewtime'] = 'The changed time was: {$a->one}. ';
$string['invalidtimechangeoriginal'] = 'The original time was: {$a->one}. ';
$string['invalidtimeresettime'] = 'The time was reset to the original time of: {$a->one}.';
$string['journalid'] = 'journalid to transfer from';
$string['journalmissing'] = 'Currently, there are not any Journal activities in this course.';
$string['journaltodiaryxfrdid'] = '<br>This is a list of each Diary activity in this course.<br><b>    ID</b> | Course | Diary name<br>';
$string['journaltodiaryxfrjid'] = 'This is a list of each Journal activity in this course.<br><b>    ID</b> | Course | Journal name<br>';
$string['journaltodiaryxfrp1'] = 'This is an admin user only function to transfer Journal entries to Diary entries. Entries from multiple Journal\'s can be transferred to a single Diary or to multiple separate Diary\'s. This is a new capability and is still under development.<br><br>';
$string['journaltodiaryxfrp2'] = 'If you use the, <b>Transfer and send email</b>, checkbox, any journal entry transferred to a diary activity will mark the new entry as needing an email sent to the user so they know the entry has been transferred to a diary activity.<br><br>';
$string['journaltodiaryxfrp3'] = 'If you use the, <b>Transfer without email</b>, button an email will NOT be sent to each user even though the process automatically adds feedback in the new Diary entry, even if the original Journal entry had not feedback included.<br><br>';
$string['journaltodiaryxfrp4'] = 'The name of this course you are working in is: <b> {$a->one}</b>, with a Course ID of: <b> {$a->two}</b><br><br>';
$string['journaltodiaryxfrp5'] = 'If you elect to include feedback regarding the transfer and the journal entry does not already have any feedback, you will automatically be added as the teacher for the entry to prevent an error.<br><br>';
$string['journaltodiaryxfrtitle'] = 'Journal to Diary xfr';
$string['lastnameasc'] = 'Last name ascending:';
$string['lastnamedesc'] = 'Last name descending:';
$string['latestmodifiedentry'] = 'Most recently modified entries:';
$string['lexicaldensity_help'] = 'The lexical density is a percentage calculated using the following formula.

 100 x (number of unique words) / (total number of words)

Thus, an essay in which many words are repeated has a low lexical density, while a essay with many unique words has a high lexical density.';
$string['lexicaldensity'] = 'Lexical density';
$string['longwords_help'] = 'Long words are words that have three or more syllables. Note that the algorithm for determining the number of syllables yields only approximate results.';
$string['longwords'] = 'Unique long words';
$string['longwordspersentence'] = 'Long words per sentence';
$string['lowestgradeentry'] = 'Lowest rated entries:';
$string['mailed'] = 'Mailed';
$string['mailsubject'] = 'Diary feedback';
$string['max'] = ' max';
$string['maxc'] = ' Max: ';
$string['maxchar_help'] = 'If a number greater than zero is entered, the user must use less characters than the maximum number listed, or receive a penalty for each of the extra characters.';
$string['maxchar'] = 'Character count maximum';
$string['maxcharacterlimit_desc'] = 'Note: This entry can use a <strong>maximum of {$a} characters.</strong>';
$string['maxcharacterlimit_help'] = 'If a number is entered, the user must use less characters than the maximum number listed.';
$string['maxcharacterlimit'] = 'Character count maximum';
$string['maxparagraph_help'] = 'If a number greater than zero is entered, the user must use less paragraphs than the maximum number listed, or receive a penalty for each of the extra paragraphs.';
$string['maxparagraph'] = 'Paragraph count maximum';
$string['maxparagraphlimit_desc'] = 'Note: This entry can use a <strong>maximum of {$a} paragraphs.</strong>';
$string['maxparagraphlimit_help'] = 'If a number is entered, the user must use less paragraphs than the maximum number listed.';
$string['maxparagraphlimit'] = 'Paragraph count maximum';
$string['maxpossrating'] = 'The maximum possible rating for this entry is {$a} points.';
$string['maxsentence_help'] = 'If a number greater than zero is entered, the user must use less sentences than the maximum number listed, or receive a penalty for each of the extra sentences.';
$string['maxsentence'] = 'Sentence count maximum';
$string['maxsentencelimit_desc'] = 'Note: This entry can use a <strong>maximum of {$a} sentences.</strong>';
$string['maxsentencelimit_help'] = 'If a number is entered, the user must use less sentences than the maximum number listed.';
$string['maxsentencelimit'] = 'Sentence count maximum';
$string['maxword_help'] = 'If a number greater than zero is entered, the user must use less words than the maximum number listed, or receive a penalty for each of the extra words.';
$string['maxword'] = 'Word count maximum';
$string['maxwordlimit_desc'] = 'Note: This entry can use a <strong>maximum of {$a} words.</strong>';
$string['maxwordlimit_help'] = 'If a number is entered, the user must use less words than the maximum number listed.';
$string['maxwordlimit'] = 'Word count maximum';
$string['mediumwords_help'] = 'Medium words are words that have two syllables. Note that the algorithm for determining the number of syllables yields only approximate results.';
$string['mediumwords'] = 'Unique medium words';
$string['min'] = ' min';
$string['minc'] = ' Min: ';
$string['minchar_help'] = 'If a number greater than zero is entered, the user must use more characters than the minimum number listed, or receive a penalty for each of the missing characters.';
$string['minchar'] = 'Character count minimum';
$string['mincharacterlimit_desc'] = 'Note: This entry must use a <strong>minimum of {$a} characters.</strong>';
$string['mincharacterlimit_help'] = 'If a number is entered, the user must use more characters than the minimum number listed.';
$string['mincharacterlimit'] = 'Character count minimum';
$string['minmaxcharpercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max character count error.';
$string['minmaxcharpercent'] = 'Character penalty per Min/Max count error';
$string['minmaxhdr_help'] = 'These settings define the defaults for minimum and maximum character and word counts in all new diarys.';
$string['minmaxhdr'] = 'Min/Max counts';
$string['minmaxparagraphpercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max paragraph count error.';
$string['minmaxparagraphpercent'] = 'Paragraph penalty per Min/Max count error';
$string['minmaxparapercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max paragraph count error.';
$string['minmaxparapercent'] = 'Paragraph penalty per Min/Max count error';
$string['minmaxpercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max count error.';
$string['minmaxpercent'] = 'Penalty per Min/Max count error';
$string['minmaxsentencepercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max sentence count error.';
$string['minmaxsentencepercent'] = 'Sentence penalty per Min/Max count error';
$string['minmaxsentpercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max sentence count error.';
$string['minmaxsentpercent'] = 'Sentence penalty per Min/Max count error';
$string['minmaxwordpercent_help'] = 'Select the percentage of total rating that should be deducted for each Min/Max word count error.';
$string['minmaxwordpercent'] = 'Word penalty per Min/Max count error';
$string['minparagraph_help'] = 'If a number greater than zero is entered, the user must use more paragraphs than the minimum number listed, or receive a penalty for each of the missing paragraphs.';
$string['minparagraph'] = 'Paragraph count minimum';

$string['minparagraphlimit_desc'] = 'Note: This entry must use a <strong>minimum of {$a} paragraphs.</strong>';
$string['minparagraphlimit_help'] = 'If a number is entered, the user must use more paragraphs than the minimum number listed.';
$string['minparagraphlimit'] = 'Paragraph count minimum';
$string['minsentence_help'] = 'If a number greater than zero is entered, the user must use more sentences than the minimum number listed, or receive a penalty for each of the missing sentences.';
$string['minsentence'] = 'Sentence count minimum';
$string['minsentencelimit_desc'] = 'Note: This entry must use a <strong>minimum of {$a} sentences.</strong>';
$string['minsentencelimit_help'] = 'If a number is entered, the user must use more sentences than the minimum number listed.';
$string['minsentencelimit'] = 'Sentence count minimum';
$string['minword_help'] = 'If a number greater than zero is entered, the user must use more words than the minimum number listed, or receive a penalty for each of the missing words.';
$string['minword'] = 'Word count minimum';
$string['minwordlimit_desc'] = 'Note: This entry must use a <strong>minimum of {$a} words.</strong>';
$string['minwordlimit_help'] = 'If a number is entered, the user must use more words than the minimum number listed.';
$string['minwordlimit'] = 'Word count minimum';
$string['missing'] = 'Missing';
$string['modulename_help'] = 'The diary activity enables teachers to obtain students feedback
 over a period of time.';
$string['modulename'] = 'Diary';
$string['modulenameplural'] = 'Diaries';
$string['needsgrading'] = ' This entry has not been given feedback or rated yet.';
$string['needsregrade'] = 'This entry has changed since feedback or a rating was given.';
$string['newdiaryentries'] = 'New diary entries';
$string['nextentry'] = 'Next entry';
$string['nodeadline'] = 'Always open';
$string['noentriesmanagers'] = 'There are no teachers';
$string['noentry'] = 'No entry. ';
$string['noratinggiven'] = 'No rating given';
$string['notextdetected'] = '<b>No text detected!</b>';
$string['notopenuntil'] = 'This diary won\'t be open until';
$string['notstarted'] = 'You have not started this diary yet';
$string['numwordscln'] = '{$a->one} clean text words using {$a->two} characters, NOT including {$a->three} spaces. ';
$string['numwordsnew'] = 'New calculation: {$a->one} raw text words using {$a->two} characters, in {$a->three} sentences, in {$a->four} paragraphs. ';
$string['numwordsraw'] = '{$a->one} raw text words using  {$a->two} characters, including {$a->three} spaces. ';
$string['numwordsstd'] = '{$a->one} standardized words using {$a->two} characters, including {$a->three} spaces. ';
$string['outof'] = ' out of {$a} entries.';
$string['overallrating'] = 'Overall rating';
$string['pagesize'] = 'Entries per page';
$string['paragraphs'] = 'Paragraphs:';
$string['percentofentryrating'] = '{$a}% of the entry rating.';
$string['phrasecasesensitiveno'] = 'Match is case-insensitive.';
$string['phrasecasesensitiveyes'] = 'Match is case-sensitive.';
$string['phrasefullmatchno'] = 'Match full or partial words.';
$string['phrasefullmatchyes'] = 'Match full words only.';
$string['phraseignorebreaksno'] = 'Recognize line breaks.';
$string['phraseignorebreaksyes'] = 'Ignore line breaks.';
$string['pluginadministration'] = 'Diary module administration';
$string['pluginname'] = 'Diary';
$string['popoverhelp'] = 'click for info';
$string['potautoratingerrpen'] = 'Potential Autorating error penalty: {$a->one}% or {$a->two} points off.';
$string['potcommerrpen'] = 'Potential Common error penalty: {$a->one} * {$a->two} = {$a->three}% or {$a->four} points off.';
$string['phrasefullmatchno'] = 'Match full or partial words.';
$string['phrasefullmatchyes'] = 'Match full words only.';
$string['present'] = 'Present';
$string['previousentry'] = 'Previous entry';
$string['privacy:metadata:diary_entries:diary'] = 'The ID of the Diary activity in which the entry was posted.';
$string['privacy:metadata:diary_entries:entrycomment'] = 'Teacher feedback and possibly, auto rating feedback.';
$string['privacy:metadata:diary_entries:mailed'] = 'Has this user been mailed yet?';
$string['privacy:metadata:diary_entries:promptid'] = 'The ID of the automatic writing prompt used for auto-rating and feedback.';
$string['privacy:metadata:diary_entries:promptdatestart'] = 'The date the automatic writing prompt started being used.';
$string['privacy:metadata:diary_entries:promptdatestop'] = 'The date the automatic writing prompt stopped being used.';
$string['privacy:metadata:diary_entries:prompttext'] = 'The text of the writing prompt used for auto-rating and feedback.';
$string['privacy:metadata:diary_entries:rating'] = 'The numerical grade for this diary entry. Can be determined by scales/advancedgradingforms etc., but will always be converted back to a floating point number.';
$string['privacy:metadata:diary_entries:teacher'] = 'The user ID of the person rating the entry.';
$string['privacy:metadata:diary_entries:text'] = 'The content of this entry.';
$string['privacy:metadata:diary_entries:timecreated'] = 'Time the entry was created.';
$string['privacy:metadata:diary_entries:timemarked'] = 'Time the entry was rated.';
$string['privacy:metadata:diary_entries:timemodified'] = 'Time the entry was last modified.';
$string['privacy:metadata:diary_entries:userid'] = 'ID of the user.';
$string['privacy:metadata:diary_entries'] = 'A record of a diary entry.';
$string['prompt'] = 'Enter your writing prompt';
$string['promptbgc'] = 'Background color for this prompt';
$string['promptbgc_help'] = 'This sets the overall background color for this prompt.';
$string['promptid'] = 'Prompt id';
$string['promptinfo'] = 'There are {$a->past} past prompts, {$a->current} current prompt, and {$a->future} future prompts for this diary activity.<br>';
$string['promptmaxc'] = 'Char max';
$string['promptmaxp'] = 'Para max';
$string['promptmaxs'] = 'Sent max';
$string['promptmaxw'] = 'Word max';
$string['promptminc'] = 'Char min';
$string['promptminp'] = 'Para min';
$string['promptmins'] = 'Sent min';
$string['promptminw'] = 'Word min';
$string['promptminmaxcp'] = 'Char %';
$string['promptminmaxpp'] = 'Para %';
$string['promptminmaxsp'] = 'Sent %';
$string['promptminmaxwp'] = 'Word %';
$string['promptremovefailure'] = 'This prompt, ID {$a}, is in use and cannot be removed.';
$string['promptremovesuccess'] = 'You have successfully removed prompt, ID {$a}.';
$string['promptstart'] = 'Prompt start';
$string['promptstitle'] = 'Diary writing prompts';
$string['promptstop'] = 'Prompt stop';
$string['promptsviewtitle'] = 'View writing prompts';
$string['prompttext'] = 'Prompt text';
$string['promptzerocount'] = '<td>Currently, there are, {$a} prompts for this Diary activity. </td>';
$string['rate'] = 'Rate';
$string['rating'] = 'Rating for this entry';
$string['reload'] = 'Reload and show from current to oldest diary entry';
$string['removealldiarytags'] = 'Remove all Diary tags';
$string['removeentries'] = 'Remove all entries';
$string['removemessages'] = 'Remove all Diary entries';
$string['reportsingle'] = 'Get all Diary entries for this user.';
$string['reportsingleallentries'] = 'All Diary entries for this user.';
$string['returnto'] = 'Return to {$a}';
$string['returntoreport'] = 'Return to report page for - {$a}';
$string['saveallfeedback'] = 'Save all my feedback';
$string['savesettings'] = 'Save settings';
$string['search:entry'] = 'Diary - entries';
$string['search:entrycomment'] = 'Diary - entry comment';
$string['search:activity'] = 'Diary - activity information';
$string['search'] = 'Search';
$string['selectentry'] = 'Select entry for marking';
$string['sentences'] = 'Sentences:';
$string['sentencesperparagraph'] = 'Sentences per paragraph';
$string['shortwords_help'] = 'Short words are words that have only one syllable. Note that the algorithm for determining the number of syllables yields only approximate results.';
$string['shortwords'] = 'Unique short words';
$string['showlistno'] = 'Hide no entry';
$string['showlistpreference'] = 'Show users without entries';
$string['showlistyes'] = 'Show no entry';
$string['shownone'] = 'Show none';
$string['showoverview'] = 'Show diarys overview on my moodle';
$string['showrecentactivity'] = 'Show recent activity';
$string['showstudentsonly'] = 'Show students only';
$string['showteacherandstudents'] = 'Show teacher and students';
$string['showteachersonly'] = 'Show teachers only';
$string['showtextstats_help'] = 'If this option is enabled, statistics about the text will be shown.';
$string['showtextstats'] = 'Show text statistics?';
$string['showtostudentsonly'] = 'Yes, show to students only';
$string['showtoteachersandstudents'] = 'Yes, show to teachers and students';
$string['showtoteachersonly'] = 'Yes, show to teachers only';
$string['sortcurrententry'] = 'From current diary entry to the first entry.';
$string['sortfirstentry'] = 'From first diary entry to the latest entry.';
$string['sorthighestentry'] = 'From highest rated diary entry to the lowest rated entry.';
$string['sortlastentry'] = 'From latest modified diary entry to the oldest modified entry.';
$string['sortlowestentry'] = 'From lowest rated diary entry to the highest entry.';
$string['sortoptions'] = ' Sort options: ';
$string['sortorder'] = 'Sort order is: ';
$string['startnewentry'] = 'Start new entry';
$string['startoredit'] = 'Start new or edit today\'s entry';
$string['statshdr_help'] = 'These settings dfine the defaults for the statistics in all new diarys.';
$string['statshdr'] = 'Text statistics';
$string['statshide'] = 'Hide statistics';
$string['statsshow'] = 'Show statistics';
$string['studentemail'] = 'Send email notifications to students';
$string['studentemail_help'] = 'Enable or disable the capability to immediately send email notifications to students.';
$string['tablecolumncharacters'] = 'Characters';
$string['tablecolumnedit'] = 'Edit&nbsp;&nbsp;&nbsp;&nbsp;';
$string['tablecolumnparagraphs'] = 'Paragraphs';
$string['tablecolumnpromptsbgc'] = 'Prompt background color';
$string['tablecolumnprompts'] = 'Prompts';
$string['tablecolumnsentences'] = 'Sentences';
$string['tablecolumnstart'] = 'Start';
$string['tablecolumnstatus'] = 'Status';
$string['tablecolumnstop'] = 'Stop';
$string['tablecolumnwords'] = 'Words&nbsp;&nbsp;&nbsp;&nbsp;';
$string['tagarea_diary_entries'] = 'Diary entries';
$string['tcount'] = 'Currently, this diary activity has a total of {$a} writing prompts that belong to it.<br>';
$string['teacher'] = 'Teacher';
$string['teacheremail'] = 'Send email notifications to teachers';
$string['teacheremail_help'] = 'Enable or disable the capability to immediately send email notifications to teachers.';
$string['text_editor'] = 'Prompt text';
$string['text'] = 'Enter your writing prompt';
$string['textstatitems_help'] = 'Select any items here that you wish to appear in the text statistics that are shown on a view page, report page, and reportsingle page.';
$string['textstatitems'] = 'Statistical items';
$string['timecreated'] = 'Time created';
$string['timemarked'] = 'Time marked';
$string['timemodified'] = 'Time modified';
$string['toolbar'] = 'Toolbar:';
$string['totalsyllables'] = 'Total Syllables {$a}';
$string['transfer'] = 'Transfer entry\'s';
$string['transferwemail'] = 'Transfer and send email. <b>Default: Do not send email</b>';
$string['transferwfb'] = 'Transfer and include feedback regarding the transfer. <b>Default: Do not include feedback</b>';
$string['transferwfbmsg'] = '<br> This entry was transferred from the Journal named:  {$a}';
$string['transferwoe'] = 'Transfer without email';
$string['uniquewords'] = 'Unique words';
$string['userid'] = 'User id';
$string['usertoolbar'] = 'User toolbar:';
$string['viewalldiaries'] = 'View all course diaries';
$string['viewallentries'] = 'View {$a} diary entries';
$string['viewentries'] = 'View entries';
$string['warning'] = '<b>WARNING - You have {$a} current prompts, which is an error. You cannot have multiple, overlapping current dates! This needs to be fixed!</b><br>';
$string['words'] = 'Words:';
$string['wordspersentence'] = 'Words per sentence';
$string['writingpromptlable'] = 'Current writing prompt: {$a->counter} (ID: {$a->entryid}) which started on {$a->starton} and will end on {$a->endon}.<br>{$a->datatext}';
$string['writingpromptlable2'] = 'Writing prompt: ';
$string['writingpromptlable3'] = 'Writing Prompt Editor';
$string['writingpromptnotused'] = 'The normal diary settings were used for this entry\'s auto-rating percent settings.';
$string['writingpromptused'] = 'Writing prompt ID: {$a} was used for this entry\'s auto-rating percent settings.';
$string['xfrresults'] = 'There were {$a->one} entry\'s processed, and {$a->two} of them transferred.';
