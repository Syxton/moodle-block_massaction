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
 * Describes hooks used for testing.
 *
 * @copyright  2025 Monash University
 * @author     Trisha Milan <trishamilan@catalyst-au.net>
 * @package    block_massaction
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/mock_hook_callbacks.php');

$callbacks = [
    [
        'hook' => \block_massaction\hook\filter_allowed_cm_selection::class,
        'callback' => [
            \block_massaction\hook\fixtures\mock_hook_callbacks::class,
            'filter_allowed_cm_selection',
        ],
    ],
];
