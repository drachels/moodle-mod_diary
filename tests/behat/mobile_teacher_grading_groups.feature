@mod @mod_diary
Feature: Mobile teacher grading view with group selector
  In order to grade diary entries on mobile
  As a teacher
  I need group selector controls and feedback actions to be available in mobile view

  Background:
    Given the following "courses" exist:
      | fullname | shortname | groupmode |
      | Course 1 | C1        | 1         |
    And the following "activities" exist:
      | activity | name       | intro | course | idnumber |
      | diary    | Test diary | Test  | C1     | diary1   |
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | teacher1 | Teacher   | 1        | t1@example.com |
      | student1 | Student   | 1        | s1@example.com |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
    And the following "group members" exist:
      | user     | group |
      | student1 | GA    |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test diary"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Test entry for grading."
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Teacher can see group selector in mobile grading view
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "Test diary"
    Then I should see "Group A" in the "page" region
