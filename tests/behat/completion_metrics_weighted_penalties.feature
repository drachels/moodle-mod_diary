@mod @mod_diary
Feature: Completion metric weighted penalties in auto-rating table
  In order to keep auto-rating penalties aligned with configured metric weights
  As a teacher
  I need grouped completion metric penalty rows to reflect weighted points off

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
      | activity | name                   | intro | course | enablestats | enableautorating |
      | diary    | Metrics weighted test  | n     | C1     | 1           | 1                |
    And the following diary metric requirements are configured:
      | diary                  | metric      | operator | value  | penalty |
      | Metrics weighted test  | uniquewords | >=       | 999999 | 4       |
      | Metrics weighted test  | shortwords  | >=       | 999999 | 2       |
    And the following config values are set as admin:
      | texteditors | textarea |

  @javascript
  Scenario: Teacher sees weighted points off in grouped unique-metrics row
    When I am on the "Metrics weighted test" "diary activity" page logged in as "student1"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Simple sample text for weighted penalty checks."
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Metrics weighted test"
    And I follow "View 1 diary entries"
    Then I should see "2 unmet item(s) out of 2 configured requirement(s), 6 points off."
    And I should see "[pen 4]"
    And I should see "[pen 2]"
