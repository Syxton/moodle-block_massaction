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
        $sectionid = empty($data->sectionid) ? false : $data->sectionid;
        actions::duplicate((array) $data->modules, $sectionid);
    }
}
