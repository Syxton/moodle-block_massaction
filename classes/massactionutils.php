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
 * massactionutils class: Utility class providing methods for generating data used by the massaction block.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_massaction;

use backup;
use backup_controller;
use base_plan_exception;
use base_setting_exception;
use coding_exception;
use context_module;
use dml_exception;
use moodle_exception;
use restore_controller;
use restore_controller_exception;
use stdClass;

/**
 * Mass action utility functions class.
 *
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class massactionutils {

    /**
     * Method to extract the modules from the request JSON which is sent by the block_massaction JS module to the backend.
     *
     * @param string $massactionrequest the json string containing the module ids to be handled as well as the action
     *  which should be applied to them
     * @return stdClass the data structure converted from the json
     * @throws dml_exception if the database lookup fails
     * @throws moodle_exception if the json is of a wrong format
     */
    public static function extract_modules_from_json(string $massactionrequest): stdClass {
        global $DB;
        // Parse the submitted data.
        $data = json_decode($massactionrequest);

        // Verify that the submitted module IDs do belong to the course.
        if (!property_exists($data, 'moduleIds') || !is_array($data->moduleIds) || count($data->moduleIds) == 0) {
            throw new moodle_exception('jsonerror', 'block_massaction');
        }

        $modulerecords = $DB->get_records_select('course_modules',
            'id IN (' . implode(',', array_fill(0, count($data->moduleIds), '?')) . ')',
            $data->moduleIds);

        foreach ($data->moduleIds as $modid) {
            if (!isset($modulerecords[$modid])) {
                throw new moodle_exception('invalidmoduleid', 'block_massaction', $modid);
            }
        }

        if (!isset($data->action)) {
            throw new moodle_exception('noaction', 'block_massaction');
        }
        $data->modulerecords = $modulerecords;
        return $data;
    }

    /**
     * This duplicates a course module to a *different* course.
     *
     * This function is mainly copied from 'duplicate_module' from /course/lib.php. Unfortunately, it seems that this function
     * was once intended to also be able to duplicate a module to another course, but mid-function it started to be specific to
     * the course the source module is part of.
     *
     * @param object $course course object.
     * @param object $cm course module object to be duplicated.
     * @return int id of the duplicated course module
     * @throws base_plan_exception
     * @throws base_setting_exception
     * @throws coding_exception
     * @throws restore_controller_exception
     * @throws moodle_exception
     */
    public static function duplicate_cm_to_course(object $course, object $cm): int {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->libdir . '/filelib.php');

        $a = new stdClass();
        $a->modtype = get_string('modulename', $cm->modname);
        $a->modname = format_string($cm->name);

        if (!plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {
            throw new moodle_exception('duplicatenosupport', 'error', '', $a);
        }

        // Backup the activity.

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();

        $bc->destroy();

        // Restore the backup immediately.
        $rc = new restore_controller($backupid, $course->id,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

        // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
        $plan = $rc->get_plan();
        $groupsetting = $plan->get_setting('groups');
        if (empty($groupsetting->get_value())) {
            $groupsetting->set_value(true);
        }

        $cmcontext = context_module::instance($cm->id);
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }

        $rc->execute_plan();

        // Now a bit hacky part follows - we try to get the cmid of the newly
        // restored copy of the module.
        $newcmid = null;
        $tasks = $rc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
        }
        return $newcmid;
    }
}
