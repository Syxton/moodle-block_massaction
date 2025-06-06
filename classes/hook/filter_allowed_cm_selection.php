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

use core\hook\described_hook;

/**
 * Hook to allow filtering of allowed course module IDs for mass action.
 *
 * @copyright  2025 Monash University
 * @author     Trisha Milan <trishamilan@catalyst-au.net>
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filter_allowed_cm_selection implements described_hook {

    /**
     * Constructor for the filter_allowed_cm_selection hook.
     *
     * @param int $courseid The course ID where the mass action is being performed.
     * @param array $cmids An array of course module IDs initially eligible for selection.
     *                     This can be modified by hook listeners to apply restrictions.
     */
    public function __construct(
        public readonly int $courseid,
        public array $cmids
    ) {
    }

    /**
     * Describes the hook purpose.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Hook to restrict course module selection in block_massaction.';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return array
     */
    public static function get_hook_tags(): array {
        return ['block_massaction'];
    }
}
