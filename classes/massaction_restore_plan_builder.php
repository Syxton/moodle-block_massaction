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

use restore_plan_builder;
use restore_root_task;
use restore_final_task;
/**
 * A specialized restore plan builder that provides a method to build a plan for restoring multiple activities.
 *
 * @package    block_massaction
 * @copyright  2026 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class massaction_restore_plan_builder extends restore_plan_builder {
    /**
     * Builds a plan that restores a backup of multiple activities.
     *
     * @param massaction_restore_controller $controller The restore controller for which the plan is being built.
     * @param array $cmids An array of course module IDs that should be restored.
     * @return massaction_restore_plan
     */
    public static function build_multiple_activities_plan(
        massaction_restore_controller $controller,
        array $cmids
    ): massaction_restore_plan {
        if (empty($cmids)) {
            throw new \invalid_argument_exception('At least one cmid must be provided');
        }

        $plan = $controller->get_plan();

        $plan->add_task(new restore_root_task('root_task'));
        $controller->get_progress()->progress();

        foreach ($cmids as $cmid) {
            self::build_activity_plan($controller, $cmid);
        }

        $plan->add_task(new restore_final_task('final_task'));
        $controller->get_progress()->progress();

        $plan->set_built();

        return $plan;
    }
}
