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
 * duplicate_task class: Adhoc task to process duplicating course modules from the block_massaction plugin.
 *
 * @package    block_massaction
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_massaction\task;

use block_massaction\actions;
use core\task\adhoc_task;
use moodle_exception;
use require_login_exception;
use restore_controller_exception;

/**
 * Duplicate task class.
 *
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_task extends adhoc_task {

    /**
     * Executes the duplication of multiple course modules.
     *
     * @throws require_login_exception
     * @throws restore_controller_exception if any of the duplication fails
     * @throws moodle_exception
     */
    public function execute() {
        $data = $this->get_custom_data();
        // We use sectionid for duplicating modules in the same course, but sectionnum for duplicating to another course.
        if (!empty($data->courseid)) {
            // If a courseid has been set we are duplicating to another course.
            $sectionnum = -1;
            if (property_exists($data, 'sectionnum')) {
                // If no sectionnum has been specified, we default to -1 which means course modules will be restored to the same
                // section they have in the source course.
                $sectionnum = $data->sectionnum;
            }
            try {
                actions::duplicate_to_course((array) $data->modules, $data->courseid, $sectionnum);
            } catch (moodle_exception $e) {
                // Add the error to log and complete the task.
                mtrace($e->getMessage());
            }
        } else {
            // If no courseid has been set, we just duplicate in the same course.
            $sectionid = empty($data->sectionid) ? false : $data->sectionid;
            try {
                actions::duplicate((array) $data->modules, $sectionid);
            } catch (moodle_exception $e) {
                // Add the error to log and complete the task.
                mtrace($e->getMessage());
            }
        }
    }
}
