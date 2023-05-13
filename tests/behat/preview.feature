@ou @ou_vle @qbehaviour @qbehaviour_selfassess
Feature: Attempt (preview) a question using the self-assessment behaviour
  As a student
  In order to get value from embedded manually graded questions
  I need to assess my one response.

  Background:
    Given the following config values are set as admin:
      | behaviour | immediatefeedback | question_preview |
      | history   | shown             | question_preview |
    And the following "users" exist:
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

  @javascript
  Scenario: Preview a question and try to submit a response with rating/comment.
    Given the following "questions" exist:
      | questioncategory | qtype     | name                  | template | canselfrate | canselfcomment |
      | Test questions   | recordrtc | Record audio question | audio    | 1           | 1              |
    When I am on the "Record audio question" "core_question > preview" page logged in as teacher
    And I should see "Please record yourself talking about Moodle."
    And "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should see "Submit: recording.ogg"
    And I click on "Rated 2 stars" "icon"
    And I press "Save"
    And I should see "Self-assessed 2 stars with no comment"
    And I click on "Rated 5 stars" "icon"
    And I set the following fields to these values:
      | Comment | Seems OK to me. |
    And I press "Save"
    And I should see "Self-assessed 5 stars with comment: Seems OK to me."

  @javascript
  Scenario: Preview a question with just comment UI, no ratings.
    Given the following "questions" exist:
      | questioncategory | qtype     | name                  | template | canselfrate | canselfcomment |
      | Test questions   | recordrtc | Record audio question | audio    | 0           | 1              |
    When I am on the "Record audio question" "core_question > preview" page logged in as teacher
    And "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should see "Submit: recording.ogg"
    And I should not see "Rating"
    And I set the following fields to these values:
      | Comment | Seems OK to me. |
    And I press "Save"
    And I should see "Commented: Seems OK to me."

  @javascript
  Scenario: Preview a question with just rating, no comment.
    Given the following "questions" exist:
      | questioncategory | qtype     | name                  | template | canselfrate | canselfcomment |
      | Test questions   | recordrtc | Record audio question | audio    | 1           | 0              |
    When I am on the "Record audio question" "core_question > preview" page logged in as teacher
    And I should see "Please record yourself talking about Moodle."
    And "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should not see "Comment" in the "div.self-assessment" "css_element"
    And I should see "Submit: recording.ogg"
    And I click on "Rated 2 stars" "icon"
    And I press "Save"
    And I should see "Self-assessed 2 stars with no comment"

  @javascript
  Scenario: Preview a question with neither comment nor rating.
    Given the following "questions" exist:
      | questioncategory | qtype     | name                  | template | canselfrate | canselfcomment |
      | Test questions   | recordrtc | Record audio question | audio    | 0           | 0              |
    When I am on the "Record audio question" "core_question > preview" page logged in as teacher
    And "teacher" has recorded "moodle-sharon.ogg" into the record RTC question
    And I press "Save"
    Then I should see "I hope you spoke clearly and coherently."
    And I should not see "Rating"
    And I should not see "Comment" in the "div.selfassess" "css_element"
    And I should see "Submit: recording.ogg"
