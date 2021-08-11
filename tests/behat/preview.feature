@ou @ou_vle @qbehaviour @qbehaviour_selfassess @_switch_window @javascript
Feature: Attempt (preview) a question using the self-assessment behaviour
  As a student
  In order to get value from embedded manually graded questions
  I need to assess my one response.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Mark      | Allright | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name                  | template |
      | Test questions   | recordrtc | Record audio question | audio    |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  Scenario: Preview a question and try to submit a response with rating/comment.
    Given the following config values are set as admin:
      | behaviour | immediatefeedback | question_preview |
      | history   | shown             | question_preview |
    And I choose "Preview" action for "Record audio question" in the question bank
    And I switch to "questionpreview" window
    And I should see "Please record yourself talking about Moodle."
    When "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should see "Submit: File recording.ogg"
    And I click on "Rated 2 stars" "icon"
    And I press "Save"
    And I should see "Self-assessed 2 stars with no comment"
    And I click on "Rated 5 stars" "icon"
    And I set the following fields to these values:
      | Comment       | Seems OK to me. |
    And I press "Save"
    And I should see "Self-assessed 5 stars with comment: Seems OK to me."
    And I switch to the main window

  Scenario: Preview a question with max mark 0. Just comment UI, no ratings.
    Given I choose "Preview" action for "Record audio question" in the question bank
    And I switch to "questionpreview" window
    And I set the following fields to these values:
      | How questions behave | Immediate feedback |
      | Marked out of        | 0                  |
      | Response history     | Shown              |
    And I press "Start again with these options"
    When "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should see "Submit: File recording.ogg"
    And I should not see "Rating"
    And I set the following fields to these values:
      | Comment       | Seems OK to me. |
    And I press "Save"
    And I should see "Commented: Seems OK to me."
