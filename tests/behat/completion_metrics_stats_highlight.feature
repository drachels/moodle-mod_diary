@mod @mod_diary
Feature: Completion metric highlights in stats table
  In order to guide student writing improvements
  As a teacher and student
  I need configured completion metrics to show clear pass/fail highlights in stats rows

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
      | activity | name                  | intro | course | enablestats | enableautorating |
      | diary    | Metrics highlight test | n     | C1     | 1           | 1                |
    And the following diary metric requirements are configured:
      | diary                  | metric      | operator | value  | penalty |
      | Metrics highlight test | uniquewords | <=       | 999999 | 1       |
      | Metrics highlight test | shortwords  | >=       | 999999 | 2       |
    And the following config values are set as admin:
      | texteditors | textarea |

  @javascript
  Scenario: Student sees success and needs-work metric highlight notes
    When I am on the "Metrics highlight test" "diary activity" page logged in as "student1"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Simple sample text for metrics highlighting checks."
    And I press "Save changes"
    Then I should see "Target met. Aim to stay at or below 999999. This goal is worth 1 point."
    And I should see "Keep working. Aim for at least 999999. This goal is worth 2 points."
    And I should see "Unique words" in the "//td[contains(@class,'table-success')]" "xpath_element"
    And I should see "Unique short words" in the "//td[contains(@class,'table-danger')]" "xpath_element"
