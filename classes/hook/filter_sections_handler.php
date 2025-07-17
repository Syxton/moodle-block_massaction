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

use coding_exception;

/**
 * Trait for providing the common methods for the filter sections hooks.
 *
 * @copyright  2024 ISB Bayern
 * @author     Philipp Memmel
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait filter_sections_handler {

    /** @var array Array of section numbers which originally are available for block_massaction. */
    private readonly array $originalsectionnums;

    /**
     * Creates the hook object.
     */
    public function __construct(
            /** @var int the course id which is target for section select. */
            private readonly int $courseid,
            /** @var array $sectionnums the section numbers which are available (so the available sections the hook listeners may filter) */
            private array $sectionnums
    ) {
        $this->originalsectionnums = $this->sectionnums;
    }

    /**
     * Getter for the available sections without any changes by any hook listener.
     *
     * @return array array of section numbers which are available by block_massaction
     */
    public function get_original_sectionnums(): array {
        return $this->originalsectionnums;
    }

    /**
     * Getter for the course id the section numbers are referring to.
     *
     * You can determine if this course id belongs to the same course
     *
     * @return int
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Getter for the currently available section numbers.
     *
     * This will be evaluated by block_massaction to determine the available sections.
     *
     * @return array array of available section numbers (integers)
     */
    public function get_sectionnums(): array {
        return array_values($this->sectionnums);
    }

    /**
     * Remove a section number from the list of available/allowed section numbers.
     *
     * Does nothing if a section number is passed which is not contained in the list of currently available sections
     *
     * @param int $sectionnum The section number to remove from the list
     */
    public function remove_sectionnum(int $sectionnum): void {
        $index = array_search($sectionnum, $this->sectionnums);
        if ($index === false) {
            return;
        }
        unset($this->sectionnums[$index]);
    }
}
