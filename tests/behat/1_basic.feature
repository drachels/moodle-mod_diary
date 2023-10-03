@mod @mod_diary1
Feature: Basic diary use
  In order to complete diary entries
  As a teacher or student
  I need to make a diary entry

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity  | name           | intro               | course | section | idnumber |
      | diary     | Test diary     | This is a diary     | C1     | 1       | D001     |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: A teacher creates a diary entry
    When I am on the "Test diary" "diary activity" page logged in as "teacher1"
    Then I should see "Test diary"
    And I should see "This is a diary"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Some sample text by teacher1."
    And I press "Save changes"
    And I should see "Test diary"
    And I log out

  @javascript
  Scenario: A student creates a diary entry
    When I am on the "Test diary" "diary activity" page logged in as "student1"
    Then I should see "Test diary"
    And I should see "This is a diary"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Some sample text by student1."
    And I press "Save changes"
    And I should see "Test diary"
    And I log out

