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
 * Primary block class.
 *
 * @package    block_massaction
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Configures and displays the block.
 *
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_massaction extends block_base {

    /**
     * Initialize the plugin. This method is being called by the parent constructor by default.
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_massaction');
    }

    /**
     * Which page types this block may appear on.
     *
     * The information returned here is processed by the
     * blocks_name_allowed_in_format() function. Look there if you need
     * to know exactly how this works.
     *
     * @return array page-type prefix => true/false.
     * @throws dml_exception
     */
    public function applicable_formats(): array {
        $applicableformats['site-index'] = false;
        $formats = explode(',', get_config('block_massaction', 'applicablecourseformats'));

        foreach ($formats as $pluginname) {
            $applicableformats['course-view-' . $pluginname] = true;
        }
        return $applicableformats;
    }

    /**
     * No need to have multiple blocks to perform the same functionality
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Has config function.
     *
     * @see block_base::has_config()
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets up the content of the block for display to the user.
     *
     * @return stdClass The HTML content of the block.
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_content(): stdClass {
        global $CFG, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if ($this->page->user_is_editing()) {

            $applicableformatkey = 'course-view-' . $COURSE->format;
            $iscoursecompatible = in_array($applicableformatkey, array_keys($this->applicable_formats()))
                && $this->applicable_formats()[$applicableformatkey];
            if (!$iscoursecompatible) {
                $this->content = new stdClass();
                $this->content->text = get_string('unusable', 'block_massaction');
                $this->content->footer = '';
                return $this->content;
            }

            // Check for double instances. This usually should not be an issue, but in rare cases users manage to add
            // two blocks to the site.
            $massactionblockscount = 0;
            foreach ($this->page->blocks->get_regions() as $region) {
                foreach ($this->page->blocks->get_blocks_for_region($region) as $block) {
                    if ($block instanceof block_massaction) {
                        $massactionblockscount++;
                    }
                    if ($massactionblockscount > 1) {
                        $this->content = new stdClass();
                        $this->content->text = get_string('multipleinstances', 'block_massaction');
                        $this->content->footer = '';
                        return $this->content;
                    }
                }
            }

            // Initialize the JS module.
            $this->page->requires->js_call_amd('block_massaction/massactionblock', 'init');

            $context = context_course::instance($COURSE->id);
            // Actions to be rendered later on.
            $actionicons = [];
            if (has_capability('moodle/course:activityvisibility', $context)) {
                // As we want to use this symbol for the *operation*, not the state, we switch the icons hide/show.
                $actionicons['show'] = 't/hide';
                $actionicons['hide'] = 't/show';
                if (!empty($CFG->allowstealth)) {
                    $actionicons['makeavailable'] = 't/block';
                }
            }
            if (has_capability('moodle/backup:backuptargetimport', $context)
                    && has_capability('moodle/restore:restoretargetimport', $context)) {
                $actionicons['duplicate'] = 't/copy';
            }
            if (has_capability('moodle/backup:backuptargetimport', $context)) {
                $actionicons['duplicatetocourse'] = 't/copy';
            }
            if (has_capability('moodle/course:manageactivities', $context)) {
                $actionicons['delete'] = 't/delete';
                if (course_get_format($COURSE->id)->uses_indentation()) {
                    // From Moodle 4.0 on the course format has to declare if it supports indentation or not.
                    $actionicons['moveright'] = 't/right';
                    $actionicons['moveleft'] = 't/left';
                }
                $actionicons['showdescription'] = 't/more';
                $actionicons['hidedescription'] = 't/less';
            }
            if (has_capability('block/massaction:sendcontentchangednotifications',
                    context_block::instance($this->instance->id))) {
                $actionicons['contentchangednotification'] = 't/email';
            }

            $actions = [];
            foreach ($actionicons as $action => $iconpath) {
                $actions[] = ['action' => $action, 'icon' => $iconpath,
                    'actiontext' => get_string('action_' . $action, 'block_massaction')];
            }

            $this->content->text = $OUTPUT->render_from_template('block_massaction/block_massaction',
                ['actions' => $actions, 'formaction' => $CFG->wwwroot . '/blocks/massaction/action.php',
                    'instanceid' => $this->instance->id, 'requesturi' => $_SERVER['REQUEST_URI'],
                    'helpicon' => $OUTPUT->help_icon('usage', 'block_massaction'),
                    'show_moveto_select' => has_capability('moodle/course:manageactivities', $context),
                    'show_duplicateto_select' => (has_capability('moodle/backup:backuptargetimport', $context) &&
                        has_capability('moodle/restore:restoretargetimport', $context)),
                    'sectionselecthelpicon' => $OUTPUT->help_icon('sectionselect', 'block_massaction')
                ]);
        }
        return $this->content;
    }
}
