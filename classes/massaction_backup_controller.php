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

namespace block_massaction;

use backup;
use backup_controller;

/**
 * A specialized controller for creating backups of multiple activities.
 *
 * @package    block_massaction
 * @copyright  2026 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class massaction_backup_controller extends backup_controller {
    /**
     * Builds a backup controller for multiple activities.
     *
     * @param integer $userid The user ID for whom the backup is being created.
     * @param array $cmids An array of course module IDs to be included in the backup.
     */
    public function __construct(int $userid, array $cmids) {
        if (empty($cmids)) {
            throw new \invalid_argument_exception('At least one cmid must be provided');
        }
        parent::__construct(
            backup::TYPE_1ACTIVITY,
            array_values($cmids)[0],
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $userid
        );
        $this->plan = new massaction_backup_plan($this);
        $this->plan = massaction_backup_plan_builder::build_multiple_activities_plan($this, $cmids);
        $this->plan->set_built();
    }
}
