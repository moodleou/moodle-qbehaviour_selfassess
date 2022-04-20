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

/**
 * Question that want to work with self-assess behaviour should use this trait in their question class.
 *
 * It declares the two fields that must exist.
 */
trait question_with_self_assessment {

    /**
     * @var bool whether the students can self rate their own response.
     */
    public $canselfrate = 0;

    /**
     * @var bool whether the students can self comment on their own response.
     */
    public $canselfcomment = 0;
}
