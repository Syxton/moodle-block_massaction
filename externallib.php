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
 * block_massaction externallib for retrieving updated section structure via ajax.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_massaction\massactionutils;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class block_massaction_external extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_sections_parameters(): external_function_parameters {
        return new external_function_parameters(['courseId' => new external_value(PARAM_INT, 'Course ID')]);
    }

    /**
     * Definition of return values of the webservice.
     *
     * @return external_multiple_structure
     */
    public static function get_sections_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                    'modules' => new external_value(PARAM_TEXT, 'Module IDs string'),
                ]
            )
        );
    }

    /**
     * The actual method returning the sections data via webservice.
     *
     * @param int $courseid the course id of the course we want to retrieve the sections information of
     * @return array the sections data structure
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function get_sections(int $courseid): array {
        $params = self::validate_parameters(self::get_sections_parameters(), ['courseId' => $courseid]);
        $context = context_course::instance($params['courseId']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        return massactionutils::extract_sections_information($params['courseId']);
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_modulesinfo_parameters(): external_function_parameters {
        return new external_function_parameters(['courseId' => new external_value(PARAM_INT, 'Course ID')]);
    }

    /**
     * Definition of return values of the webservice.
     *
     * @return external_multiple_structure
     */
    public static function get_modulesinfo_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'modid' => new external_value(PARAM_INT, 'module id'),
                    'name' => new external_value(PARAM_TEXT, 'module name'),
                ]
            )
        );
    }

    /**
     * The actual method returning the modulesinfo (names) data via webservice.
     *
     * @param int $courseid the course id of the course we want to retrieve the modules information of
     * @return array the modulenames data objects: [{'modid' => MOD_ID, 'name' => MOD_NAME}, ...]
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function get_modulesinfo(int $courseid): array {
        $params = self::validate_parameters(self::get_modulesinfo_parameters(), ['courseId' => $courseid]);
        $context = context_course::instance($params['courseId']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        return massactionutils::get_mod_names($params['courseId']);
    }
}
