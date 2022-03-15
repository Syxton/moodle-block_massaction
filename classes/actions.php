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
 * actions class: Utility class providing methods for actions performed by the massaction block.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_massaction;

use coding_exception;
use core\event\course_module_updated;
use dml_exception;
use moodle_exception;
use require_login_exception;
use required_capability_exception;
use restore_controller_exception;

/**
 * Block actions class.
 *
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actions {
    /**
     * Helper function to perform indentation/outdentation.
     *
     * @param array $modules list of module records to modify
     * @param int $amount 1 for indent, -1 for outdent, other values are not permitted
     * @throws dml_exception if database write fails
     */
    public static function adjust_indentation(array $modules, int $amount) : void {
        global $DB;
        if (empty($modules) || abs($amount) != 1) {
            return;
        }
        $courseid = reset($modules)->course;
        foreach ($modules as $cm) {
            $cm->indent += $amount;
            // Respect indentation limits like in course/lib.php#1824 and course/lib.php#1825.
            if ($cm->indent < 0 || $cm->indent > 16) {
                continue;
            }

            $DB->set_field('course_modules', 'indent', $cm->indent, ['id' => $cm->id]);
        }
        rebuild_course_cache($courseid);
    }

    /**
     * Helper function to set visibility of modules.
     *
     * @param array $modules list of module records to modify
     * @param bool $visible true to show, false to hide
     * @param bool $visibleonpage false if you want the modules to be available ($visible has to be true), but not visible for
     *  students on the course page
     * @throws coding_exception
     */
    public static function set_visibility(array $modules, bool $visible, bool $visibleonpage = true) : void {
        global $COURSE, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $visibleint = $visible ? 1 : 0;
        $visibleonpageint = $visibleonpage ? 1 : 0;
        $courseformat = course_get_format($COURSE->id);

        foreach ($modules as $cm) {
            if ($visible && !$visibleonpage) {
                // We want to set the visibility to 'available, but hidden', but have to respect the global config and
                // the course format config.
                if (empty($CFG->allowstealth) || !$courseformat->allow_stealth_module_visibility($cm, $cm->section)) {
                    // We silently ignore this course module it must not be set to 'available, but hidden'.
                    continue;
                }
            }
            if (set_coursemodule_visible($cm->id, $visibleint, $visibleonpageint)) {
                course_module_updated::create_from_cm(get_coursemodule_from_id(false, $cm->id))->trigger();
            }
        }
    }

    /**
     * Helper function for duplicating multiple course modules.
     *
     * @param array $modules list of module records to duplicate
     * @param int $sectionnumber section to which the modules should be moved, false if same section as original
     * @throws moodle_exception if we cannot find the course the given modules belong to
     * @throws require_login_exception if we cannot determine the correct context
     * @throws restore_controller_exception If there is an error while duplicating
     */
    public static function duplicate(array $modules, $sectionnumber = false) : void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/lib/modinfolib.php');
        if (empty($modules) || !reset($modules)
                || !property_exists(reset($modules), 'course')) {
            return;
        }

        $courseid = reset($modules)->course;
        // Needed to set the correct context.
        require_login($courseid);

        $modinfo = get_fast_modinfo($courseid);
        $orderinsection = [];
        foreach ($modules as $cm) {
            $duplicatedmod = duplicate_module($modinfo->get_course(), $modinfo->get_cm($cm->id));
            $orderinsection[$duplicatedmod->id] = array_search($cm->id, $modinfo->get_sections()[$duplicatedmod->sectionnum]);
        }
        // The array $orderinsection now has the structure ['duplicated_cmid' => 'place_of_original_cm_in_section'].
        // Now sort array by 'place_of_original_cm_in_section' order in section so we afterwards can iterate over it
        // and move the newly duplicated modules to the end of their section in the correct order:
        // Let order of mods in a section be mod1, mod2, mod3, mod4, mod5. If we duplicate mod2, mod4, the order afterwards will be
        // mod1, mod2, mod3, mod4, mod5, mod2(dup), mod4(dup).
        $duplicatecmids = array_keys($orderinsection);
        $places = array_values($orderinsection);
        array_multisort($duplicatecmids, SORT_ASC, $places, SORT_ASC);
        $orderinsection = array_combine($duplicatecmids, $places);

        // Refetch course structure now including the duplicated modules.
        $modinfo = get_fast_modinfo($courseid);
        foreach ($orderinsection as $duplicatedmodid => $place) {
            unset($place); // Unused and not needed anymore.
            if ($sectionnumber === false) {
                $section = $modinfo->get_section_info($modinfo->get_cm($duplicatedmodid)->sectionnum);
            } else { // Duplicate to a specific section.
                // Verify target.
                if (!$section = $DB->get_record('course_sections', array('course' => $cm->course, 'section' => $sectionnumber))) {
                    throw new moodle_exception('sectionnotexist', 'block_massaction');
                }
            }
            // Move each module to the end of their section.
            moveto_module($modinfo->get_cm($duplicatedmodid), $section);
        }
    }

    /**
     * Print out the list of course-modules to be deleted for confirmation.
     *
     * @param array $modules the modules which should be deleted
     * @param string $massactionrequest the request to pass through for deleting
     * @param int $instanceid the instanceid
     * @param string $returnurl the url we return to when canceling the confirmation page
     * @throws coding_exception
     * @throws dml_exception if we can't read from the database
     * @throws moodle_exception if we have invalid params or moodle url creation fails
     * @throws require_login_exception
     * @throws required_capability_exception
     */
    public static function print_deletion_confirmation(array $modules, string $massactionrequest,
                                                       int $instanceid, string $returnurl) : void {
        global $DB, $PAGE, $OUTPUT, $CFG;
        $modulelist = [];

        foreach ($modules as $cmrecord) {
            if (!$cm = get_coursemodule_from_id('', $cmrecord->id, 0, true)) {
                throw new moodle_exception('invalidcoursemodule');
            }

            if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
                throw new moodle_exception('invalidcourseid');
            }

            $context = \context_course::instance($course->id);
            require_capability('moodle/course:manageactivities', $context);
            $modulelist[] = ['moduletype' => get_string('modulename', $cm->modname), 'modulename' => $cm->name];
        }

        $optionsonconfirm = [
            'instance_id' => $instanceid,
            'return_url' => $returnurl,
            'request' => $massactionrequest,
            'del_confirm' => 1
        ];
        $optionsoncancel = ['id' => $cm->course];

        $strdelcheck = get_string('deletecheck', 'block_massaction');

        require_login($course->id);
        $PAGE->set_url(new \moodle_url('/blocks/massaction/action.php'));
        $PAGE->set_title($strdelcheck);
        $PAGE->set_heading($course->fullname);
        $PAGE->navbar->add($strdelcheck);
        echo $OUTPUT->header();

        // Render the content.
        $content = $OUTPUT->render_from_template('block_massaction/deletionconfirm',
            ['modules' => $modulelist]);

        echo $OUTPUT->box_start('noticebox');
        $formcontinue =
            new \single_button(new \moodle_url("{$CFG->wwwroot}/blocks/massaction/action.php", $optionsonconfirm),
                get_string('delete'), 'post');
        $formcancel =
            new \single_button(new \moodle_url("{$CFG->wwwroot}/course/view.php?id={$course->id}", $optionsoncancel),
                get_string('cancel'), 'get');
        echo $OUTPUT->confirm($content, $formcontinue, $formcancel);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
    }

    /**
     * Perform the actual deletion of the selected course modules.
     *
     * @param array $modules
     * @throws coding_exception
     * @throws dml_exception if we cannot read from database
     * @throws moodle_exception
     */
    public static function perform_deletion(array $modules) : void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        foreach ($modules as $cmrecord) {
            if (!$cm = get_coursemodule_from_id('', $cmrecord->id, 0, true)) {
                new moodle_exception('invalidcoursemodule');
            }

            if (!$DB->get_record('course', array('id' => $cm->course))) {
                throw new moodle_exception('invalidcourseid');
            }

            $modlib = $CFG->dirroot . '/mod/' . $cm->modname . '/lib.php';

            if (file_exists($modlib)) {
                require_once($modlib);
            } else {
                new moodle_exception('modulemissingcode', '', '', $modlib);
            }

            course_delete_module($cm->id, true);
        }
    }

    /**
     * Move the selected course modules to another section.
     *
     * @param array $modules the modules to be moved
     * @param int $target ID of the section to move to
     * @throws coding_exception
     * @throws dml_exception if we cannot read from database
     * @throws moodle_exception if we have invalid parameters
     */
    public static function perform_moveto(array $modules, int $target): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        foreach ($modules as $cmrecord) {
            if (!$cm = get_coursemodule_from_id('', $cmrecord->id, 0, true)) {
                throw new moodle_exception('invalidcoursemodule');
            }

            // Verify target.
            if (!$section = $DB->get_record('course_sections', array('course' => $cm->course, 'section' => $target))) {
                throw new moodle_exception('sectionnotexist', 'block_massaction');
            }

            moveto_module($cmrecord, $section);
        }
    }
}
