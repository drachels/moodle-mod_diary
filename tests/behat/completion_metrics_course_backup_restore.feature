@mod @mod_diary
Feature: Course restore preserves weighted diary completion metrics
  In order to migrate courses safely
  As an admin or teacher
  I need course restore to keep weighted completion metric penalties

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name                    | intro | course | enablestats | enableautorating |
      | diary    | Metrics course backup   | n     | C1     | 1           | 1                |
    And the following diary metric requirements are configured:
      | diary                  | metric      | operator | value  | penalty |
      | Metrics course backup  | uniquewords | >=       | 999999 | 4       |
      | Metrics course backup  | shortwords  | >=       | 999999 | 2       |
    And the following config values are set as admin:
      | texteditors | textarea |
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Restored course keeps weighted grouped penalties
    Given I am on the "Metrics course backup" "diary activity" page logged in as "student1"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Course restore weighted penalty check text."
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | diary_metrics_course_backup.mbz |
    And I restore "diary_metrics_course_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 restored |
      | Schema | Course short name | C2R               |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 2 restored" course homepage
    And I follow "Metrics course backup"
    And I follow "View 1 diary entries"
    Then I should see "2 unmet item(s) out of 2 configured requirement(s), 6 points off."
    And I should see "[pen 4]"
    And I should see "[pen 2]"
