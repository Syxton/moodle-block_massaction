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

defined('MOODLE_INTERNAL') || die();

/**
 * block_massmaction phpunit tests.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @auther     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_massaction_test extends advanced_testcase {

    public stdClass $course;

    /**
     * Prepare testing
     */
    public function setUp(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();
        $this->resetAfterTest();

        $teacher = $generator->create_user();
        $this->course = $generator->create_course(['numsections' => 5]);
        $generator->enrol_user($teacher->id, $this->course->id, 'editingteacher');

        // Generate two modules of each type for each of the 5 sections, so we have 6 modules per section.
        for ($i = 0; $i < 10; $i++) {
            $generator->create_module('assign', ['course' => $this->course->id], ['section' => floor($i / 2)]);
            $generator->create_module('label', ['course' => $this->course->id], ['section' => floor($i / 2)]);
            $generator->create_module('page', ['course' => $this->course->id], ['section' => floor($i / 2)]);
        }

        $this->setUser($teacher);
    }

    public function test_extract_modules_from_json() {
        // Negative tests.
        $this->expectException(\moodle_exception::class);
        block_massaction\massactionutils::extract_modules_from_json('{}');
        $this->expectException(\moodle_exception::class);
        block_massaction\massactionutils::extract_modules_from_json('');
        $this->expectException(\moodle_exception::class);
        block_massaction\massactionutils::extract_modules_from_json('{[]}');

        // Positive tests.
        $modulerecords = $this->get_test_course_modules();
        $selectedmodulerecords = array_splice($modulerecords, 1, 3);
        $selectedmodulerecords = array_map(fn($module) => $module->id, $selectedmodulerecords);
        $jsonstring = '{"action":"moveleft","moduleIds":[';
        foreach ($selectedmodulerecords as $selectedmodule) {
            $jsonstring .= '"'.$selectedmodule.'",';
        }
        $jsonstring = substr($jsonstring, 0, -1);
        $jsonstring .= ']}';
        $data = block_massaction\massactionutils::extract_modules_from_json($jsonstring);
        foreach ($selectedmodulerecords as $selectedmodule) {
            $this->assertTrue(in_array($selectedmodule, $data->moduleIds));
            $this->assertTrue(in_array($selectedmodule, array_keys($data->modulerecords)));
        }
        foreach ($data->moduleIds as $extractedmoduleid) {
            $this->assertTrue(in_array($extractedmoduleid, $selectedmodulerecords));
        }
        foreach (array_keys($data->modulerecords) as $moduleidfrommodulerecords) {
            $this->assertTrue(in_array($moduleidfrommodulerecords, $selectedmodulerecords));
        }
    }

    public function test_get_mod_names() {
        $modulerecords = $this->get_test_course_modules();
        $modnames = \block_massaction\massactionutils::get_mod_names($this->course->id);

        // Check if there is a modname object ['modid' => MOD_ID, 'name' => MOD_NAME] for each of the course modules in the course.
        $this->assertTrue(array_map(fn($modnameobject) => $modnameobject->modid, $modnames)
            === array_values(array_map(fn($mod) => $mod->id, $modulerecords)));

        $modinfo = get_fast_modinfo($this->course->id);
        foreach ($modnames as $modnameobject) {
            // Check for each given course module if the returned object contains the correct module's name.
            $this->assertEquals($modinfo->get_cm($modnameobject->modid)->get_name(), $modnameobject->name);
        }
    }

    private function get_test_course_modules(): array {
        global $DB;
        $modulerecords = $DB->get_records_select('course_modules', 'course = ?', [$this->course->id], 'id');
        return $modulerecords;
    }

    public function test_mass_delete_modules() {
        global $DB;
        $modulerecords = $this->get_test_course_modules();
        block_massaction\actions::perform_deletion($modulerecords);
        foreach ($modulerecords as $module) {
            // We delete asynchronously, so we have to only check if there aren't any modules without deletion in progress.
            $modulerecord = $DB->get_record_select('course_modules', 'id = ? AND deletioninprogress = 0', [$module->id]);
            $this->assertEquals(false, $modulerecord);
        }
    }

    public function test_mass_move_modules_to_new_section() {
        global $DB;
        $targetsectionnum = 3;

        // Method should do nothing for empty modules array.
        // Throwing an exception would make this whole test fail, so this a 'valid' test.
        block_massaction\actions::perform_moveto([], $targetsectionnum);

        // Select some random course modules from different sections to be moved.
        $moduleidstomove[] = get_fast_modinfo($this->course->id)->get_sections()[1][0];
        $moduleidstomove[] = get_fast_modinfo($this->course->id)->get_sections()[2][1];
        $moduleidstomove[] = get_fast_modinfo($this->course->id)->get_sections()[3][2];
        $modulestomove = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $moduleidstomove));

        block_massaction\actions::perform_moveto($modulestomove, $targetsectionnum);
        // If the move of the selected modules has been successful, all the moved course module ids should be listed in the
        // 'sequence' field of the target section entry in the course_sections table.
        $targetsection = $DB->get_record('course_sections', ['course' => $this->course->id, 'section' => $targetsectionnum]);
        foreach ($moduleidstomove as $movedmoduleid) {
            $this->assertTrue(in_array($movedmoduleid, explode(',', $targetsection->sequence)));
        }
    }

    public function test_mass_hide_unhide_modules() {
        // Method should do nothing for empty modules array.
        // Throwing an exception would make this whole test fail, so this is a 'valid' test.
        block_massaction\actions::set_visibility([], 1);

        // Select some random course modules from different sections to be hidden.
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[1][0];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[2][1];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[3][2];
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));

        // Assert the modules are visible before calling method.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
        }
        block_massaction\actions::set_visibility($selectedmodules, false);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be hidden.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->visible);
        }

        // Check, if hide them again will change nothing.
        block_massaction\actions::set_visibility($selectedmodules, false);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be hidden.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->visible);
        }

        // All modules are hidden now, make them visible again.
        block_massaction\actions::set_visibility($selectedmodules, true);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be visible again.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
        }

        // All modules are visible now, check if making them visible again will change nothing.
        block_massaction\actions::set_visibility($selectedmodules, true);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now still be visible.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
        }

        // Check if we can hide them, but make them available.
        block_massaction\actions::set_visibility($selectedmodules, true, false);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now still be visible.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
            $this->assertEquals(0, $module->visibleoncoursepage);
        }

        // Check if we can show them again.
        block_massaction\actions::set_visibility($selectedmodules, true);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be completely visible again.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
            $this->assertEquals(1, $module->visibleoncoursepage);
        }

        // Hide them and then make them only available.
        block_massaction\actions::set_visibility($selectedmodules, false);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be completely hidden.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->visible);
        }
        // Now make them only available, but not visible on course page.
        block_massaction\actions::set_visibility($selectedmodules, true, false);
        // Reload modules from database.
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        // All selected modules should now be only available, but not visible.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->visible);
            $this->assertEquals(0, $module->visibleoncoursepage);
        }
    }

    public function test_mass_duplicate_modules() {
        // Call with empty values should do nothing.
        block_massaction\actions::duplicate([]);
        block_massaction\actions::duplicate([new \stdClass()]);

        // First of all: Re-order modules of some sections randomly.
        // Reason: We want to see if the order of the section is preserved which usually is different from the module ids.
        // The method to be tested should follow the sections order. To be able to see the correct effect we have to ensure that
        // the order of moduleids isn't the same as the order in the section.
        moveto_module(get_fast_modinfo($this->course->id)->get_cm(get_fast_modinfo($this->course->id)->get_sections()[1][0]),
            get_fast_modinfo($this->course->id)->get_section_info(1));
        moveto_module(get_fast_modinfo($this->course->id)->get_cm(get_fast_modinfo($this->course->id)->get_sections()[1][3]),
            get_fast_modinfo($this->course->id)->get_section_info(1));
        moveto_module(get_fast_modinfo($this->course->id)->get_cm(get_fast_modinfo($this->course->id)->get_sections()[3][0]),
            get_fast_modinfo($this->course->id)->get_section_info(3));
        moveto_module(get_fast_modinfo($this->course->id)->get_cm(get_fast_modinfo($this->course->id)->get_sections()[3][3]),
            get_fast_modinfo($this->course->id)->get_section_info(3));

        // Select some random course modules from different sections to be hidden.
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[1][0];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[1][1];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[3][0];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[3][2];

        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));

        block_massaction\actions::duplicate($selectedmodules);

        $modinfo = get_fast_modinfo($this->course->id);
        $sections = $modinfo->get_sections();
        $sectiononemodulesincorrectorder = $sections[1];
        $this->assertEquals($selectedmoduleids[0], $sectiononemodulesincorrectorder[0]);
        $this->assertEquals($selectedmoduleids[1], $sectiononemodulesincorrectorder[1]);
        // After the six already existing modules the duplicated modules should appear.
        $this->assertEquals($modinfo->get_cm($sectiononemodulesincorrectorder[6])->name,
            $modinfo->get_cm($selectedmoduleids[0])->name . ' (copy)');
        $this->assertEquals($modinfo->get_cm($sectiononemodulesincorrectorder[7])->name,
            $modinfo->get_cm($selectedmoduleids[1])->name . ' (copy)');

        // Same for the other modules in the other section.
        $sectionthreemodulesincorrectorder = $sections[3];
        $this->assertEquals($selectedmoduleids[2], $sectionthreemodulesincorrectorder[0]);
        $this->assertEquals($selectedmoduleids[3], $sectionthreemodulesincorrectorder[2]);
        // After the six already existing modules the duplicated modules should appear.
        $this->assertEquals($modinfo->get_cm($sectionthreemodulesincorrectorder[6])->name,
            $modinfo->get_cm($selectedmoduleids[2])->name . ' (copy)');
        $this->assertEquals($modinfo->get_cm($sectionthreemodulesincorrectorder[7])->name,
            $modinfo->get_cm($selectedmoduleids[3])->name . ' (copy)');
    }

    public function test_mass_adjust_indentation() {
        // Method should do nothing for empty modules array.
        // Throwing an exception would make this whole test fail, so this a 'valid' test.
        block_massaction\actions::adjust_indentation([], 1);

        // Select some random course modules from different sections to be hidden.
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[1][0];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[2][1];
        $selectedmoduleids[] = get_fast_modinfo($this->course->id)->get_sections()[3][2];
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));

        // Assert the modules are not indented yet.
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }
        // Negative tests: Method should only work if parameter 'amount' equals '1' oder '-1'.
        // In all other cases method should do nothing.
        block_massaction\actions::adjust_indentation($selectedmodules, 0);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));

        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }
        block_massaction\actions::adjust_indentation($selectedmodules, -2);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }
        block_massaction\actions::adjust_indentation($selectedmodules, 2);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }

        // Now indent to the right.
        block_massaction\actions::adjust_indentation($selectedmodules, 1);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(1, $module->indent);
        }

        // We now indent another 15 times to check if we properly handle maximum amount of indenting to the right.
        for ($i = 0; $i < 15; $i++) {
            block_massaction\actions::adjust_indentation($selectedmodules, 1);
        }
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(16, $module->indent);
        }
        // Indenting another time to the right now should do nothing.
        block_massaction\actions::adjust_indentation($selectedmodules, 1);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(16, $module->indent);
        }

        // We now indent 16 times to the left to be back at 'no indentation'.
        for ($i = 0; $i < 16; $i++) {
            block_massaction\actions::adjust_indentation($selectedmodules, -1);
        }
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }
        // Indenting another time to the left now should do nothing.
        block_massaction\actions::adjust_indentation($selectedmodules, -1);
        $selectedmodules = array_filter($this->get_test_course_modules(), fn($module) => in_array($module->id, $selectedmoduleids));
        foreach ($selectedmodules as $module) {
            $this->assertEquals(0, $module->indent);
        }

    }

}
