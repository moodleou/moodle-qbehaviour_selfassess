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
 * Defines the renderer for the self-assessment behaviour.
 *
 * @package    qbehaviour_selfassess
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Renderer for outputting parts of a question belonging to the self-assessment behaviour.
 */
class qbehaviour_selfassess_renderer extends qbehaviour_renderer {
    public function controls(question_attempt $qa, question_display_options $options) {
        return $this->submit_button($qa, $options);
    }

    public function feedback(question_attempt $qa, question_display_options $options) {
        if (!$qa->get_state()->is_finished()) {
            return '';
        }

        $output = '';
        $output .= html_writer::start_div('self-assessment');
        if ($options->readonly === qbehaviour_selfassess::READONLY_EXCEPT_SELFASSESS) {
            $output .= $this->self_assessment_editable($qa, $options);
        } else {
            $output .= $this->self_assessment_read_only($qa, $options);
        }

        $output .= html_writer::end_div();
        return $output;
    }

    public function self_assessment_editable(question_attempt $qa, question_display_options $options) {
        $output = '';

        // Select menu for stars. TODO improve this.
        $starchoices = [
            "\u{2606}\u{2606}\u{2606}\u{2606}\u{2606}",
            "\u{2605}\u{2606}\u{2606}\u{2606}\u{2606}",
            "\u{2605}\u{2605}\u{2606}\u{2606}\u{2606}",
            "\u{2605}\u{2605}\u{2605}\u{2606}\u{2606}",
            "\u{2605}\u{2605}\u{2605}\u{2605}\u{2606}",
            "\u{2605}\u{2605}\u{2605}\u{2605}\u{2605}",
        ];
        $stars = $qa->get_last_behaviour_var('stars');
        $name = $qa->get_behaviour_field_name('stars');
        $output .= html_writer::div(
                html_writer::tag('label', get_string('rateyourself', 'qbehaviour_selfassess'), ['for' => $name]) .
                $this->help_icon('rateyourself', 'qbehaviour_selfassess') .
                html_writer::select($starchoices, $name, $stars, ['' => 'choosedots'], ['id' => $name]),
                'self-assessment');

        // Editor for the comment.
        $inputname = $qa->get_behaviour_field_name('selfcomment');
        $id = $inputname . '_id';
        list($comment, $commentformat) = $this->get_last_self_comment($qa);

        $editor = editors_get_preferred_editor($commentformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        $editor->set_text($comment);
        $editor->use_editor($id, question_utils::get_editor_options($options->context));

        $commenteditor = html_writer::tag('div', html_writer::tag('textarea', s($comment),
                array('id' => $id, 'name' => $inputname, 'rows' => 5, 'cols' => 60)));

        $editorformat = '';
        if (count($formats) == 1) {
            reset($formats);
            $editorformat .= html_writer::empty_tag('input', ['type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => key($formats)]);
        } else {
            $editorformat = html_writer::start_tag('div', ['class' => 'fitem']);
            $editorformat .= html_writer::start_tag('div', ['class' => 'fitemtitle']);
            $editorformat .= html_writer::tag('label', get_string('format'), ['for' => 'menu' . $inputname . 'format']);
            $editorformat .= html_writer::end_tag('div');
            $editorformat .= html_writer::start_tag('div', ['class' => 'felement fhtmleditor']);
            $editorformat .= html_writer::select($formats, $inputname.'format', $commentformat, '');
            $editorformat .= html_writer::end_tag('div');
            $editorformat .= html_writer::end_tag('div');
        }

        $output .= html_writer::tag('div', html_writer::tag('div',
                        html_writer::tag('label', get_string('comment', 'question'),
                                array('for' => $id)), array('class' => 'fitemtitle')) .
                html_writer::tag('div', $commenteditor, array('class' => 'felement fhtmleditor', 'data-fieldtype' => "editor")),
                array('class' => 'fitem'));
        $output .= $editorformat;

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
        return $output;
    }

    public function self_assessment_read_only(question_attempt $qa, question_display_options $options) {
        $output = '';

        $stars = $qa->get_last_behaviour_var('stars');
        $output .= html_writer::div(get_string('selfassessment', 'qbehaviour_selfassess',
                str_repeat("\u{2605}", $stars) . str_repeat("\u{2606}", $stars)),
                'self-assessment');

        list($comment, $commentformat) = $this->get_last_self_comment($qa);
        if ($comment !== null) {
            $output .= html_writer::div(get_string('commentx', 'question',
                    format_text($comment, $commentformat, ['context' => $options->context])),
                    'self-comment');
        }

        return $output;
    }

    /**
     * Get the most recent self-comment (and format) if any.
     *
     * @param question_attempt $qa the attempt to get the comment from.
     * @return array comment and format, or null, null if there is not comment.
     */
    protected function get_last_self_comment(question_attempt $qa) {
        $comment = [null, null];
        foreach ($qa->get_reverse_step_iterator() as $step) {
            if ($step->has_behaviour_var('selfcomment')) {
                $comment = [$step->get_behaviour_var('selfcomment'),
                        $step->get_behaviour_var('selfcommentformat')];
                break;
            }
        }

        if (html_is_blank($comment[0])) {
            return [null, null];
        }

        return $comment;
    }
}
