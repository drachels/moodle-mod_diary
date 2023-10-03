@mod @mod_diary1
Feature: Basic diary use
  In order to complete diary entries
  As a teacher or student
  I need to make diary entries

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity  | name             | intro                       | course | section | idnumber |
      | diary     | Diary for testing | This diaries introduction. | C1     | 1       | D001     |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Terri     | Teacher  | teacher1@asd.com |
      | student1 | Owen      | Money    | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: A student creates multiple entries in one diary (old journal mode)
    When I am on the "Diary for testing" "diary activity" page logged in as "student1"
    Then I should see "Diary for testing"
    And I should see "This diaries introduction."
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "First sample text by Owen Money."
    And I wait 3 seconds
    And I press "Save changes"
    And I should see "Diary for testing"
    And I should see "This diaries introduction."
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Second sample text by Owen Money."
    And I wait 3 seconds
    And I press "Save changes"
    And I should see "Diary for testing"
    And I should see "This diaries introduction."
    And I wait 3 seconds
    And I log out

  #@javascript
  #Scenario: A teacher creates multiple diary entries
    When I am on the "Diary for testing" "diary activity" page logged in as "teacher1"
    Then I should see "Diary for testing"
    And I should see "This diaries introduction."
    #And I should see "View 0 diary entries"
    And I should see "View 1 diary entries"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "First sample text by Terri Teacher."
    And I wait 3 seconds
    And I press "Save changes"
    And I should see "Diary for testing"
    And I should see "View 2 diary entries"
    And I wait 3 seconds
    And I follow "View 2 diary entries"
    Then I should see "Owen Money"
    And I should see "Add to feedback"
    And I press "Add to feedback"
    And I should see "Terri Teacher"
    And I wait 3 seconds
    And I log out
