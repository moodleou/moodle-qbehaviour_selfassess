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

  Scenario: Preview a question and try to submit nothing.
    Given the following config values are set as admin:
      | behaviour | immediatefeedback | question_preview |
    When I choose "Preview" action for "Record audio question" in the question bank
    And I switch to "questionpreview" window
    Then I should see "Please record yourself talking about Moodle."
    And I press "Check"
    And I should see "Not answered"
    And I should see "I hope you spoke clearly and coherently."
    And I set the following fields to these values:
      | Rate your response | ★★★★☆        |
      | Comment            | Seems OK to me. |
    And I press "Save"
    And the following fields match these values:
      | Rate your response | ★★★★☆        |
      | Comment            | Seems OK to me. |
    And I switch to the main window
