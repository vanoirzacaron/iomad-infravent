@mod @mod_peerreview
Feature: Setting grades to pass via peerreview editing form
  In order to define grades to pass
  As a teacher
  I can set them in the peerreview settings form, without the need to go to the gradebook

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |

  Scenario: Adding a new peerreview with grade to pass field set
    Given the following "activities" exist:
      | activity   | name             | course | idnumber    |
      | peerreview   | Awesome peerreview | c1     | peerreview1   |
    When I am on the "Awesome peerreview" "peerreview activity editing" page logged in as teacher1
    And I set the field "Submission grade to pass" to "45"
    And I set the field "Assessment grade to pass" to "10.5"
    And I press "Save and return to course"
    And I am on the "Awesome peerreview" "peerreview activity editing" page
    Then the field "Submission grade to pass" matches value "45.00"
    And the field "Assessment grade to pass" matches value "10.50"

  @javascript
  Scenario: Grade to pass kept even with submission types without online text (MDL-64862)
    Given the following "activities" exist:
      | activity | course | name             | submissiongradepass | gradinggradepass | submissiontypetextavailable |
      | peerreview | c1     | Another peerreview | 42                  | 10.1             | 0                           |
    When I am on the "Course1" course page logged in as teacher1
    Then I should not see "Adding a new peerreview"
    And I am on the "Another peerreview" "peerreview activity editing" page
    And the field "Submission grade to pass" matches value "42.00"
    And the field "Assessment grade to pass" matches value "10.10"

  Scenario: Adding a new peerreview with grade to pass fields left empty
    Given the following "activities" exist:
      | activity   | name                     | course | idnumber    |
      | peerreview   | Another awesome peerreview | c1     | peerreview1   |
    When I am on the "Another awesome peerreview" "peerreview activity editing" page logged in as teacher1
    Then the field "Submission grade to pass" matches value "0.00"
    And the field "Assessment grade to pass" matches value "0.00"

  Scenario: Adding a new peerreview with non-numeric value of a grade to pass
    Given the following "activities" exist:
      | activity   | name                     | course | idnumber    | section |
      | peerreview   | Another awesome peerreview | c1     | peerreview1   | 1       |
    When I am on the "Another awesome peerreview" "peerreview activity editing" page logged in as teacher1
    And I set the field "Assessment grade to pass" to "You shall not pass!"
    And I press "Save and return to course"
    Then I should see "Updating peerreview in Topic 1"
    And I should see "You must enter a number here"

  Scenario: Adding a new peerreview with invalid value of a grade to pass
    Given the following "activities" exist:
      | activity   | name                    | course | idnumber   | section |
      | peerreview   | Almost awesome peerreview | c1     | peerreview1  | 1       |
    When I am on the "Almost awesome peerreview" "peerreview activity editing" page logged in as teacher1
    And I set the field "Assessment grade to pass" to "10000000"
    And I press "Save and return to course"
    Then I should see "Updating peerreview in Topic 1"
    And I should see "The grade to pass can not be greater than the maximum possible grade"

  Scenario: Emptying grades to pass fields sets them to zero
    Given the following "activities" exist:
      | activity   | name                   | course | idnumber  | section |
      | peerreview   | Super awesome peerreview | c1     | peerreview1 | 1       |
    When I am on the "Super awesome peerreview" "peerreview activity editing" page logged in as teacher1
    And I set the field "Submission grade to pass" to "59.99"
    And I set the field "Assessment grade to pass" to "0.000"
    And I press "Save and return to course"
    And I should not see "Updating peerreview in Topic 1"
    And I am on the "Super awesome peerreview" "peerreview activity editing" page
    And the field "Submission grade to pass" matches value "59.99"
    And the field "Assessment grade to pass" matches value "0.00"
    When I set the field "Submission grade to pass" to ""
    And I set the field "Assessment grade to pass" to ""
    And I press "Save and display"
    Then I should not see "Adding a new peerreview"
    And I am on the "Super awesome peerreview" "peerreview activity editing" page
    And the field "Submission grade to pass" matches value "0.00"
    And the field "Assessment grade to pass" matches value "0.00"
