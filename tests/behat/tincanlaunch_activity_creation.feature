@mod @mod_tincanlaunch
Feature: Create and configure xAPI Launch Link activities
  In order to use xAPI content with Moodle
  As a teacher
  I need to be able to create and configure xAPI Launch Link activities

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | tincanlaunchlrsendpoint       | https://lrs.example.com/endpoint/ | tincanlaunch |
      | tincanlaunchlrsauthentication | 1                                 | tincanlaunch |
      | tincanlaunchlrslogin          | testkey                           | tincanlaunch |
      | tincanlaunchlrspass           | testsecret                        | tincanlaunch |
      | tincanlaunchlrsduration       | 9000                              | tincanlaunch |
      | tincanlaunchcustomacchp       |                                   | tincanlaunch |
      | tincanlaunchuseactoremail     | 1                                 | tincanlaunch |

  @javascript
  Scenario: Teacher creates a basic xAPI Launch Link activity
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "xAPI Launch Link" to section "1"
    And I set the following fields to these values:
      | Launch link name | My xAPI Activity                            |
      | Launch URL       | https://example.com/xapi-content/index.html |
      | Activity ID      | https://example.com/xapi-content            |
    And I press "Save and return to course"
    Then I should see "My xAPI Activity" in the "region-main" "region"

  @javascript
  Scenario: Teacher creates an xAPI activity with simplified launch
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "xAPI Launch Link" to section "1"
    And I set the following fields to these values:
      | Launch link name        | Simplified xAPI Activity                    |
      | Launch URL              | https://example.com/xapi-content/index.html |
      | Activity ID             | https://example.com/xapi-content            |
      | Enable simplified launch | 1                                          |
    And I press "Save and return to course"
    Then I should see "Simplified xAPI Activity" in the "region-main" "region"

  @javascript
  Scenario: Teacher edits an existing xAPI Launch Link activity
    Given the following "activities" exist:
      | activity      | name              | course | idnumber | tincanlaunchurl                             | tincanactivityid                   |
      | tincanlaunch  | Existing Activity | C1     | tcl1     | https://example.com/xapi-content/index.html | https://example.com/xapi-content   |
    And I log in as "teacher1"
    And I am on the "Existing Activity" "tincanlaunch activity" page
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Launch link name | Updated Activity Name |
    And I press "Save and return to course"
    Then I should see "Updated Activity Name" in the "region-main" "region"

  @javascript
  Scenario: Student can see an xAPI Launch Link activity
    Given the following "activities" exist:
      | activity      | name           | course | idnumber | tincanlaunchurl                             | tincanactivityid                   |
      | tincanlaunch  | Student Activity | C1   | tcl2     | https://example.com/xapi-content/index.html | https://example.com/xapi-content   |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Student Activity" in the "region-main" "region"
