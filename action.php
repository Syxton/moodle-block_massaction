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
 * Processes Mass Actions submissions
 *
 * @package    block_massaction
 * @copyright  2011 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

// Parameters/arguments.
$instanceid     = required_param('instanceid', PARAM_INT);
$returnurl      = required_param('returnurl', PARAM_TEXT);
$action         = required_param('action', PARAM_TEXT);
$activities     = required_param('activities', PARAM_RAW);
$courseid       = required_param('courseid', PARAM_INT);

$courseformat   = optional_param('format', 'topics', PARAM_TEXT);
$selectedall    = optional_param('selectedall', false, PARAM_BOOL);
$section        = optional_param('selectedsection', 'all', PARAM_TEXT);
$target         = optional_param('target', 0, PARAM_INT);
$confirmdelete  = optional_param('confirmdelete', false, PARAM_BOOL);

// Check capability.
$context = context_block::instance($instanceid);
$PAGE->set_context($context);
require_capability('block/massaction:use', $context);

$activities = json_decode($activities);
$numberofactivities = count($activities);

// Ensure we have an array of module ids and that we have at least one id.
if (!is_array($activities) || $numberofactivities < 1) {
    print_error('missingparam', 'block_massaction', 'Module ID');
}

$modulerecords = $DB->get_records_select('course_modules', 'ID IN (' .
                                          implode(',', array_fill(0, $numberofactivities, '?'))
                                          . ')', $activities);

// Keep track of courses to rebuild cache.
$rebuildcourses = array();

foreach ($activities as $activityid) {
    if (!isset($modulerecords[$activityid])) {
        print_error('invalidmoduleid', 'block_massaction', $activityid);
    } else {
        if (!array_key_exists($modulerecords[$activityid]->course, $rebuildcourses)) {
            $rebuildcourses[$modulerecords[$activityid]->course] = true;
        }
    }
}

if (!isset($action)) {
    print_error('noaction', 'block_massaction');
}

// Dispatch the submitted action.
switch ($action) {
    case 'outdent':
    case 'indent':
        adjust_indentation($modulerecords, $action == 'outdent' ? -1 : 1, $context);
        break;

    case 'hide1':
    case 'hide2':
    case 'show':
        set_visibility($modulerecords, $action, $context);
        break;

    case 'delete':
        if ($confirmdelete) {
            perform_deletion($modulerecords, $returnurl);
        } else {
            display_delete_confirmation_page($modulerecords, $activities, $instanceid, $courseid, $returnurl);
        }
        break;

    case 'move':
        if (!isset($target)) {
            print_error('missingparam', 'block_massaction', 'target');
        } else {
            move_module($modulerecords, $target);
        }
        break;

    case 'clone':
        if (!isset($target)) {
            print_error('missingparam', 'block_massaction', 'target');
        } else {
            clone_module($modulerecords, $target);
        }
        break;

    default:
        print_error('invalidaction', 'block_massaction', $data->action);
        break;
}

// Rebuild course cache.
foreach ($rebuildcourses as $courseid => $nada) {
    rebuild_course_cache($courseid);
}

/*
 * If we're doing anything other than attempting to delete activities, then redirecting here is
 * appropriate. This is because if we redirect before we rebuild the course cache, then some of
 * our actions (particularly indent/outdent) may not take effect or be reflected on the page.
 *
 * If we are trying to delete, then redirecting here is not appropriate because trying to do so
 * throws errors on the confirmation page. Instead, we need to redirect after we've actually
 * deleted the selected item(s).
 */
if ($action != 'delete') {
    redirect($returnurl);
}

/**
 * helper function to perform indentation/outdentation
 *
 * @param array  $modules list of module records to modify
 * @param int    $amount 1 for indent, -1 for outdent
 * @param object $context
 *
 * @return void
 */
function adjust_indentation($modules, $amount, $context) {
    global $DB;

    require_capability('moodle/course:manageactivities', $context);

    foreach ($modules as $cm) {
        $cm->indent += $amount;

        if ($cm->indent < 0) {
            $cm->indent = 0;
        }

        $DB->set_field('course_modules', 'indent', $cm->indent, array('id' => $cm->id));
    }
}

/**
 * helper function to set visibility
 *
 * @param array  $modules list of module records to modify
 * @param bool   $visible true to show, false to hide
 * @param object $context
 *
 * @return void
 */
function set_visibility($modules, $action, $context) {
    global $CFG;

    $avail = 0;
    $visible = 0;
    if ($action != 'show') {
        if ($action == 'hide2') {
            $avail = 1;
            $visible = 0;
        }
    } else {
        $avail = 1;
        $visible = 1;
    }

    require_once($CFG->dirroot.'/course/lib.php');

    foreach ($modules as $cm) {
        set_coursemodule_visible($cm->id, $avail, $visible);
    }
}

/**
 * Displays the deletion confirmation page.
 *
 * @param array  $modulerecords Array of module records
 * @param array  $activities Array of selected modules/activities
 * @param int    $instanceid Instance id
 * @param int    $courseid Course id
 * @param string $returnurl The url to which to redirect
 *
 * @return void
 */
