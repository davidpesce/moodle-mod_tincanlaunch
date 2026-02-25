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

  Scenario: Student sees an xAPI activity with completion tracking enabled
    Given the following "activities" exist:
      | activity     | name                | course | idnumber | tincanlaunchurl                             | tincanactivityid                 | completion | tincanverbid                             |
      | tincanlaunch | Completion Activity | C1     | tcl3     | https://example.com/xapi-content/index.html | https://example.com/xapi-content | 2          | http://adlnet.gov/expapi/verbs/completed |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Completion Activity" in the "region-main" "region"
