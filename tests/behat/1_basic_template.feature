@mod @mod_diary1
Feature: Teacher can setup diary
  In order to complete diary entries
  As a teacher
  I need to set up a diary activity

  @javascript
  Scenario: A teacher creates a diary activity
    # Teacher 1 adds diary activity.
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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "diary" to section "1" and I fill the form with:
      | Name        | Diary name          |
      | Description | A diary for testing |
    And I follow "Diary name"
    And I should see "Start new entry"
    And I follow "Start new entry"
    And I set the field "Entry" to "Some sample text."
    And I press "Save changes"
    And I should see "diary Name"
    And I should see "A diary for testing"
    And I wait "3" seconds
    And I log out
