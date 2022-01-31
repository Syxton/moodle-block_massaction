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
 * block_massaction service definition
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$services = [
    'block_massaction' => [
        'functions' => ['block_massaction_get_sections'],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'block_massaction',
        'downloadfiles' => 0,
        'uploadfiles'  => 0
    ]
];

$functions = [
    'block_massaction_get_sections' => [
        'classname'   => 'block_massaction_external',
        'classpath'   => 'blocks/massaction/externallib.php',
        'methodname'  => 'get_sections',
        'description' => 'Retrieves the section information.',
        'type'        => 'read',
        'ajax' => true,
        'services' => ['block_massaction'],
        'capabilities' => '',
    ],
    'block_massaction_get_modulesinfo' => [
        'classname'   => 'block_massaction_external',
        'classpath'   => 'blocks/massaction/externallib.php',
        'methodname'  => 'get_modulesinfo',
        'description' => 'Retrieves the modules information.',
        'type'        => 'read',
        'ajax' => true,
        'services' => ['block_massaction'],
        'capabilities' => '',
    ],
];

