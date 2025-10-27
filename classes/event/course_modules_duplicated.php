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

namespace block_massaction\event;

use core\event\base;

/**
 * The course_modules_duplicated event class.
 *
 * @package     block_massaction
 * @category    event
 * @author      Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_modules_duplicated extends base {
    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event:course_modules_duplicated', 'block_massaction');
    }

    /**
     * Return localised event description.
     *
     * @return string
     */
    public function get_description(): string {
        $cms = [];
        $failed = [];
        foreach ($this->other['cms'] as $srccm => $dstcm) {
            $cms[] = get_string(
                'event:duplicated_description',
                'block_massaction',
                ['src' => $srccm,
                    'dst' => $dstcm,
                ]
            );
        }

        foreach ($this->other['failed'] as $cmid) {
            $failed[] = 'cmid \'' . $cmid . '\'';
        }

        return
            get_string(
                'event:duplicated_summary',
                'block_massaction',
                ['countcomplete' => count($cms),
                    'countfailed' => count($failed),
                ]
            )
            .
            (
            $cms ? get_string(
                'event:duplicated_completed_list',
                'block_massaction',
                ['list' => implode(', ', $cms)]
            )
                : '')
            .
            (
            $failed ? get_string(
                'event:duplicated_failed_list',
                'block_massaction',
                ['list' => implode(', ', $failed)]
            )
                : '');
    }

    /**
     * Validates the custom data.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['cms']) || !is_array($this->other['cms'])) {
            throw new \coding_exception('The \'cms\' value must be array and set in other.');
        }
        if (!isset($this->other['failed']) || !is_array($this->other['failed'])) {
            throw new \coding_exception('The \'failed\' value must be array and set in other.');
        }
    }
}
