@mod @mod_peerreview @core_completion @javascript
Feature: peerreview submission and assessment with pass grade activity completion

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student3@example.com |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname  | shortname | enablecompletion |
      | Course1   | c1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | student3 | c1     | student        |
      | student4 | c1     | student        |
      | teacher1 | c1     | editingteacher |
    And the following "activity" exists:
      | activity                  | peerreview                  |
      | name                      | Testpeerreview              |
      | course                    | c1                        |
      | idnumber                  | peerreview1                 |
      | submissiontypetext        | 2                         |
      | submissiontypefile        | 1                         |
      | completion                | 2                         |
      | completiongradeitemnumber | 0                         |
      | submissiongradepass       | 40                        |
      | gradinggradepass          | 60                        |
      | completionpassgrade       | 1                         |
    # teacher1 sets up assessment form and changes the phase to submission
    When I am on the "Testpeerreview" "peerreview activity" page logged in as teacher1
    And I edit assessment form in peerreview "Testpeerreview" as:
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor |         |
    And I change phase in peerreview "Testpeerreview" to "Submission phase"
    # student1 submits
    And I am on the "Testpeerreview" "peerreview activity" page logged in as student1
    And I add a submission in peerreview "Testpeerreview" as:
      | Title              | Submission1  |
      | Submission content | Some content |
    # student2 submits
    And I am on the "Testpeerreview" "peerreview activity" page logged in as student2
    And I add a submission in peerreview "Testpeerreview" as:
      | Title              | Submission2  |
      | Submission content | Some content |
    # student3 submits
    And I am on the "Testpeerreview" "peerreview activity" page logged in as student3
    And I add a submission in peerreview "Testpeerreview" as:
      | Title              | Submission3  |
      | Submission content | Some content |
    # teacher1 allocates reviewers and changes the phase to assessment
    And I am on the "Testpeerreview" "peerreview activity" page logged in as teacher1
    And I allocate submissions in peerreview "Testpeerreview" as:
      | Participant   | Reviewer      |
      | Sam1 Student1 | Sam2 Student2 |
      | Sam2 Student2 | Sam1 Student1 |
      | Sam3 Student3 | Sam1 Student1 |
      | Sam2 Student2 | Sam4 Student4 |
    And I am on the "Testpeerreview" "peerreview activity" page
    And I change phase in peerreview "Testpeerreview" to "Assessment phase"
    # student1 assesses work of student2 and student3
    And I am on the "Testpeerreview" "peerreview activity" page logged in as student1
    And I assess submission "Sam2" in peerreview "Testpeerreview" as:
      | grade__idx_0            | 5 / 10            |
      | peercomment__idx_0      | You can do better |
      | grade__idx_1            | 10 / 10           |
      | peercomment__idx_1      | Amazing           |
      | Feedback for the author | Good work         |
    And I am on "Course1" course homepage
    And I assess submission "Sam3" in peerreview "Testpeerreview" as:
      | grade__idx_0            | 9 / 10      |
      | peercomment__idx_0      | Well done   |
      | grade__idx_1            | 8 / 10      |
      | peercomment__idx_1      | Very good   |
      | Feedback for the author | No comments |
    # student2 assesses work of student1
    And I am on the "Testpeerreview" "peerreview activity" page logged in as student2
    And I assess submission "Sam1" in peerreview "Testpeerreview" as:
      | grade__idx_0            | 6 / 10     |
      | peercomment__idx_0      |            |
      | grade__idx_1            | 7 / 10     |
      | peercomment__idx_1      |            |
      | Feedback for the author | Keep it up |
    # teacher1 makes sure he can see all peer grades
    And I am on the "Testpeerreview" "peerreview activity" page logged in as teacher1
    And I click on "//table/tbody/tr[td[contains(concat(' ', normalize-space(@class), ' '), ' participant ') and contains(.,'Sam2')]]/td[contains(concat(' ', normalize-space(@class), ' '), ' receivedgrade ') and contains(.,'Sam1')]/descendant::a[@class='grade']" "xpath_element"
    # teacher1 assesses the work on submission1 and assesses the assessment of peer
    And I set the following fields to these values:
      | Override grade for assessment | 11 |
      | Feedback for the reviewer     |    |
    And I press "Save and close"
    And I change phase in peerreview "Testpeerreview" to "Grading evaluation phase"
    And I follow "Submission1"
    And I press "Assess"
    And I set the following fields to these values:
      | grade__idx_0            | 1 / 10                      |
      | peercomment__idx_0      | Extremely bad               |
      | grade__idx_1            | 2 / 10                      |
      | peercomment__idx_1      | Very bad                    |
      | Feedback for the author | Your peers overestimate you |
    And I press "Save and close"
    And I press "Re-calculate grades"
    And I change phase in peerreview "Testpeerreview" to "Closed"

  Scenario: Add and assess submissions in peerreview with javascript enabled
    And I am on the "Course1" course page logged in as student1
    And the "Receive a grade" completion condition of "Testpeerreview" is displayed as "done"
    And the "Receive a passing grade" completion condition of "Testpeerreview" is displayed as "failed"
    And I am on the "Course1" course page logged in as student2
    And the "Receive a grade" completion condition of "Testpeerreview" is displayed as "done"
    And the "Receive a passing grade" completion condition of "Testpeerreview" is displayed as "done"
    And I am on the "Course1" course page logged in as student3
    And the "Receive a grade" completion condition of "Testpeerreview" is displayed as "done"
    And the "Receive a passing grade" completion condition of "Testpeerreview" is displayed as "done"
    And I am on the "Course1" course page logged in as student4
    And the "Receive a grade" completion condition of "Testpeerreview" is displayed as "todo"
    And the "Receive a passing grade" completion condition of "Testpeerreview" is displayed as "todo"
    And I am on the "Course1" course page logged in as teacher1
    And "Sam1 Student1" user has completed "Testpeerreview" activity
    And "Sam2 Student2" user has completed "Testpeerreview" activity
    And "Sam3 Student3" user has completed "Testpeerreview" activity
    And "Sam4 Student4" user has not completed "Testpeerreview" activity
