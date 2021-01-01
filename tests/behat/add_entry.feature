@mod @mod_diary
Feature: Students can add and edit entries to diary activities
  In order to express and refine my thoughts
  As a student
  I need to add and update my diary entry

  Scenario: A student edits his/her entry
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role           |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student        |
    And the following "activities" exist:
      | activity | name            | intro          | course | idnumber |
      | diary    | Test diary name | Diary question | C1     | diary1   |
    And I log in as "student1"
    And I am on "Course1" course homepage
    And I follow "Test diary name"
    And I press "Start new day or edit current day diary entry"
    And I set the following fields to these values:
      | Entry | First entry by student1. |
    And I press "Save changes"
    And I press "Start or edit my diary entry"
    Then the field "Entry" matches value "First entry by student1."
    And I set the following fields to these values:
      | Entry | Second reply |
    And I press "Save changes"
    And I press "Start new or edit today's entry"
    And the field "Entry" matches value "Second reply"
