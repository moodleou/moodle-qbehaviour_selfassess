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

/**
 * This file contains tests that walks a question through the self-assessment behaviour.
 *
 * @package    qbehaviour_selfassess
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../../../engine/lib.php');
require_once(__DIR__ . '/../../../engine/tests/helpers.php');
require_once(__DIR__ . '/../../../type/recordrtc/tests/walkthrough_test.php');


/**
 * Unit tests for the self-assessment question behaviour.
 */
class qbehaviour_selfassess_walkthrough_testcase extends qtype_recordrtc_walkthrough_testcase {
    /**
     * Assertion to verify that the star rating UI is not present in $this->currentoutput.
     */
    protected function assert_does_not_contain_star_rating_ui(): void {
        $this->assertNotContains('<div class="self-assessment-rating"', $this->currentoutput);
    }

    /**
     * Assertion to verify that the star rating UI is present in $this->currentoutput.
     */
    protected function assert_contains_star_rating_ui(): void {
        $this->assertContains('<div class="self-assessment-rating"', $this->currentoutput);
        $this->assertContains('<img class="icon rated" alt="Rated 5 stars" ', $this->currentoutput);
    }

    /**
     * Assert that a particular star rating radio button is the selected one.
     *
     * @param int $rating the rating that should be selected.
     */
    protected function assert_selected_rating_is(int $rating): void {
        $this->assertContains('checked="checked" class="accesshide" value="' . $rating . '">',
                $this->currentoutput);
    }

    public function test_selfassess_audio() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

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
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-selfcommentformat' => FORMAT_HTML,
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
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-selfcommentformat' => FORMAT_HTML,
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
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

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
        $this->process_submission(['-selfcomment' => '', '-selfcommentformat' => FORMAT_HTML,
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
        $this->process_submission(['-selfcomment' => '', '-selfcommentformat' => FORMAT_HTML,
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
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

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
        $this->assertContains('Please complete your answer.',
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
        $this->process_submission(['-selfcomment' => '', '-selfcommentformat' => FORMAT_HTML,
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

    public function test_selfassess_max_mark_zero_then_no_rating() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/'); // Required to output a text editor without errors.

        // Create a recordrtc question in the DB.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'interactive', 0);

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
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-selfcommentformat' => FORMAT_HTML, '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->render();
        $this->assert_does_not_contain_star_rating_ui();
        $this->assertEquals('Commented: Sounds OK',
                $this->get_qa()->summarise_action($this->get_qa()->get_last_step()));

        // Re-submitting the same self-assessment should not change the grade.
        $this->process_submission(['-selfcomment' => 'Sounds OK', '-selfcommentformat' => FORMAT_HTML, '-rate' => '1']);

        $this->check_current_state(question_state::$manfinished);
        $this->check_step_count(3);
    }
}
