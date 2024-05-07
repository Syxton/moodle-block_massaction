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

use base_plan_exception;
use base_setting_exception;
use block_massaction\form\course_select_form;
use block_massaction\form\section_select_form;
use block_massaction\hook\filter_sections_different_course;
use block_massaction\hook\filter_sections_same_course;
use coding_exception;
use context_course;
use core\event\course_module_updated;
use core\task\manager;
use core_course\task\content_notification_task;
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
    public static function adjust_indentation(array $modules, int $amount): void {
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
    public static function set_visibility(array $modules, bool $visible, bool $visibleonpage = true): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        if (empty($modules)) {
            return;
        }

        foreach ($modules as $cm) {
            if ($visible && !$visibleonpage) {
                // We want to set the visibility to 'available, but hidden', but have to respect the global config and
                // the course format config.
                if (empty($CFG->allowstealth)) {
                    // We silently ignore this course module it must not be set to 'available, but not visible on course page'.
                    continue;
                }
            }

            // We here also cover the case of a hidden section. In this case moodle only uses the attribute 'visible' to determine,
            // if a course module is completely hidden ('visible' => 0) or 'available, but not visible on course page'
            // ('visible' => 1). The attribute 'visibleonpage' is being ignored, so we can pass it along anyway.
            // Because of this in case of a hidden section both actions ('show' and 'make available') lead to the same result:
            // 'available, but not visible on course page'.

            $visibleint = $visible ? 1 : 0;
            $visibleonpageint = $visibleonpage ? 1 : 0;
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
    public static function duplicate(array $modules, $sectionnumber = false): void {
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

        $idsincourseorder = self::sort_course_order($modules);

        // We now duplicate the modules in the order they have in the course. That way the duplicated modules will be correctly
        // sorted by their id:
        // Let order of mods in a section be mod1, mod2, mod3, mod4, mod5. If we duplicate mod2, mod4, the order afterwards will be
        // mod1, mod2, mod3, mod4, mod5, mod2(dup), mod4(dup).

        $cms = [];
        $errors = [];
        $duplicatedmods = [];
        $filtersectionshook = new filter_sections_same_course($courseid, array_keys($modinfo->get_section_info_all()));
        \core\di::get(\core\hook\manager::class)->dispatch($filtersectionshook);
        foreach ($idsincourseorder as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            // Not duplicated if the section is restricted.
            if (!in_array($cm->sectionnum, $filtersectionshook->get_sectionnums())) {
                throw new moodle_exception('sectionrestricted', 'block_massaction', '', $cm->sectionnum);
            }

            try {
                $duplicatedmod = duplicate_module($modinfo->get_course(), $modinfo->get_cm($cmid));
            } catch (\Exception $e) {
                $errors[$cmid] = 'cmid:' . $cmid . '(' . $e->getMessage() . ')';
                $event = \block_massaction\event\course_modules_duplicated_failed::create([
                    'context' => \context_course::instance($courseid),
                    'other' => [
                        'cmid' => $cmid,
                        'error' => $errors[$cmid],
                    ],
                ]);
                $event->trigger();
                continue;
            }
            $cms[$cmid] = $duplicatedmod->id;
            $duplicatedmods[] = $duplicatedmod;
        }

        // Refetch course structure now including the duplicated modules.
        $modinfo = get_fast_modinfo($courseid);
        foreach ($duplicatedmods as $duplicatedmod) {
            if ($sectionnumber === false) {
                $section = $modinfo->get_section_info($duplicatedmod->sectionnum);
            } else { // Duplicate to a specific section.
                // Verify target.
                if (!$section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnumber])) {
                    throw new moodle_exception('sectionnotexist', 'block_massaction');
                }
            }

            // Move each module to the end of their section.
            moveto_module($duplicatedmod, $section);
        }
        $event = \block_massaction\event\course_modules_duplicated::create([
            'context' => \context_course::instance($courseid),
            'other' => [
                'cms' => $cms,
                'failed' => array_keys($errors),
            ],
        ]);
        $event->trigger();
    }

    /**
     * Duplicates multiple modules to a specified target course into a specified target section.
     *
     * @param array $modules Array of course module records
     * @param int $targetcourseid course id of the course to duplicate the modules to
     * @param int $sectionnum section number of the section where the modules should be duplicated to. The default is -1 which
     *  means that the duplicated modules will appear in the section they have in the source course. If these sections do not exist
     *  they will be added to the target course.
     *
     * @throws coding_exception
     * @throws restore_controller_exception
     * @throws base_setting_exception
     * @throws base_plan_exception
     * @throws moodle_exception
     */
    public static function duplicate_to_course(array $modules, int $targetcourseid, int $sectionnum = -1): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/lib/modinfolib.php');
        if (empty($modules) || !reset($modules)
            || !property_exists(reset($modules), 'course')) {
            return;
        }
        $sourcecourseid = reset($modules)->course;
        $sourcecoursecontext = context_course::instance($sourcecourseid);
        $targetcoursecontext = context_course::instance($targetcourseid);

        if (!has_capability('moodle/backup:backuptargetimport', $sourcecoursecontext)) {
            throw new required_capability_exception($sourcecoursecontext,
                'moodle/backup:backuptargetimport', 'nocaptobackup', 'block_massaction');
        }
        if (!has_capability('moodle/restore:restoretargetimport', $targetcoursecontext)) {
            throw new required_capability_exception($targetcoursecontext,
                'moodle/restore:restoretargetimport', 'nocaptorestore', 'block_massaction');
        }

        $sourcemodinfo = get_fast_modinfo($sourcecourseid);
        $targetmodinfo = get_fast_modinfo($targetcourseid);
        $targetformat = course_get_format($targetmodinfo->get_course());
        $targetsectionnum = $targetformat->get_last_section_number();

        $filtersectionshook = new filter_sections_different_course($targetcourseid,
                array_keys($targetmodinfo->get_section_info_all()));
        \core\di::get(\core\hook\manager::class)->dispatch($filtersectionshook);
        $filteredsections = $filtersectionshook->get_sectionnums();

        if ($targetsectionnum == -1 && !$filtersectionshook->is_originsectionkept()) {
            // The course modules should be in the same section number as in the original course. However, the hook listener(s)
            // disabled this option, so we cancel the operation.
            // This is only a security measure and should not happen unless someone manipulates the UI.
            return;
        }

        if (!in_array($targetsectionnum, $filteredsections)) {
            // The target section number has been filtered by a hook callback, thus must not be used.
            // This is only a security measure and should not happen unless someone manipulates the UI.
            return;
        }

        $canaddsection = has_capability('moodle/course:update', context_course::instance($targetcourseid))
            && $filtersectionshook->is_makesectionallowed();

        // If a new section (that means that $sectionnum of the user is higher than $targetsectionnum), we create one.
        if ($sectionnum > $targetsectionnum) {
            // No permissions to add section.
            if (!$canaddsection) {
                return;
            }

            $targetformatopt = $targetformat->get_format_options();
            // No course format setting or no orphaned sections exist.
            if (!isset($targetformatopt['numsections']) || !($targetformatopt['numsections'] < $targetsectionnum)) {
                course_create_section($targetcourseid);
            }

            // Update course format setting to prevent new orphaned sections.
            if (isset($targetformatopt['numsections'])) {
                update_course((object)['id' => $targetcourseid, 'numsections' => $targetformatopt['numsections'] + 1]);
            }

            // Make sure new sectionnum is set accurately.
            $sectionnum = $targetsectionnum + 1;
        }

        if ($sectionnum == -1) {
            // In case no target section is specified we make sure that enough sections in the target course exist before
            // duplicating, so each course module will be restored to the section number it has in the source course.
            $srcmaxsectionnum = max(array_map(function($mod) use ($sourcemodinfo) {
                return $sourcemodinfo->get_cm($mod->id)->sectionnum;
            }, $modules));

            // If target course needs sections added but user does not have permission.
            if ($srcmaxsectionnum > $targetsectionnum && !$canaddsection) {
                return; // No permission to add section.
            }

            // Add sections if needed.
            course_create_sections_if_missing($targetcourseid, $srcmaxsectionnum);

            // Update course format setting to prevent orphaned sections.
            $targetformatopt = $targetformat->get_format_options();
            if (isset($targetformatopt['numsections']) && $targetformatopt['numsections'] < $srcmaxsectionnum) {
                update_course((object)['id' => $targetcourseid, 'numsections' => $srcmaxsectionnum]);
            }
        }

        $idsincourseorder = self::sort_course_order($modules);
        // We now duplicate the modules in the order they have in the course. That way the duplicated modules will be correctly
        // sorted by their id:
        // Let order of mods in a section be mod1, mod2, mod3, mod4, mod5. If we duplicate mod2, mod4, the order afterwards will be
        // mod1, mod2, mod3, mod4, mod5, mod2(dup), mod4(dup).
        $duplicatedmods = [];
        $cms = [];
        $errors = [];
        $filtersectionshook = new filter_sections_same_course($sourcecourseid, array_keys($sourcemodinfo->get_section_info_all()));
        \core\di::get(\core\hook\manager::class)->dispatch($filtersectionshook);
        $srcfilteredsections = $filtersectionshook->get_sectionnums();

        foreach ($idsincourseorder as $cmid) {
            $sourcecm = $sourcemodinfo->get_cm($cmid);
            // Not duplicated if the section is restricted.
            if (!in_array($sourcecm->sectionnum, $srcfilteredsections)) {
                throw new moodle_exception('sectionrestricted', 'block_massaction', '', $sourcecm->sectionnum);
            }

            try {
                $duplicatedmod = massactionutils::duplicate_cm_to_course($targetmodinfo->get_course(),
                    $sourcemodinfo->get_cm($cmid));
            } catch (\Exception $e) {
                $errors[$cmid] = 'cmid:' . $cmid . '(' . $e->getMessage() . ')';
                $event = \block_massaction\event\course_modules_duplicated_failed::create([
                    'context' => \context_course::instance($sourcecourseid),
                    'other' => [
                        'cmid' => $cmid,
                        'error' => $errors[$cmid],
                    ],
                ]);
                $event->trigger();
                continue;
            }
            $cms[$cmid] = $duplicatedmod;
            $duplicatedmods[] = $duplicatedmod;
        }

        // We need to reload new course structure.
        $targetmodinfo = get_fast_modinfo($targetcourseid);
        $targetsection = $targetmodinfo->get_section_info($sectionnum);
        if ($sectionnum != -1) {
            // A target section has been specified, so we have to move the course modules.
            foreach ($duplicatedmods as $modid) {
                moveto_module($targetmodinfo->get_cm($modid), $targetsection);
            }
        }
        $event = \block_massaction\event\course_modules_duplicated::create([
            'context' => \context_course::instance($sourcecourseid),
            'other' => [
                'cms' => $cms,
                'failed' => array_keys($errors),
            ],
        ]);
        $event->trigger();
    }

    /**
     * Prints the course select form.
     *
     * @param course_select_form $courseselectform
     * @return void
     */
    public static function print_course_select_form(course_select_form $courseselectform): void {
        global $OUTPUT;
        // Show the course selector.
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox block-massaction-courseselectbox', 'block_massaction-course-select-box');
        $courseselectform->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
    }

    /**
     * Prints the section select form.
     *
     * @param section_select_form $sectionselectform
     * @return void
     */
    public static function print_section_select_form(section_select_form $sectionselectform): void {
        global $OUTPUT;
        // Show the section selector.
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox block-massaction-sectionselectbox', 'block_massaction-section-select-box');
        $sectionselectform->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
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
        int $instanceid, string $returnurl): void {
        global $DB, $PAGE, $OUTPUT, $CFG;
        $modulelist = [];

        $idsincourseorder = self::sort_course_order($modules);

        foreach ($idsincourseorder as $cmid) {
            if (!$cm = get_coursemodule_from_id('', $cmid, 0, true)) {
                throw new moodle_exception('invalidcoursemodule');
            }

            if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
                throw new moodle_exception('invalidcourseid');
            }

            $context = context_course::instance($course->id);
            require_capability('moodle/course:manageactivities', $context);
            $modulelist[] = ['moduletype' => get_string('modulename', $cm->modname), 'modulename' => $cm->name];
        }

        $optionsonconfirm = [
            'instance_id' => $instanceid,
            'return_url' => $returnurl,
            'request' => $massactionrequest,
            'del_confirm' => 1,
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
    public static function perform_deletion(array $modules): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        foreach ($modules as $cm) {
            if (!$cm = get_coursemodule_from_id('', $cm->id, 0, true)) {
                new moodle_exception('invalidcoursemodule');
            }

            if (!$DB->get_record('course', ['id' => $cm->course])) {
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
     * Bulk function to show/hide the description of selected course modules on the course page.
     *
     * @param array $modules the course module records to change the showdescription flag
     * @param bool $showdescription true if description should be shown, false otherwise
     * @return void
     * @throws dml_exception
     * @throws moodle_exception if course modules cannot be found
     */
    public static function show_description(array $modules, bool $showdescription): void {
        global $DB;
        if (empty($modules)) {
            return;
        }
        $showdescriptionbit = $showdescription ? 1 : 0;

        $modinfo = get_fast_modinfo(reset($modules)->course);
        foreach ($modules as $cm) {
            if (is_null($modinfo->get_cm($cm->id)->url)) {
                // In case of course modules like 'label', we must not do anything.
                continue;
            }
            if ($cmrecord = $DB->get_record('course_modules', ['id' => $cm->id])) {
                if (intval($cmrecord->showdescription) !== $showdescriptionbit) {
                    $updatedata = new \stdClass();
                    $updatedata->id = $cm->id;
                    $updatedata->showdescription = $showdescriptionbit;
                    $DB->update_record('course_modules', $updatedata);
                    \course_modinfo::purge_course_module_cache($cm->course, $cm->id);
                }
            } else {
                throw new moodle_exception('invalidmoduleid', 'block_massaction', $cm->id);
            }
        }
    }

    /**
     * Send content changed notification for multiple course modules.
     *
     * @param array $modules the modules for which a notification should be sent
     * @throws coding_exception
     * @throws dml_exception if we cannot read from database
     * @throws moodle_exception if wrong module ids are being passed
     */
    public static function send_content_changed_notifications(array $modules): void {
        global $DB, $USER;
        foreach ($modules as $cm) {
            if (!$cm = get_coursemodule_from_id('', $cm->id, 0, true)) {
                throw new moodle_exception('invalidcoursemodule');
            }

            if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
                throw new moodle_exception('invalidcourseid');
            }

            // Schedule adhoc task for delivering the course content updated notifications. Unfortunately, there is no core lib
            // function for this, so we have to c&p from modlib.php#393 (11.06.2022).
            // We keep in sync with the functionality there: If a course module is hidden from course page, but available, it will
            // trigger a notification.
            if ($course->visible && $cm->visible) {
                $adhoctask = new content_notification_task();
                // Apparently 'update' just is used to show if the mod is either 'new' or 'updated' in the message which is
                // being sent. As all modules we handle with block_massaction already exist we can safely set 'update' to 1 which
                // means that the message will read 'course module updated' instead of 'new course module added'.
                $adhoctask->set_custom_data(
                    ['update' => 1, 'cmid' => $cm->id, 'courseid' => $course->id, 'userfrom' => $USER->id]);
                $adhoctask->set_component('course');
                manager::queue_adhoc_task($adhoctask, true);
            }
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

        $idsincourseorder = self::sort_course_order($modules);

        if (!empty($modules)) {
            $courseid = reset($modules)->course;
            $filtersectionshook = new filter_sections_same_course(
                    $courseid,
                    array_keys(get_fast_modinfo($courseid)->get_section_info_all())
            );
            \core\di::get(\core\hook\manager::class)->dispatch($filtersectionshook);
        }

        foreach ($idsincourseorder as $cmid) {
            if (!$cm = get_coursemodule_from_id('', $cmid, 0, true)) {
                throw new moodle_exception('invalidcoursemodule');
            }

            // Verify target.
            if (!$section = $DB->get_record('course_sections', ['course' => $cm->course, 'section' => $target])) {
                throw new moodle_exception('sectionnotexist', 'block_massaction');
            }

            // Not moving if the section is restricted.
            if (!in_array($cm->sectionnum, $filtersectionshook->get_sectionnums())) {
                throw new moodle_exception('sectionrestricted', 'block_massaction', '', $cm->sectionnum);
            }

            // Move each module to the end of their section.
            moveto_module($cm, $section);
        }
    }

    /**
     * Return modules in the order they are listed in the course.
     *
     * @param array $modules the modules to be sorted
     * @return array $idsincourseorder the modules in the order they are listed in the course
     */
    private static function sort_course_order(array $modules): array {
        if (empty($modules)) {
            return [];
        }

        $courseid = reset($modules)->course;

        $modinfo = get_fast_modinfo($courseid);

        // We extract the order of modules across all sections.
        $sections = $modinfo->get_sections();
        $idsincourseorder = [];
        // We "flatmap" all the module ids, section after section with the given order of the modules in their section.
        foreach ($sections as $modids) {
            $idsincourseorder = array_merge($idsincourseorder, $modids);
        }

        // We filter all modules: After that only the modules which should be duplicated are being left.
        $idsincourseorder = array_filter($idsincourseorder, function($cmid) use ($modules) {
            return in_array($cmid, array_map(function($cm) {
                return $cm->id;
            }, $modules));
        });

        return $idsincourseorder;
    }
}
