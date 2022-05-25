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

use qbehaviour_selfassess\question_with_self_assessment;

/**
 * Renderer for outputting parts of a question belonging to the self-assessment behaviour.
 *
 * @package    qbehaviour_selfassess
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_selfassess_renderer extends qbehaviour_renderer {

    /**
     * The number of stars to be displayed for rating.
     * The displayed stars are internally numbered as 1 to n(this constant) from left to right.
     */
    const MAX_NUMBER_OF_STARS = 5;

    public function controls(question_attempt $qa, question_display_options $options): string {
        $output = $this->submit_button($qa, $options);

        // Bit of a hack to get the core button, with all the required setup, but just change the label.
        $output = str_replace(html_writer::attribute('value', get_string('check', 'question')),
                html_writer::attribute('value', get_string('saveandfeedback', 'qbehaviour_selfassess')), $output);

        return $output;
    }

    public function feedback(question_attempt $qa, question_display_options $options): string {
        if (!$qa->get_state()->is_finished()) {
            return '';
        }

        $output = '';
        if ($options->readonly === qbehaviour_selfassess::READONLY_EXCEPT_SELFASSESS) {
            $output .= $this->self_assessment_editable($qa, $options);
        } else {
            $output .= $this->self_assessment_read_only($qa, $options);
        }

        return html_writer::nonempty_tag('div', $output, ['class' => 'self-assessment']);
    }

    /**
     * Render the self-assessment UI.
     *
     * @param question_attempt $qa the question attempt being rendered.
     * @param question_display_options $options the display options.
     * @return string HTML.
     */
    public function self_assessment_editable(question_attempt $qa, question_display_options $options): string {
        $output = '';
        /** @var question_with_self_assessment $question */
        $question = $qa->get_question();

        if ($question->canselfrate) {
            $stars = $qa->get_last_behaviour_var('stars');
            $name = $qa->get_behaviour_field_name('stars');
            $starratinghtml = $this->star_rating_select($qa, $name, (int) $stars);
            $output .= html_writer::div(
                    html_writer::tag('label', get_string('rateyourself', 'qbehaviour_selfassess'), ['for' => $name]) .
                    ' ' . $this->help_icon('rateyourself', 'qbehaviour_selfassess') . ' ' .
                    html_writer::tag('span', $starratinghtml, ['class' => 'rating']), 'self-assessment-rating');
        }

        // Editor for the comment.
        if ($question->canselfcomment) {
            list($comment) = $this->get_last_self_comment($qa);
            $inputname = $qa->get_behaviour_field_name('selfcomment');

            $output .= html_writer::div(
                    html_writer::tag('label', get_string('comment', 'question'), ['for' => $inputname]) .
                    ' ' . html_writer::tag('textarea', s($comment),
                            ['id' => $inputname, 'name' => $inputname, 'rows' => 2, 'cols' => 60]),
                    'self-assess-comment');
        }

        if ($question->canselfrate || $question->canselfcomment) {
            // Save button.
            $attributes = [
                'type' => 'submit',
                'id' => $qa->get_behaviour_field_name('Save'),
                'name' => $qa->get_behaviour_field_name('rate'),
                'value' => get_string('save'),
                'class' => 'submit btn btn-secondary',
            ];
            $output .= html_writer::empty_tag('input', $attributes);

            $this->page->requires->js_init_call('M.core_question_engine.init_submit_button',
                    array($attributes['id'], $qa->get_slot()));
        }

        return $output;
    }

    /**
     * Render a read-only view of the self-assessment UI.
     *
     * @param question_attempt $qa the question attempt being rendered.
     * @param question_display_options $options the display options.
     * @return string HTML.
     */
    public function self_assessment_read_only(question_attempt $qa, question_display_options $options): string {
        $output = '';
        /** @var question_with_self_assessment $question */
        $question = $qa->get_question();

        if ($question->canselfrate) {
            $stars = $qa->get_last_behaviour_var('stars');
            $output .= html_writer::div(get_string('selfassessment', 'qbehaviour_selfassess',
                    str_repeat("\u{2605}", $stars) . str_repeat("\u{2606}", self::MAX_NUMBER_OF_STARS - $stars)),
                    'self-assessment');
        }

        if ($question->canselfcomment) {
            list($comment, $commentformat) = $this->get_last_self_comment($qa);
            if ($comment !== null) {
                $output .= html_writer::div(get_string('commentx', 'question',
                        format_text($comment, $commentformat, ['context' => $options->context])),
                        'self-comment');
            }
        }

        return $output;
    }

    /**
     * Get the most recent self-comment (and format) if any.
     *
     * @param question_attempt $qa the attempt to get the comment from.
     * @return array comment and format, or null, null if there is no comment.
     */
    protected function get_last_self_comment(question_attempt $qa): array {
        $comment = [null, null];
        foreach ($qa->get_reverse_step_iterator() as $step) {
            if ($step->has_behaviour_var('selfcomment')) {
                $comment = [$step->get_behaviour_var('selfcomment'), FORMAT_MOODLE];
                break;
            }
        }

        if (html_is_blank($comment[0])) {
            return [null, null];
        }

        return $comment;
    }

    /**
     * Return HTML structure (hidden radio button input fields labeled with appropriate icons as rating stars).
     *
     * @param question_attempt $qa the attempt to get star rating.
     * @param string $name unique name for the question on the page
     * @param int $currentstars last/current rating number
     * @return string html structure
     */
    protected function star_rating_select(question_attempt $qa, string $name, int $currentstars): string {
        $output = '';
        for ($i = 0; $i < self::MAX_NUMBER_OF_STARS + 1; $i++) {
            $rated = get_string('rated', 'qbehaviour_selfassess', $i);
            if ($i > 0) {
                $starempty = $this->pix_icon('starempty', $rated, 'qbehaviour_selfassess', ['class' => 'rated']);
                $starfilled = $this->pix_icon('starfilled', $rated, 'qbehaviour_selfassess', ['class' => 'rated']);
                $output .= "<label for=\"$name-$i\">
                        <span class=\"empty\" tabindex=\"0\">$starempty</span>
                        <span class=\"filled\" tabindex=\"0\">$starfilled</span>
                    </label>";
            }
            $checked = '';
            if ($i == $currentstars) {
                $checked = 'checked="checked"';
            }
            $output .= "<input id=\"$name-$i\" type=\"radio\" name=\"$name\" $checked class=\"accesshide\" value=\"$i\">";
        }
        // Add a button for clear rating after the stars to be displayed in the same line.
        $output .= '<input type="button" name="clearbutton" value="' .
                get_string('clear') . '" class="clearrating">';
        $this->page->requires->js_call_amd('qbehaviour_selfassess/rating', 'init',
                [$qa->get_outer_question_div_unique_id()]);
        return $output;
    }
}
