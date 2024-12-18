@mod @mod_peerreview
Feature: peerreview 'Late submissions are allowed' task
  In order to encourage students submit their submissions on time when late submissions are allowed
  We want only students who have not submitted their work to see the 'Late submissions are allowed' task
  and they can only see this after the submission deadline.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | student1 | Sam1      | Student1 | student1@example.com  |
      | student2 | Sam2      | Student2 | student2@example.com  |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com  |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | teacher1 | c1     | editingteacher |
    And the following "activities" exist:
      | activity | name          | course | idnumber  | latesubmissions | submisstionstart | submissionend |
      | peerreview | Testpeerreview1 | c1     | peerreview1 | 1               | 1514904308       | 1893369600    |
    # Teacher sets up assessment form and changes the phase to submission.
    And I am on the "Testpeerreview1" "peerreview activity" page logged in as teacher1
    And I edit assessment form in peerreview "Testpeerreview1" as:
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor | Aspect3 |
    And I change phase in peerreview "Testpeerreview1" to "Submission phase"

  @javascript
  Scenario: Student1 add his submission to Testpeerreview1 before submission deadline, but student2 does not submitt
    # Student1 submits.
    When I am on the "Testpeerreview1" "peerreview activity" page logged in as student1
    Then I should see "Submissions deadline:"
    And I should not see "Late submissions are allowed"
    And I add a submission in peerreview "Testpeerreview1" as:
      | Title              | Submission from s1  |
      | Submission content | Some content from student1 |

    # Teacher modifies submission deadline.
    When I am on the "Testpeerreview1" "peerreview activity" page logged in as teacher1
    Then I should see "Late submissions are allowed"
    And I navigate to "Settings" in current page administration
    And I click on "Expand all" "link" in the "region-main" "region"
    And I set the field "id_submissionend_day" to "1"
    And I set the field "id_submissionend_month" to "January"
    And I set the field "id_submissionend_year" to "2018"
    And I click on "Save and display" "button"
    And I follow "Switch to the assessment phase"

    # Student1 has already submitted and cannot see 'Late submissions are allowed'.
    When I am on the "Testpeerreview1" "peerreview activity" page logged in as student1
    Then I should see "Submissions deadline:"
    And I should not see "Late submissions are allowed"

    # Student2 has not submitted yet who can see 'Late submissions are allowed' text after the submission deadline.
    When I am on the "Testpeerreview1" "peerreview activity" page logged in as student2
    Then I should see "Submissions deadline:"
    And I should see "Monday, 1 January 2018"
    And I should see "Late submissions are allowed"

    # Teacher can see 'Late submissions are allowed' text after submission deadline.
    When I am on the "Testpeerreview1" "peerreview activity" page logged in as teacher1
    Then I should see "Submissions deadline:"
    And I should see "Monday, 1 January 2018"
    And I should see "Late submissions are allowed"
