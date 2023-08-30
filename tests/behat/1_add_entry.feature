@mod @mod_diary
Feature: Students can add and edit entries to diary activities
  In order to express and refine my thoughts
  As a student
  I need to add and update my diary entry

  Scenario: A student edits his/her entry
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
      | Course 2 | C2        | 0        | 1         |
      | Course 3 | C3        | 0        | 1         |
    And the following "activities" exist:
      | activity | course | idnumber  | name              | intro                    |
      | diary    | C1     | diary1    | Test diary name 1 | Test diary description 1 |
      | diary    | C2     | diary2    | Test diary name 2 | Test diary description 2 |
      | diary    | C3     | diary3    | Test diary name 3 | Test diary description 3 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C2     | GC21     |
      | Group 1 | C3     | GC31     |
      | Group 2 | C3     | GC32     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
      | student2 | C2     | student        |
      | teacher1 | C3     | editingteacher |
      | student3 | C3     | student        |
    And the following "group members" exist:
      | user     | group |
      | student1 | GC21  |
      | student2 | GC21  |
      | student3 | GC31  |
      | student3 | GC32  |
    When I am on the "diary1" "diary activity" page logged in as student1
    And I should see "You have not started this diary yet."
    And I press "Start new entry" #BROKEN
    And I set the following fields to these values:
      | Entry | First entry by student1. |
    And I press "Save changes"
    And I press "Edit this entry"
    Then the field "Entry" matches value "First entry by student1."
    And I set the following fields to these values:
      | Entry | Second entry by student1. |
    And I press "Save changes"
    Then the field "Entry" matches value "First entry by student1.Second entry by student1."
    And I press "Start new entry"
    And I set the following fields to these values:
      | Entry | Third entry by student1. |
    And I press "Save changes"
