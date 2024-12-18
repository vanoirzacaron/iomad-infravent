@mod @mod_peerreview @_file_upload
Feature: Provide example submission
  In order to let students practise the assessment process in the peerreview
  As a teacher
  I need to be able to define example submission and its referential assessment

  @javascript
  Scenario: Add example submission with attachments to a peerreview
    # Prepare the users, course, enrolments and the peerreview instance.
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |
    And the following "activities" exist:
      | activity | name         | course | idnumber  | useexamples |
      | peerreview | Testpeerreview | c1     | peerreview1 | 1           |
    # As a teacher, define the assessment form to be used in the peerreview.
    When I am on the "Course1" course page logged in as teacher1
    And I edit assessment form in peerreview "Testpeerreview" as:
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor |         |
    # Add an example submission with an attachment.
    And I press "Add example submission"
    And I set the following fields to these values:
      | Title | First example submission |
      | Submission content | Just an example but hey, it works! |
      | Attachment | lib/tests/fixtures/empty.txt |
    And I press "Save changes"
    # Make sure that the submission was saved.
    Then I should see "First example submission"
    And I should see "Just an example but hey, it works!"
    And "empty.txt" "link" should exist