function display_delete_confirmation_page($modulerecords, $activities, $instanceid, $courseid, $returnurl) {
    global $CFG, $OUTPUT, $PAGE;

    $modules = array();

    foreach ($modulerecords as $modulerecord) {
        $cm = validate_module($modulerecord->id);
        $course = validate_course($cm->course);

        $context = context_course::instance($course->id);
        require_capability('moodle/course:manageactivities', $context);

        $moduletype = get_string('modulename', $cm->modname);
        $modules[$modulerecord->id] = array('moduletype' => $moduletype, 'modulename' => $cm->name);
    }

    $continueoptions = array('confirmdelete' => true, 'instanceid' => $instanceid,
                                   'action' => 'delete', 'courseid' => $courseid,
                                   'returnurl' => $returnurl, 'activities' => json_encode($activities));

    $canceloptions = array('id' => $course->id);
    $strconfirmdeletion = get_string('confirmdeletiontitle', 'block_massaction');

    require_login($course->id);

    $PAGE->requires->css('/blocks/massaction/styles.css');
    $PAGE->set_url(new moodle_url('/blocks/massaction/action.php'));
    $PAGE->set_title($strconfirmdeletion);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strconfirmdeletion);
    echo $OUTPUT->header();

    $content = get_string('confirmdeletiontext', 'block_massaction');
    $content .= '<table id="block-massaction-module-deletion-list" style="width: 100%;">
        <thead>
            <td style="width: 49%;"><h5>'.
                get_string('moduledeletionname', 'block_massaction').'</h5></td>
            <td style="width: 2%;">&nbsp;</td>
            <td style="width: 49%;"><h5>'.
                get_string('moduledeletiontype', 'block_massaction').'</h5></td>
        </thead>
        <tbody>';

    foreach ($modules as $module) {
        $content .= '
                <tr>
                    <td><p>'.$module['modulename'].'</p></td>
                    <td>&nbsp;</td>
                    <td><p>'.$module['moduletype'].'</p></td>
                </tr>';
    }

    $content .= '
        </tbody>
    </table>';

    echo $OUTPUT->box_start('noticebox');
    $continuebutton = new single_button(
        new moodle_url($CFG->wwwroot.'/blocks/massaction/action.php', $continueoptions),
        get_string('delete'), 'post');
    $cancelbutton = new single_button(
        new moodle_url($CFG->wwwroot.'/course/view.php?id='.$course->id, $canceloptions),
        get_string('cancel'), 'get');

    echo $OUTPUT->confirm($content, $continuebutton, $cancelbutton);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();

    return;
}

/**
 * perform the actual deletion of the selected course modules
 *
 * @param array  $modules Array of module database record objects
 * @param string $returnurl The url to which to redirect
 *
 * @return void
 */
function perform_deletion($modules, $returnurl) {
    global $CFG;

    require_once($CFG->dirroot.'/course/lib.php');

    foreach ($modules as $modulerecord) {
        $cm = validate_module($modulerecord->id);
        $course = validate_course($cm->course);

        $context = context_course::instance($course->id);
        require_capability('moodle/course:manageactivities', $context);

        $modlib = $CFG->dirroot.'/mod/'.$cm->modname.'/lib.php';

        if (file_exists($modlib)) {
            require_once($modlib);
        } else {
            print_error('modulemissingcode', '', '', $modlib);
        }

        course_delete_module($cm->id);
    }

    redirect($returnurl);
}

/**
 * perform the actual relocation of the selected course modules
 *
 * @param array $modules
 * @param int   $target ID of the section to move to
 *
 * @return void
 */
function move_module($modules, $target) {
    global $CFG;

    require_once($CFG->dirroot.'/course/lib.php');

    foreach ($modules as $modulerecord) {
        $cm = validate_module($modulerecord->id);

        // Verify target section.
        $section = validate_section($cm->course, $target);

        $context = context_course::instance($section->course);
        require_capability('moodle/course:manageactivities', $context);

        moveto_module($modulerecord, $section);
    }
}

/**
 * Perform the duplication of the selected course modules
 *
 * @param array $modules
 * @param int   $target ID of the section to move to
 *
 * @return void
 */
function clone_module($modules, $target) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/course/lib.php');

    foreach ($modules as $modulerecord) {
        // Check for all possible failure conditions before doing actual work.
        $cm = validate_module($modulerecord->id);

        // Verify target section.
        $section = validate_section($cm->course, $target);

        $context = context_course::instance($section->course);
        require_capability('moodle/course:manageactivities', $context);

        // No failures and we possess the required capability. Duplicate the module.
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $newcm = duplicate_module($course, $cm);

        moveto_module($newcm, $section);
    }
}

/**
 * Checks that the $target section exists in the course.
 *
 * @param int $course Course id
 * @param int $target Section number
 *
 * @return object $section Section database record
 */
function validate_section($course, $target) {
    global $DB;

    $section = $DB->get_record('course_sections',
        array('course' => $course, 'section' => $target));

    if (!$section) {
        print_error('sectionnotexist', 'block_massaction');
    } else {
        return $section;
    }
}

/**
 * Checks that the module exists.
 *
 * @param int $moduleid Module id
 *
 * @return object $cm Course module database record
 */
function validate_module($moduleid) {
    if (!$cm = get_coursemodule_from_id('', $moduleid, 0, true)) {
        print_error('invalidcoursemodule');
    } else {
        return $cm;
    }
}

/**
 * Checks that the course exists.
 *
 * @param int $courseid The course id
 *
 * @return object $course Course database record
 */
function validate_course($courseid) {
    global $DB;

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourseid');
    } else {
        return $course;
    }
}
