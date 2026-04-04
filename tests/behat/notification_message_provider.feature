@mod @mod_diary
Feature: Diary notification message provider
  In order to receive notifications about diary entries
  As a user
  I need message provider settings to be properly registered and language strings available

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email      |
      | teacher1 | Teacher   | 1        | t1@asd.com |
      | student1 | Student   | 1        | s1@asd.com |

  Scenario: Diary message provider language string is registered
    Given I log in as "admin"
    When I open diary notification preferences
    Then I should see "Notification preferences"
    And I should see "Diary entry confirmation"
