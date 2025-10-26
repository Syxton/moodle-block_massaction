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
 * The course_modules_duplicated_failed event class.
 *
 * @package     block_massaction
 * @category    event
 * @author      Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_modules_duplicated_failed extends base {
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
        return get_string('event:course_modules_duplicated_failed', 'block_massaction');
    }

    /**
     * Return localised event description.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string(
            'event:duplicated_failed_description',
            'block_massaction',
            ['cmid' => $this->other['cmid'],
                'error' => $this->other['error'],
            ]
        );
    }

    /**
     * Validates the custom data.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['cmid'])) {
            throw new \coding_exception('The \'cmid\' value must be set in other.');
        }
        if (!isset($this->other['error'])) {
            throw new \coding_exception('The \'error\' value must be set in other.');
        }
    }
}
