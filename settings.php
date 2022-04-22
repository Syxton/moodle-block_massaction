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
 * Settings for the massactions block.
 *
 * @package    block_massaction
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $ADMIN->add('blocksettings', new admin_category('block_massaction_settings',
        new lang_string('pluginname', 'block_massaction')));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'block_massaction/duplicatemaxactivities',
            new lang_string('duplicatemaxactivities', 'block_massaction'),
            new lang_string('duplicatemaxactivities_description', 'block_massaction'),
            5
        ));

        $pluginmanager = \core_plugin_manager::instance();
        $plugins = [];
        foreach (array_keys($pluginmanager->get_installed_plugins('format')) as $plugin) {
            $plugins[$plugin] = new lang_string('pluginname', 'format_' . $plugin);
        }
        // Sort alphabetically. Custom sort function needed, because lang_string is an object.
        uasort($plugins, function($a, $b) {
            return strcmp($a->out(), $b->out());
        });

        // These are the formats supported by the maintainer.
        $supportedformatsbydefault = ['weeks' => 1, 'topics' => 1, 'topcoll' => 1, 'flexsections' => 1];

        $settings->add(new admin_setting_configmulticheckbox(
            'block_massaction/applicablecourseformats',
            new lang_string('applicablecourseformats', 'block_massaction'),
            new lang_string('applicablecourseformats_description', 'block_massaction'),
            $supportedformatsbydefault,
            $plugins)
        );
    }
}
