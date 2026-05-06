@mod @mod_diary
Feature: Diary attachment persistence for mobile parity
  In order to preserve media work across edit/view/report flows
  As a teacher and student
  I need diary attachments to persist after save and remain visible when re-opened

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
      | activity | name                     | intro | course |
      | diary    | Mobile attachment diary  | n     | C1     |
    And the following config values are set as admin:
      | texteditors | textarea |

  @javascript @_file_upload
  Scenario: Attachment remains visible after save, reopen, and teacher report view
    Given I am on the "Mobile attachment diary" "diary activity" page logged in as "student1"
    When I press "Start new or edit today's entry"
    And I set the field "Entry" to "Entry with attachment persistence check."
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachment" filemanager
    And I press "Save changes"
    Then I should see "empty.txt"
    When I press "Start new or edit today's entry"
    Then I should see "empty.txt"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Mobile attachment diary"
    And I follow "View 1 diary entries"
    Then I should see "empty.txt"
