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
 * Question behaviour for students to self-assess their work.
 *
 * @package    qbehaviour_selfassess
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Question behaviour for students to self-assess their work.
 *
 * The student enters their response during the attempt, and it is saved. Later,
 * when the whole attempt is finished, the attempt goes into the NEEDS_GRADING
 * state, and the teacher must grade it manually.
 */
class qbehaviour_selfassess extends question_behaviour_with_save {
    const READONLY_EXCEPT_SELFASSESS = 0x10;

    public function is_compatible_question(question_definition $question) {
        return $question instanceof question_with_responses;
    }

    public function adjust_display_options(question_display_options $options) {
        global $USER;
        $originalreadonly = $options->readonly;
        parent::adjust_display_options($options);
        if ($this->qa->get_state()->is_finished()) {
            if (!$originalreadonly && ($USER->id == $this->qa->get_step(0)->get_user_id())) {
                $options->readonly = self::READONLY_EXCEPT_SELFASSESS;
            }
        }
    }

    public function get_expected_data() {
        $expecteddata = parent::get_expected_data();

        if (!$this->qa->get_state()->is_finished()) {
            $expecteddata['submit'] = PARAM_BOOL;
        } else {
            $expecteddata['stars'] = PARAM_INT;
            $expecteddata['selfcomment'] = PARAM_RAW;
            $expecteddata['selfcommentformat'] = PARAM_INT;
            $expecteddata['rate'] = PARAM_BOOL;
        }
        return $expecteddata;
    }

    public function process_action(question_attempt_pending_step $pendingstep) {
        if ($pendingstep->has_behaviour_var('submit')) {
            return $this->process_submit($pendingstep);
        } else if ($pendingstep->has_behaviour_var('finish')) {
            return $this->process_finish($pendingstep);
        } else if ($pendingstep->has_behaviour_var('rate')) {
            return $this->process_self_assess($pendingstep);
        } else if ($pendingstep->has_behaviour_var('comment')) {
            return $this->process_comment($pendingstep);
        } else {
            return $this->process_save($pendingstep);
        }
    }

    public function process_save(question_attempt_pending_step $pendingstep) {
        $status = parent::process_save($pendingstep);
        if ($status == question_attempt::KEEP &&
                $pendingstep->get_state() == question_state::$complete) {
            $pendingstep->set_state(question_state::$todo);
        }
        return $status;
    }

    public function process_submit(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        if (!$this->is_complete_response($pendingstep)) {
            $pendingstep->set_state(question_state::$invalid);
            return question_attempt::KEEP;
        }

        return $this->process_finish($pendingstep);
    }

    public function process_finish(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        $response = $pendingstep->get_qt_data();
        if (!$this->question->is_gradable_response($response)) {
            $pendingstep->set_state(question_state::$gaveup);
        } else {
            $pendingstep->set_state(question_state::$needsgrading);
        }
        $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        return question_attempt::KEEP;
    }

    public function process_self_assess(question_attempt_pending_step $pendingstep) {
        if (!$this->qa->get_state()->is_finished()) {
            throw new coding_exception('Cannot self-assess a question before it is finished.');
        }

        if (!$pendingstep->has_behaviour_var('stars')) {
            throw new coding_exception('Cannot self-assess a question without a star rating.');
        }

        if ($this->is_same_self_assessment($pendingstep)) {
            return question_attempt::DISCARD;
        }

        $stars = $pendingstep->get_behaviour_var('stars');
        if ($stars < 0 || $stars > 5) {
            throw new coding_exception('Number of stars must be between 0 and 5 inclusive.');
        }
        $pendingstep->set_fraction($stars / 5);
        $pendingstep->set_state($this->qa->get_state()->corresponding_commented_state(
                $pendingstep->get_fraction()));
        return question_attempt::KEEP;
    }

    /**
     * Checks whether two self-assessment actions are the same.
     *
     * That is, whether the star rating, and comment (if given) are the same.
     *
     * @param question_attempt_step $pendingstep contains the new responses.
     * @return bool whether the new assessment is the same as we already have.
     */
    protected function is_same_self_assessment($pendingstep) {
        $previouscomment = $this->qa->get_last_behaviour_var('selfcomment');
        $newcomment = $pendingstep->get_behaviour_var('selfcomment');

        // When the comment empty, $previouscomment is an empty string but $newcomment is null,
        // therefore they are not equal to each other. That's why checking if
        // $previouscomment != $newcomment is not sufficent.
        if (($previouscomment != $newcomment) && !(is_null($previouscomment) && html_is_blank($newcomment))) {
            // The comment has changed.
            return false;
        }

        if (!html_is_blank($newcomment)) {
            // Check comment format.
            $previouscommentformat = $this->qa->get_last_behaviour_var('selfcommentformat');
            $newcommentformat = $pendingstep->get_behaviour_var('selfcommentformat');
            if ($previouscommentformat != $newcommentformat) {
                return false;
            }
        }

        // So, now we know the comment is the same, so check the mark, if present.
        $previousstars = $this->qa->get_last_behaviour_var('stars');
        $newstars = $pendingstep->get_behaviour_var('stars');

        return $previousstars !== $newstars;
    }

    public function summarise_action(question_attempt_step $step) {
        if ($step->has_behaviour_var('submit')) {
            return $this->summarise_submit($step);
        } else if ($step->has_behaviour_var('finish')) {
            return $this->summarise_finish($step);
        } else if ($step->has_behaviour_var('rate')) {
            return $this->summarise_self_assess($step);
        } else if ($step->has_behaviour_var('comment')) {
            return $this->summarise_manual_comment($step);
        } else {
            return $this->summarise_save($step);
        }
    }

    /**
     * Produce a text summary of a self-assessment action.
     *
     * @param question_attempt_step $step the step to summarise.
     * @return string a summary of the action performed.
     */
    protected function summarise_self_assess(question_attempt_step $step): string {
        $a = new stdClass();
        $a->stars = $step->get_behaviour_var('stars');

        $comment = $step->get_behaviour_var('selfcomment');
        if ($comment === null || $comment === '') {
            return get_string('selfassessed', 'qbehaviour_selfassess', $a);
        } else {
            $a->comment = shorten_text($comment, 200);
            return get_string('selfassessedwithcomment', 'qbehaviour_selfassess', $a);
        }
    }
}
