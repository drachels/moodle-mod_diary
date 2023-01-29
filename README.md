# Moodle Diary module
Diary is based off the Journal plugin. The Diary module allows a teacher to collect online text, audio, and video from a user. Depending on the Diary setup options, a user can create a new entry each calendar day or even each minute. Each entry can be individually reviewed, provided with feedback, and graded, with overall rating results being shown based on the selected aggregate type of rating. Currently, the user can edit any entry, and can even create a new entry for a skipped day. The data submitted by a student is visible only to the teacher, course manager, or site admin, and not to the other users in the course. 

Inside or outside the classroom, students post entry's into a Diary
activity from notebook, netbook, android, iPhone, iPod or any other device
which can access the Moodle site. 

- Moodle tracker component: https://github.com/drachels/moodle-mod_diary/issues
- Documentation: https://docs.moodle.org/401/en/Diary
- Source Code: https://github.com/drachels/moodle-mod_diary
- License: http://www.gnu.org/licenses/gpl-2.0.txt

## Install from git
- Navigate to Moodle root folder.
- **git clone git://github.com/drachels/moodle-mod_diary.git mod/diary**
- **cd diary
- **git checkout MOODLE_XY_STABLE** (where XY is the moodle version, e.g: MOODLE_30_STABLE, MOODLE_28_STABLE...)
- Click the 'Notifications' link on the frontpage administration block or **php admin/cli/upgrade.php** if you have access to a command line interpreter.

## Install from a compressed file
- Extract the compressed file data.
- Rename the main folder to diary.
- Copy to the Moodle mod/ folder.
- Click the 'Notifications' link on the frontpage administration block.

## Install directly in newer versions of Moodle
- Download the zip file from Moodle downloads.
- In your Moodle navigate to Site administration > Plugins > Install plugins.
- Drag and drop the downloaded zip file into the file area.
- Click the 'Install plugin from the ZIP file' button.

## Install directly from Moodle plugins
- Your site must be registered at Moodle.
- In your web browser navigate to Moodle.org > Downloads > Moodle Plugins directory > Search for 'Diary'.
- From the Diary plugin page, click on, 'Install now'.
- Click the 'Install now' link corresponding to your site.
- Follow the prompts to complete the install.