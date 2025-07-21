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

namespace block_massaction\hook;

use core\di;
use advanced_testcase;
use core\hook\manager;

/**
 * Class to test the hook inside asynchronous_copy_task.
 *
 * @copyright  2025 Monash University
 * @author     Trisha Milan <trishamilan@catalyst-au.net>
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filter_allowed_cm_selection_test extends advanced_testcase {

    /**
     * Test the hook.
     *
     * @covers \block_massaction\hook\filter_allowed_cm_selection
     */
    public function test_filter_allowed_cm_selection(): void {
        $this->setAdminUser();
        $this->resetAfterTest();

        // Load the callback classes.
        require_once(__DIR__ . '/fixtures/filter_allowed_cm_selection_hooks.php');

        // Replace the version of the manager in the DI container with a phpunit one.
        di::set(
            manager::class,
            manager::phpunit_get_instance([
                'block_massaction' => __DIR__ .
                    '/fixtures/filter_allowed_cm_selection_hooks.php',
            ]),
        );

        $course = $this->getDataGenerator()->create_course(['numsections' => 5]);
        $modulerecord = ['course' => $course->id, 'showdescription' => 0];

        // Create two assignment activities.
        $mod1 = $this->getDataGenerator()->create_module('assign', $modulerecord);
        $mod2 = $this->getDataGenerator()->create_module('assign', $modulerecord);

        // Create one page activity which will be restricted.
        $mod3 = $this->getDataGenerator()->create_module('page', $modulerecord);

        // Now call the hook with all three cmids.
        $hook = new filter_allowed_cm_selection($course->id, [$mod1->cmid, $mod2->cmid, $mod3->cmid]);
        di::get(manager::class)->dispatch($hook);

        // Assert that the page cmid is filtered out.
        $this->assertNotContains($mod3->cmid, $hook->cmids);
        $this->assertContains($mod1->cmid, $hook->cmids);
        $this->assertContains($mod2->cmid, $hook->cmids);
        $this->assertCount(2, $hook->cmids);
    }
}
