@mod @mod_tincanlaunch
Feature: xAPI Launch Link activity completion settings
  In order to track learner progress with xAPI content
  As a teacher
  I need to be able to configure completion rules for xAPI activities

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
  Scenario: Teacher creates an xAPI activity with verb-based completion
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "xAPI Launch Link" to section "1"
    And I set the following fields to these values:
      | Launch link name | Completion xAPI Activity                    |
      | Launch URL       | https://example.com/xapi-content/index.html |
      | Activity ID      | https://example.com/xapi-content            |
    And I expand all fieldsets
    And I set the following fields to these values:
      | Add requirements         | 1                                          |
      | completionverbenabled    | 1                                          |
      | tincanverbid             | http://adlnet.gov/expapi/verbs/completed   |
    And I press "Save and return to course"
    Then I should see "Completion xAPI Activity" in the "region-main" "region"

  @javascript
  Scenario: Student sees completion requirements on xAPI activity
    Given the following "activities" exist:
      | activity     | name                 | course | idnumber | tincanlaunchurl                             | tincanactivityid                 | completion | tincanverbid                               |
      | tincanlaunch | Completion Activity  | C1     | tcl3     | https://example.com/xapi-content/index.html | https://example.com/xapi-content | 2          | http://adlnet.gov/expapi/verbs/completed   |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Completion Activity" in the "region-main" "region"
