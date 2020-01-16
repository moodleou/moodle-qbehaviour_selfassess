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
    public function test_selfassess_audio() {
        $this->resetAfterTest();
        $this->setAdminUser();

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

        // Process a response and check the expected result.
        $response = $this->store_submission_file('moodle-tim.ogg');
        $response['-submit'] = '1';
        $this->process_submission($response);

        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        // TODO check current output.
        $this->save_quba();

        // Now manually grade
        $this->process_submission(['-manualcomment' => 'Sounds OK', '-stars' => '4', '-save' => '1']);

        $this->check_current_state(question_state::$mangrpartial);
        $this->check_current_mark(4);
        $this->check_step_count(3);
        // TODO check current output.
        $this->save_quba();
    }
}
