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
 * Behat massaction skippable steps definitions.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat massaction skippable steps definitions.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_massaction extends behat_base {

    /**
     * Checks that tiles course format is installed.
     *
     * @Given /^tiles_course_format_is_installed$/
     */
    public function tiles_course_format_is_installed() {
        $formatplugins = core_plugin_manager::instance()->get_plugins_of_type('format');
        if (!isset($formatplugins["tiles"])) {
            throw new \Moodle\BehatExtension\Exception\SkippedException;
        }
    }

    /**
     * Checks that topcoll course format is installed.
     *
     * @Given /^topcoll_course_format_is_installed$/
     */
    public function topcoll_course_format_is_installed() {
        $formatplugins = core_plugin_manager::instance()->get_plugins_of_type('format');
        if (!isset($formatplugins["topcoll"])) {
            throw new \Moodle\BehatExtension\Exception\SkippedException;
        }
    }

    /**
     * Checks that onetopic course format is installed.
     *
     * @Given /^onetopic_course_format_is_installed$/
     */
    public function onetopic_course_format_is_installed() {
        $formatplugins = core_plugin_manager::instance()->get_plugins_of_type('format');
        if (!isset($formatplugins["onetopic"])) {
            throw new \Moodle\BehatExtension\Exception\SkippedException;
        }
    }

    /**
     * Checks that grid course format is installed.
     *
     * @Given /^grid_course_format_is_installed$/
     */
    public function grid_course_format_is_installed() {
        $formatplugins = core_plugin_manager::instance()->get_plugins_of_type('format');
        if (!isset($formatplugins["grid"])) {
            throw new \Moodle\BehatExtension\Exception\SkippedException;
        }
    }

    /**
     * Checks that flexsections course format is installed.
     *
     * @Given /^flexsections_course_format_is_installed$/
     */
    public function flexsections_course_format_is_installed() {
        $formatplugins = core_plugin_manager::instance()->get_plugins_of_type('format');
        if (!isset($formatplugins["flexsections"])) {
            throw new \Moodle\BehatExtension\Exception\SkippedException;
        }
    }
}
