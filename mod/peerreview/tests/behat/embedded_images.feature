@mod @mod_peerreview @javascript
Feature: Teachers can embed images into instructions and conclusion fields
  In order to display images as a part of instructions or conclusions in the peerreview
  As a teacher
  I need to be able to embed images into the fields and they should display correctly

  @editor_tiny
  Scenario: Embedding the image into the instructions and conclusions fields
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "user private files" exist:
      | user     | filepath                                   | filename       |
      | teacher1 | mod/peerreview/tests/fixtures/moodlelogo.png | moodlelogo.png |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And the following "activities" exist:
      | activity | course | name                          |
      | peerreview | C1     | peerreview with embedded images |
    And I am on the "peerreview with embedded images" "peerreview activity editing" page logged in as admin
    And I set the following fields to these values:
      | Instructions for submission format  | 1 |
      | Instructions for assessment format  | 1 |
      | Conclusion format                   | 1 |
    And I press "Save and display"
    And I log out
    When I log in as "teacher1"
    # Edit the peerreview.
    And I am on the "peerreview with embedded images" "peerreview activity editing" page
    And I expand all fieldsets
    And I click on "Image" "button" in the "Instructions for submission" "form_row"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodlelogo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "How to submit"
    And I click on "Save image" "button"
    And I press "Save and display"
    # Embed the image into Instructions for assessment.
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Image" "button" in the "Instructions for assessment" "form_row"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodlelogo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "How to assess"
    And I click on "Save image" "button"
    And I press "Save and display"
    # Embed the image into Conclusion.
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Image" "button" in the "Conclusion" "form_row"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodlelogo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "Well done"
    And I click on "Save image" "button"
    And I press "Save and display"
    # Save the form and check the images are displayed in appropriate phases.
    And I change phase in peerreview "peerreview with embedded images" to "Submission phase"
    Then "//*[contains(@class, 'instructions')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/mod_peerreview/instructauthors/moodlelogo.png') and @alt='How to submit']" "xpath_element" should exist
    And I change phase in peerreview "peerreview with embedded images" to "Assessment phase"
    Then "//*[contains(@class, 'instructions')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/mod_peerreview/instructreviewers/moodlelogo.png') and @alt='How to assess']" "xpath_element" should exist
    And I change phase in peerreview "peerreview with embedded images" to "Closed"
    Then "//*[contains(@class, 'conclusion')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/mod_peerreview/conclusion/moodlelogo.png') and @alt='Well done']" "xpath_element" should exist
