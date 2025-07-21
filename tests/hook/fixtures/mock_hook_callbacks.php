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

namespace block_massaction\hook\fixtures;

use block_massaction\hook\filter_allowed_cm_selection;

/**
 * Callbacks used to test the hooks.
 *
 * @copyright  2025 Monash University
 * @author     Trisha Milan <trishamilan@catalyst-au.net>
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_hook_callbacks {

    /**
     * Callback used to test the filter_allowed_cm_selection hook.
     *
     * @param filter_allowed_cm_selection $hook
     */
    public static function filter_allowed_cm_selection(filter_allowed_cm_selection $hook) {
        $modinfo = get_fast_modinfo($hook->courseid);
        $filtered = [];

        foreach ($hook->cmids as $cmid) {
            $cm = $modinfo->cms[$cmid];
            // Block page activities from being allowed.
            if ($cm->modname !== 'page') {
                $filtered[] = $cmid;
            }
        }
        $hook->cmids = $filtered;
    }
}
