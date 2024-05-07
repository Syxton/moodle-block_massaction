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

defined('MOODLE_INTERNAL') || die();

#[\core\attribute\label('Hook dispatched when block_massaction is duplicating activities into another course. '
    . 'The hook provides ways to customize which sections the user can duplicate activities to.')]
#[\core\attribute\tags('block_massaction')]
/**
 * Hook class for filtering a list of target sections when duplicating into another course.
 *
 * @copyright  2024 ISB Bayern
 * @author     Philipp Memmel
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_sections_different_course {

    use filter_sections_handler;

    /** @var bool Determines if the user will be able to keep the original section of a course module when performing some operations. */
    private bool $originsectionkept = true;

    /** @var bool Determines if the user will be able to create a new section when performing some operations. */
    private bool $makesectionallowed = true;

    /**
     * Disables the option to keep the original section of a course module.
     */
    public function disable_originsectionkept(): void {
        $this->originsectionkept = false;
    }

    /**
     * Returns if the option to keep the course modules in the original section when duplicating or not.
     *
     * This information is only used in the case that the target course is different from the one that contains the course modules.
     *
     * @return bool if the user will be allowed to keep the original section of the course modules
     */
    public function is_originsectionkept(): bool {
        return $this->originsectionkept;
    }

    /**
     * Disables the option to create a new section.
     */
    public function disable_makesection(): void {
        $this->makesectionallowed = false;
    }

    /**
     * Returns if the option to create a new section is allowed or not.
     *
     * This information is only used in the case that the target course is different from the one that contains the course modules.
     *
     * @return bool if the user will be allowed to create a new section
     */
    public function is_makesectionallowed(): bool {
        return $this->makesectionallowed;
    }
}
