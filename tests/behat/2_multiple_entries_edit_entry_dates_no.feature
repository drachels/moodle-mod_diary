@mod @mod_diary1
Feature: Multiple entries with edit entry dates no
  In order to complete diary entries without editing dates
  As a teacher or student
  I need to make multiple diary entries and not be able to edit the dates

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

# Note that for this feature, the settings for the activty will need to be modified
# so that users can make multiple entries.
  @javascript
  Scenario: A student creates multiple entries in one diary (old journal mode)
    When I am on the "Test diary" "diary activity" page logged in as "student1"
    Then I should see "Test diary"
    And I should see "This is a diary"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Some sample text by student1."
    And I press "Save changes"
    And I should see "Test diary"
    And I should see "This is a diary"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    #And I should see "Some sample text by student1."
    And I set the field "Entry" to "A second sample text by student1."
    And I press "Save changes"
    And I should see "Test diary"
    And I should see "This is a diary"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "A third sample text by student1."
    And I press "Save changes"
    And I should see "Test diary"
    And I should see "A third sample text by student1."
    And I log out

  @javascript
  Scenario: A teacher creates multiple diary entries
    When I am on the "Test diary" "diary activity" page logged in as "teacher1"
    Then I should see "Test diary"
    And I should see "This is a diary"
    And I should see "View 0 diary entries"
    And I should see "Start new or edit today's entry"
    And I press "Start new or edit today's entry"
    And I set the field "Entry" to "Some sample text by teacher1."
    And I press "Save changes"
    And I should see "Test diary"
    And I log out
