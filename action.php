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

use block_massaction\actions;
use block_massaction\form\course_select_form;
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
$blockcontext = context_block::instance($instanceid);
require_capability('block/massaction:use', $blockcontext);

$data = block_massaction\massactionutils::extract_modules_from_json($massactionrequest);
$modulerecords = $data->modulerecords;

$context = $blockcontext->get_course_context();
// Dispatch the submitted action.

// Redirect to course by default.
$redirect = true;

switch ($data->action) {
    case 'moveleft':
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:indent', $blockcontext);
        block_massaction\actions::adjust_indentation($modulerecords, -1);
        break;
    case 'moveright':
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:indent', $blockcontext);
        block_massaction\actions::adjust_indentation($modulerecords, 1);
        break;
    case 'hide':
        require_capability('moodle/course:activityvisibility', $context);
        require_capability('block/massaction:activityshowhide', $blockcontext);
        block_massaction\actions::set_visibility($modulerecords, false);
        break;
    case 'show':
        require_capability('moodle/course:activityvisibility', $context);
        require_capability('block/massaction:activityshowhide', $blockcontext);
        block_massaction\actions::set_visibility($modulerecords, true);
        break;
    case 'makeavailable':
        require_capability('moodle/course:activityvisibility', $context);
        require_capability('block/massaction:activityshowhide', $blockcontext);
        if (empty($CFG->allowstealth)) {
            throw new invalid_parameter_exception('The "makeavailable" action is deactivated.');
        }
        block_massaction\actions::set_visibility($modulerecords, true, false);
        break;
    case 'duplicate':
        require_capability('moodle/backup:backuptargetimport', $context);
        require_capability('moodle/restore:restoretargetimport', $context);
        require_capability('block/massaction:duplicate', $blockcontext);
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
        require_capability('block/massaction:delete', $blockcontext);
        if (!$deletionconfirmed) {
            $redirect = false;
            block_massaction\actions::print_deletion_confirmation($modulerecords, $massactionrequest, $instanceid, $returnurl);
        } else {
            block_massaction\actions::perform_deletion($modulerecords);
        }
        break;
    case 'showdescription':
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:descriptionshowhide', $blockcontext);
        block_massaction\actions::show_description($modulerecords, true);
        break;
    case 'hidedescription':
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:descriptionshowhide', $blockcontext);
        block_massaction\actions::show_description($modulerecords, false);
        break;
    case 'contentchangednotification':
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:sendcontentchangednotifications', $blockcontext);
        block_massaction\actions::send_content_changed_notifications($modulerecords);
        break;
    case 'moveto':
        if (!isset($data->moveToTarget)) {
            throw new moodle_exception('missingparam', 'block_massaction');
        }
        require_capability('moodle/course:manageactivities', $context);
        require_capability('block/massaction:movetosection', $blockcontext);
        block_massaction\actions::perform_moveto($modulerecords, $data->moveToTarget);
        break;
    case 'duplicateto':
        if (!isset($data->duplicateToTarget)) {
            throw new moodle_exception('missingparam', 'block_massaction');
        }
        require_capability('moodle/backup:backuptargetimport', $context);
        require_capability('moodle/restore:restoretargetimport', $context);
        require_capability('block/massaction:movetosection', $blockcontext);
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
    case 'duplicatetocourse':
        $PAGE->set_context($context);
        $PAGE->set_url($CFG->wwwroot . '/blocks/massaction/action.php');
        $targetcourseid = optional_param('targetcourseid', 0, PARAM_INT);

        $options = [
            'request' => $massactionrequest,
            'instance_id' => $instanceid,
            'return_url' => $returnurl,
            'sourcecourseid' => $context->instanceid,
        ];

        $courseselectform = new course_select_form(null, $options);
        if ($courseselectform->is_cancelled()) {
            redirect($returnurl);
        }
        if (empty($targetcourseid)) {
            $redirect = false;
            actions::print_course_select_form($courseselectform);
        } else {
            $options['targetcourseid'] = $targetcourseid;

            require_capability('moodle/backup:backuptargetimport', $context);
            require_capability('moodle/restore:restoretargetimport', context_course::instance($targetcourseid));
            require_capability('block/massaction:duplicatetocourse', $blockcontext);

            $sectionselectform = new block_massaction\form\section_select_form(null, $options);
            if ($sectionselectform->is_cancelled()) {
                $redirect = false;
                // Show the course selector.
                actions::print_course_select_form($courseselectform);
                break;
            } else if ($data = $sectionselectform->get_data()) {

                // We validate the section number and default to 'same section than source course' if it is not a proper section
                // number.
                $targetsectionnum = property_exists($data, 'targetsectionnum') && is_numeric($data->targetsectionnum)
                    ? $data->targetsectionnum : -1;

                if (get_config('block_massaction', 'duplicatemaxactivities') < count($modulerecords)) {
                    $duplicatetask = new duplicate_task();
                    $duplicatetask->set_userid($USER->id);
                    $duplicatetask->set_custom_data(['modules' => $modulerecords, 'sectionnum' => $targetsectionnum,
                        'courseid' => $targetcourseid]);
                    manager::queue_adhoc_task($duplicatetask);
                    redirect($returnurl, get_string('backgroundtaskinformation', 'block_massaction'), null,
                        notification::NOTIFY_SUCCESS);
                } else {
                    block_massaction\actions::duplicate_to_course($modulerecords, $targetcourseid, $targetsectionnum);
                }

                redirect($returnurl, get_string('actionexecuted', 'block_massaction'), null,
                    notification::NOTIFY_SUCCESS);

            } else {
                $redirect = false;
                actions::print_section_select_form($sectionselectform);
            }
        }
        break;
    default:
        throw new moodle_exception('invalidaction', 'block_massaction', $data->action);
}

if ($redirect) {
    // Redirect back to the previous page.
    // If an error has occurred, the action handler functions already should have thrown an exception to the user, so if we get to
    // this point in the code, the demanded action should have been successful.
    redirect($returnurl, get_string('actionexecuted', 'block_massaction'), null,
        notification::NOTIFY_SUCCESS);
}
