@mod @mod_diary
Feature: Delete diary entry with confirmation
  In order to remove unwanted diary entries
  As a student or teacher
  I need to be able to delete an entry and confirm the action

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity | name            | intro    | course | idnumber |
      | diary    | Test diary      | Diary    | C1     | diary1   |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  @javascript
  Scenario: Student deletes an entry and confirms deletion
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test diary"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Entry to be deleted."
    And I press "Save changes"
    And I press "Edit this entry"
    And I press "Delete"
    When I press "Delete"
    Then I should see "You have not started this diary yet."
