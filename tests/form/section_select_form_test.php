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

namespace block_massaction\form;

/**
 * Tests for the section select form.
 *
 * @package    block_massaction
 * @copyright  2026 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(section_select_form::class)]
final class section_select_form_test extends \advanced_testcase {
    #[\Override]
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/massaction/tests/fixtures/testable_section_select_form.php');
        parent::setUpBeforeClass();
    }

    /**
     * Data provider for test_target_section_names.
     *
     * @return array Datasets with the stored section name and the expected option text (null means fallback name).
     */
    public static function target_section_names_provider(): array {
        return [
            'regular_name' => ['sectionname' => 'Topic A', 'expected' => 'Topic A'],
            'html_in_name' => [
                'sectionname' => '<img src=x onerror=alert(document.cookie)>Topic A',
                'expected' => 'Topic A',
            ],
            'name_zero' => ['sectionname' => '0', 'expected' => '0'],
            'empty_name' => ['sectionname' => '', 'expected' => null],
        ];
    }

    /**
     * Test the option texts of the target sections.
     *
     * @param string $sectionname The name stored for section 1 of the target course.
     * @param ?string $expected The expected option text, null if the fallback name is expected.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('target_section_names_provider')]
    public function test_target_section_names(string $sectionname, ?string $expected): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $sourcecourse = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $sourcecourse->id]);
        $targetcourse = $generator->create_course(['numsections' => 1]);
        $DB->set_field('course_sections', 'name', $sectionname, ['course' => $targetcourse->id, 'section' => 1]);
        rebuild_course_cache($targetcourse->id, true);

        $form = new testable_section_select_form(null, [
            'request' => json_encode(['action' => 'duplicatetocourse', 'moduleIds' => [$page->cmid]]),
            'instance_id' => 0,
            'return_url' => (new \moodle_url('/course/view.php', ['id' => $sourcecourse->id]))->out(false),
            'sourcecourseid' => $sourcecourse->id,
            'targetcourseid' => $targetcourse->id,
        ]);

        $optiontexts = [];
        foreach ($form->get_quickform()->getElement('sections')->getElements() as $radio) {
            $optiontexts[$radio->getValue()] = $radio->getText();
        }

        $this->assertSame($expected ?? get_string('section') . ' 1', $optiontexts[1]);
    }
}
