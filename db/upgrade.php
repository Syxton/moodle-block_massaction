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
 * Mass Actions block capabilities data.
 *
 * @package    block_massaction
 * @copyright  2022 Rose Hulman
 * @author     Matt Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 * @param object $block
 * @return bool true if upgrade successfully finished
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_block_massaction_upgrade($oldversion, $block): bool {

    if ($oldversion < 2022000000) { // Stand-in function.
        upgrade_block_savepoint(true, 2022000000, 'massaction', false);
    }

    if ($oldversion < 2023041700) { // Add onetopic, grid, and tiles support.
        block_massaction_add_supported_format('onetopic');
        block_massaction_add_supported_format('grid');
        block_massaction_add_supported_format('tiles');
        upgrade_block_savepoint(true, 2023041700, 'massaction', false);
    }

    return true;
}

/**
 * Handles upgrades that add new supported formats.
 *
 * @param string $addformat
 * @throws coding_exception
 * @throws dml_exception
 */
function block_massaction_add_supported_format(string $addformat): void {
    global $DB;

    // Get current settings to update.
    $selectedformats = get_config('block_massaction', 'applicablecourseformats');
    $selectedformats = explode(',', $selectedformats);

    // Gather all possible course formats.
    $pluginmanager = \core_plugin_manager::instance();
    $plugins = [];
    foreach (array_keys($pluginmanager->get_installed_plugins('format')) as $plugin) {
        $plugins[$plugin] = new lang_string('pluginname', 'format_' . $plugin);
    }

    $supportedformats = [];
    foreach ($plugins as $format => $name) {
        if (isset($name) &&
            (in_array($format, $selectedformats) ||
                $format === $addformat)) {
            $supportedformats[$format] = 1;
        }
    }

    // Update settings.
    set_config('applicablecourseformats', implode(',', array_keys($supportedformats)), 'block_massaction');
}

/**
 * Handles upgrades that remove previously supported formats
 *
 * @param string $removeformat
 * @throws coding_exception
 * @throws dml_exception
 */
function block_massaction_remove_supported_format(string $removeformat): void {
    global $DB;

    // Get current settings to update.
    $selectedformats = get_config('block_massaction', 'applicablecourseformats');
    $selectedformats = explode(',', $selectedformats);

    // Gather all possible course formats.
    $pluginmanager = \core_plugin_manager::instance();
    $plugins = [];
    foreach (array_keys($pluginmanager->get_installed_plugins('format')) as $plugin) {
        $plugins[$plugin] = new lang_string('pluginname', 'format_' . $plugin);
    }

    $supportedformats = [];
    foreach ($plugins as $format => $name) {
        if (isset($name) &&
            in_array($format, $selectedformats) &&
            $format !== $removeformat) {
            $supportedformats[$format] = 1;
        }
    }

    // Update settings.
    set_config('applicablecourseformats', implode(',', array_keys($supportedformats)), 'block_massaction');
}
