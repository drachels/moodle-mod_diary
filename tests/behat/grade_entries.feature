@mod @mod_diary
Feature: Teacher can view, comment and grade students entries
  In order to interact with students to refine an answer
  As a teacher
  I need to comment and grade users entries

  Background:
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
    And I log in as "student1"
    And I am on "Course1" course homepage
    And I follow "Test diary name"
    And I press "Start new day or edit current day diary entry"
    And I set the following fields to these values:
      | Entry | Student 1 first reply |
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I am on "Course1" course homepage
    And I follow "Test diary name"
    And I should see "Diary question"
    And I press "Start new day or edit current day diary entry"
    And I set the following fields to these values:
      | Entry | Student 2 first reply |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course1" course homepage

  Scenario: Teacher can access students entries from the diarys list page
    When I follow "Course 1"
    And I turn editing mode on
    And I add the "Activities" block
    And I click on "Diarys" "link" in the "Activities" "block"
    Then I should see "Diary question" in the "Test diary name" "table_row"
    And I should see "View 2 diary entries" in the "Test diary name" "table_row"
    And I follow "View 2 diary entries"

  Scenario: Teacher grades and adds/edits feedback to student's entries
    When I follow "Test diary name"
    And I should see "Diary question"
    And I follow "View 2 diary entries"
    Then I should see "Student 1 first reply" in the "//table[@class='diaryuserentry']/descendant::td[@class='userfullname'][contains(., 'Student 1')]/ancestor::table[@class='diaryuserentry']" "xpath_element"
    And I should see "Student 2 first reply" in the "//table[@class='diaryuserentry']/descendant::td[@class='userfullname'][contains(., 'Student 2')]/ancestor::table[@class='diaryuserentry']" "xpath_element"
    And I should not see "Entry has changed since last feedback was saved."
    And I set the field "Student 2 Grade" to "94"
    And I set the field "Student 2 Feedback" to "Well done macho man"
    And I set the field "Student 1 Grade" to "22"
    And I set the field "Student 1 Feedback" to "You can do it better"
    And I press "Save all my feedback"
    And I should see "Feedback updated for 2 entries"
    And the field "Student 2 Grade" matches value "94"
    And the field "Student 2 Feedback" matches value "Well done macho man"
    And the field "Student 1 Grade" matches value "22"
    And the field "Student 1 Feedback" matches value "You can do it better"
    And I set the field "Student 1 Grade" to "100"
    And I set the field "Student 1 Feedback" to "You could not do it better"
    And I press "Save all my feedback"
    And I should see "Feedback updated for 1 entries"
    And the field "Student 1 Feedback" matches value "You could not do it better"
    And the field "Student 1 Grade" matches value "100"
    And the field "Student 2 Feedback" matches value "Well done macho man"
    # Check that users see the regraded message
    And I log out
    And I log in as "student1"
    And I am on "Course1" course homepage
    And I follow "Test diary name"
    And I press "Start new day or edit current day diary entry"
    And I set the following fields to these values:
      | Entry | Student 1 edited first reply |
    And I press "Save changes"
    And I should see "Entry has changed since last feedback was saved"
    And I log out
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "Test diary name"
    And I follow "View 2 diary entries"
    And I should see "Entry has changed since last feedback was saved" in the "//table[@class='diaryuserentry'][contains(., 'Student 1')]" "xpath_element"
    And I should see "Student 1 edited first reply" in the "//table[@class='diaryuserentry'][contains(., 'Student 1')]" "xpath_element"
    And I should not see "Entry has changed since last feedback was saved" in the "//table[@class='diaryuserentry'][contains(., 'Student 2')]" "xpath_element"
    And I should see "Student 2 first reply" in the "//table[@class='diaryuserentry'][contains(., 'Student 2')]" "xpath_element"
