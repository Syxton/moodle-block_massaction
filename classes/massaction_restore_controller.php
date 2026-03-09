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
use restore_controller;

/**
 * Class massaction_restore_controller
 *
 * @package    block_massaction
 * @copyright  2026 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class massaction_restore_controller extends restore_controller {
    /**
     * Constructs a massaction_restore_controller instance.
     *
     * @param string $tempdir The temporary directory where the backup file is located.
     * @param integer $courseid The ID of the course into which the backup will be restored.
     * @param integer $userid The user ID for whom the restore is being performed.
     */
    public function __construct(string $tempdir, int $courseid, int $userid) {
        parent::__construct(
            $tempdir,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $userid,
            backup::TARGET_CURRENT_ADDING
        );
        $cmids = array_keys($this->get_info()->activities);
        $this->progress = new \core\progress\none();
        $this->progress->start_progress('Constructing restore_controller');
        $this->plan = new massaction_restore_plan($this);
        $this->plan = massaction_restore_plan_builder::build_multiple_activities_plan($this, $cmids);
        $this->plan->set_built();
        $this->progress->end_progress();
    }
}
