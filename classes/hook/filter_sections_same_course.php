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

namespace block_massaction\hook;

#[\core\attribute\label('Hook dispatched when block_massaction is duplicating or moving activities inside a course. '
        . 'The hook provides ways to customize which sections the user can duplicate/move activities to.')]
#[\core\attribute\tags('block_massaction')]
/**
 * Hook class for filtering a list of target sections when duplicating/moving inside a course.
 *
 * @copyright  2024 ISB Bayern
 * @author     Philipp Memmel
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_sections_same_course {
    // We use the trait here, because inheritance is not recommended for hooks.
    use filter_sections_handler;
}
