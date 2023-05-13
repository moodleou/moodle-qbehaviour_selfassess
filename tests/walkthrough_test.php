<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qbehaviour_selfassess;

use question_bank;
use question_state;
use qtype_recordrtc_test_helper;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../engine/lib.php');
require_once(__DIR__ . '/../../../engine/tests/helpers.php');
require_once(__DIR__ . '/../../../type/recordrtc/tests/walkthrough_test.php');


/**
 * Unit tests for the self-assessment question behaviour.
 *
 * @package   qbehaviour_selfassess
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qbehaviour_selfassess
 * @covers    \qbehaviour_selfassess_renderer
 */
class walkthrough_test extends \qbehaviour_walkthrough_test_base {

    /**
     * Helper to get the qa of the qusetion being attempted.
     *
     * @return \question_attempt
     */
    protected function get_qa(): \question_attempt {
        return $this->quba->get_question_attempt($this->slot);
    }

    /**
     * Prepares the data (draft file) to simulate a user submitting a given fixture file.
     *
     * @param string $fixturefile name of the file to submit.
     * @param string $filename filename to submit the file under.
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function store_submission_file(
            string $fixturefile, string $filename = 'recording.ogg'): array {
        $response = $this->setup_empty_submission_fileares();
        qtype_recordrtc_test_helper::clear_draft_area($response['recording']);
        qtype_recordrtc_test_helper::add_recording_to_draft_area(
                $response['recording'], $fixturefile, $filename);
        return $response;
    }

    /**
     * Prepares the data (draft file) but with no files in it.
     *
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function setup_empty_submission_fileares(): array {
        $this->render();
        if (!preg_match('/name="' . preg_quote($this->get_qa()->get_qt_field_name('recording')) .
                '" value="(\d+)"/', $this->currentoutput, $matches)) {
            throw new \coding_exception('Draft item id not found.');
        }
        return ['recording' => $matches[1]];
    }

    /**
     * Assertion to verify that the star rating UI is not present in $this->currentoutput.
     */
    protected function assert_does_not_contain_star_rating_ui(): void {
        $this->assertStringNotContainsString('<div class="self-assessment-rating"', $this->currentoutput);
    }

    /**
     * Assertion to verify that the star rating UI is present in $this->currentoutput.
     */
    protected function assert_contains_star_rating_ui(): void {
        $this->assertStringContainsString('<div class="self-assessment-rating"', $this->currentoutput);
        $this->assertStringContainsString('<img class="icon rated" alt="Rated 5 stars" ', $this->currentoutput);
    }

    /**
     * Assert that a particular star rating radio button is the selected one.
     *
     * @param int $rating the rating that should be selected.
     */
    protected function assert_selected_rating_is(int $rating): void {
        $this->assertStringContainsString('checked="checked" class="accesshide" value="' . $rating . '">',
                $this->currentoutput);
    }

    public function test_selfassess_audio() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'canselfrate' => 1, 'canselfcomment' => 1]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(0);

        // Now self-assess.
        $this->process_submission(['-selfcomment' => 'Sounds OK',
                '-stars' => '4', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(4);
        $this->check_step_count(3);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(4);
        $this->assertEquals('Self-assessed 4 stars with comment: Sounds OK',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));

        // Re-submitting the same self-assessment should not change the grade.
        $this->process_submission(['-selfcomment' => 'Sounds OK',
                '-stars' => '4', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(4);
        $this->check_step_count(3);
    }

    public function test_selfassess_audio_no_comment() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'canselfrate' => 1, 'canselfcomment' => 1]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(0);

        // Now self-assess.
        $this->process_submission(['-selfcomment' => '',
                '-stars' => '4', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(4);
        $this->check_step_count(3);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(4);
        $this->assertEquals('Self-assessed 4 stars with no comment',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));

        // Re-submitting the same self-assessment should not change the grade.
        $this->process_submission(['-selfcomment' => '',
                '-stars' => '4', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(4);
        $this->check_step_count(3);
    }

    public function test_selfassess_no_audio() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'canselfrate' => 1, 'canselfcomment' => 1]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Try to submit a blank response, and check it is rejected.
        $response = $this->setup_empty_submission_fileares();
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();
        $this->assertStringContainsString('Please complete your answer.',
                $this->currentoutput);

        // Submit all and finish even though not submission was made. Verify you can still self-grade.
        $this->finish();

        $this->check_current_state(question_state::$gaveup);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(0);

        // Now self-assess.
        $this->process_submission(['-selfcomment' => '',
                '-stars' => '4', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(4);
        $this->check_step_count(4);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(4);
        $this->assertEquals('Self-assessed 4 stars with no comment',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));
    }

    public function test_selfassess_comment_no_rating() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'canselfrate' => 0, 'canselfcomment' => 1]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Now self-assess.
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();
        $this->assertEquals('Commented: Sounds OK',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));

        // Re-submitting the same self-assessment should not change the grade.
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_step_count(3);
    }

    public function test_selfassess_without_clicking_button() {
        // This test simulates what happens if the student inputs a rating and/or a comment,
        // without clicking the 'Save' button, but instead just going to the next page of the quiz.
        // That should be treatd as if they did click the button.
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'canselfrate' => 1, 'canselfcomment' => 1]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(0);

        // Now simulate going to the next page of the quiz, without changing the self-assessment.
        // This should not add a step.
        $this->process_submission(['-selfcomment' => '', '-stars' => '0']);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(0);

        // Now simulate adding a rating and comment and going to the next page of the quiz.
        // This should be saved with a sensible summary.
        $this->process_submission(['-selfcomment' => 'Seems OK', '-stars' => '3']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(3);
        $this->check_step_count(3);
        $this->render();
        $this->assert_contains_star_rating_ui();
        $this->assert_selected_rating_is(3);
        $this->assertEquals('Self-assessed 3 stars with comment: Seems OK',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));

        // Re-submitting the same self-assessment should not change the grade.
        $this->process_submission(['-selfcomment' => 'Seems OK', '-stars' => '3']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(3);
        $this->check_step_count(3);
    }

    public function test_selfassess_no_feedback() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio',
                ['category' => $cat->id, 'generalfeedback' => '',
                        'canselfrate' => 0, 'canselfcomment' => 0]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 5);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('selfassess', $this->get_qa()->get_behaviour_name());
        $this->render();

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        // Verify that there is no feedback at all.
        $this->assertStringNotContainsString('outcome', $this->currentoutput);
    }
}
