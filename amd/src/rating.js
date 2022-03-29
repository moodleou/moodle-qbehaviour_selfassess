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
//

/**
 * JavaScript for rating.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/key_codes'], function(keys) {
    /**
     * Rating object.
     * @param {Element} questionDiv
     * @constructor
     */
    function Rating(questionDiv) {
        let clearButton = questionDiv.querySelector('.self-assessment-rating input.clearrating');
        clearButton.addEventListener('click', handleButtonClick);
        clearButton.addEventListener('keydown', handleButtonKeyPress);

        let radios = questionDiv.querySelectorAll('.self-assessment-rating input[type=radio]');
        let stars = questionDiv.querySelectorAll('.self-assessment-rating label');
        stars.forEach(function(star, index) {
            star.addEventListener('keydown', function(e) {
                switch (e.keyCode) {
                    case keys.enter:
                    case keys.space:
                        e.preventDefault();
                        // Using index+1, because the radio button starting from 0 and labels (stars) starting with 1.
                        // Reverse the boolean value as a shortcat instead of an if-statement.
                        radios[index + 1].checked = !radios[index + 1].checked;
                        return;

                    default:
                        return;
                }
            });
        });

        /**
         * Handles clicks on the clear button.
         *
         * @param {Event} e
         */
        function handleButtonClick(e) {
            e.preventDefault();
            clearRating(questionDiv);
            clearButton.blur();
        }

        /**
         * Handles keydown on the clear button.
         *
         * @param {KeyboardEvent} e
         */
        function handleButtonKeyPress(e) {
            switch (e.keyCode) {
                case keys.enter:
                case keys.space:
                    e.preventDefault();
                    clearRating(questionDiv);
                    clearButton.blur();
                    return;

                default:
                    return;
            }
        }

        /**
         * Clear rating and set the rating to not rated.
         *
         * @param {Element} questionDiv
         */
        function clearRating(questionDiv) {
            let radios = questionDiv.querySelectorAll('.self-assessment-rating input[type=radio]');
            radios.forEach(function(radio, index) {
                if (index === 0) {
                    radio.checked = true;
                } else if (radio.checked === true) {
                    radio.checked = false;
                }
            });
        }
    }

    return {
        /**
         * Initialise star rating for self-assess question behaviour.
         *
         * @param {string} questionId id of the outer question div
         */
        init: function(questionId) {
            M.util.js_pending('rating-' + questionId);
            let questionDiv = document.getElementById(questionId);
            new Rating(questionDiv);
            M.util.js_complete('rating-' + questionId);
        }
    };
});
