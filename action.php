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
 * Configures and displays the block.
 *
 * @package    block_massaction
 * @copyright  2011 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_massaction\task\duplicate_task;
use core\output\notification;
use core\task\manager;

require('../../config.php');

$instanceid = required_param('instance_id', PARAM_INT);
$massactionrequest = required_param('request', PARAM_TEXT);
$returnurl = required_param('return_url', PARAM_TEXT);
$deletionconfirmed = optional_param('del_confirm', 0, PARAM_BOOL);

require_login();

// Check capability.
$context = context_block::instance($instanceid);
require_capability('block/massaction:use', $context);

$data = block_massaction\massactionutils::extract_modules_from_json($massactionrequest);
$modulerecords = $data->modulerecords;

$context = $context->get_course_context();
// Dispatch the submitted action.
switch ($data->action) {
    case 'moveleft':
        require_capability('moodle/course:manageactivities', $context);
        block_massaction\actions::adjust_indentation($modulerecords, -1);
        break;
    case 'moveright':
        require_capability('moodle/course:manageactivities', $context);
        block_massaction\actions::adjust_indentation($modulerecords, 1);
        break;
    case 'hide':
        require_capability('moodle/course:activityvisibility', $context);
        block_massaction\actions::set_visibility($modulerecords, false);
        break;
    case 'show':
        require_capability('moodle/course:activityvisibility', $context);
        block_massaction\actions::set_visibility($modulerecords, true);
        break;
    case 'makeavailable':
        require_capability('moodle/course:activityvisibility', $context);
        if (empty($CFG->allowstealth)) {
            throw new invalid_parameter_exception('The "makeavailable" action is deactivated.');
        }
        block_massaction\actions::set_visibility($modulerecords, true, false);
        break;
    case 'duplicate':
        require_capability('moodle/backup:backuptargetimport', $context);
        require_capability('moodle/restore:restoretargetimport', $context);
        if (get_config('block_massaction', 'duplicatemaxactivities') < count($modulerecords)) {
            $duplicatetask = new duplicate_task();
            $duplicatetask->set_userid($USER->id);
            $duplicatetask->set_custom_data(['modules' => $modulerecords]);
            manager::queue_adhoc_task($duplicatetask);
            redirect($returnurl, get_string('backgroundtaskinformation', 'block_massaction'), null,
                notification::NOTIFY_SUCCESS);
        } else {
            block_massaction\actions::duplicate($modulerecords);
        }
        break;
    case 'delete':
        require_capability('moodle/course:manageactivities', $context);
        if (!$deletionconfirmed) {
            block_massaction\actions::print_deletion_confirmation($modulerecords, $massactionrequest, $instanceid, $returnurl);
        } else {
            block_massaction\actions::perform_deletion($modulerecords);
        }
        break;
    case 'moveto':
        if (!isset($data->moveToTarget)) {
            throw new moodle_exception('missingparam', 'block_massaction');
        }
        require_capability('moodle/course:manageactivities', $context);
        block_massaction\actions::perform_moveto($modulerecords, $data->moveToTarget);
        break;
    case 'duplicateto':
        if (!isset($data->duplicateToTarget)) {
            throw new moodle_exception('missingparam', 'block_massaction');
        }
        require_capability('moodle/backup:backuptargetimport', $context);
        require_capability('moodle/restore:restoretargetimport', $context);
        if (get_config('block_massaction', 'duplicatemaxactivities') < count($modulerecords)) {
            $duplicatetask = new duplicate_task();
            $duplicatetask->set_userid($USER->id);
            $duplicatetask->set_custom_data(['modules' => $modulerecords, 'sectionid' => $data->duplicateToTarget]);
            manager::queue_adhoc_task($duplicatetask);
            redirect($returnurl, get_string('backgroundtaskinformation', 'block_massaction'), null,
                notification::NOTIFY_SUCCESS);
        } else {
            block_massaction\actions::duplicate($modulerecords, $data->duplicateToTarget);
        }
        break;
    default:
        throw new moodle_exception('invalidaction', 'block_massaction', $data->action);
}

if ($data->action !== "delete" || $deletionconfirmed) {
    // Redirect back to the previous page.
    redirect($returnurl);
}
