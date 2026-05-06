@mod @mod_diary
Feature: Activity-level restore preserves diary completion metric requirements
  In order to reuse a diary activity safely
  As a teacher
  I need restored diary activities to keep completion metric settings and penalties

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
      | activity | name                     | intro | course | enablestats | enableautorating |
      | diary    | Metrics activity backup  | n     | C1     | 1           | 1                |
      | diary    | Metrics control activity | n     | C1     | 1           | 1                |
    And the following diary metric requirements are configured:
      | diary                    | metric      | operator | value  | penalty |
      | Metrics activity backup  | uniquewords | <=       | 999999 | 1       |
      | Metrics activity backup  | shortwords  | >=       | 999999 | 2       |
    And the following config values are set as admin:
      | texteditors | textarea |
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Restored activity keeps metric requirement guidance text
    Given I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | diary_metrics_activity_backup.mbz |
    And I restore "diary_metrics_activity_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 1 restored |
      | Schema | Course short name | C1R               |
    And I log out
    And I log in as "student1"
    And I am on "Course 1 restored" course homepage
    And I follow "Metrics activity backup"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Restore check text for activity metric requirements."
    And I press "Save changes"
    Then I should see "Target met. Aim to stay at or below 999999. This goal is worth 1 point."
    And I should see "Keep working. Aim for at least 999999. This goal is worth 2 points."
