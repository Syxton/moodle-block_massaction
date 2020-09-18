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
 * Configures and displays the block.
 *
 * @package    block_massaction
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class for displaying the Mass Actions block.
 *
 * @package block_massaction
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_massaction extends block_base {

    /**
     * initialize the plugin
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_massaction');
    }

    /**
     * Which page types this block may appear on.
     *
     * The information returned here is processed by the
     * {@link blocks_name_allowed_in_format()} function. Look there if you need
     * to know exactly how this works.
     *
     * @return array page-type prefix => true/false.
     */
    public function applicable_formats() {
        global $COURSE;

        /*
         * If the course uses sections, then it will have modules Mass Actions can act on.
         * If it doesn't, then it's very unlikely Mass Actions will be useful.
         */
        if (course_format_uses_sections($COURSE->format)) {
            $allowed = true;
        } else {
            $allowed = false;
        }

        return array('course-view' => $allowed, 'mod' => false, 'tag' => false);
    }

    /**
     * no need to have multiple blocks to perform the same functionality
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Sets up the content of the block for display to the user.
     *
     * @return The HTML content of the block.
     */
    public function get_content() {
        global $COURSE, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        if ($PAGE->user_is_editing()) {
            $jsdata = $this->get_section_data($COURSE);
            $jsdata['courseformat'] = $COURSE->format;

            /*
             * Have to cast $jsdata to an array, even though it's already an array, or the javascript
             * acts like we only sent an array consisting of the id of the first section that has
             * modules and the ids of its modules.
             */
            $PAGE->requires->js_call_amd('block_massaction/block_massaction', 'init', array($jsdata));

            // Open main content div for the plugin.
            // Having a div with a manually assigned class allows us to style it.
            $this->content->text .= html_writer::start_tag('div',
                array('class' => 'block-massaction-jsenabled'));

            // Get select elements.
            $this->content->text .= $this->get_select_section();

            // Get action links.
            $this->content->text .= $this->get_action_section();

            // Get operation menus.
            // The actions in this array have a topic or section in the course as their target.
            $actions = array(
                'move',
                'clone'
            );

            $this->content->text .= $this->get_operation_section($actions);

            // Get hidden form html.
            $this->content->text .= $this->get_form_html($COURSE->id, $COURSE->format, $this->instance->id,
                $_SERVER['REQUEST_URI']);

            // Get help section.
            $this->content->text .= $this->get_help_section();

            $this->content->text .= html_writer::end_tag('div');
        }

        return $this->content;
    }

    /**
     * Tests if this block has been implemented correctly.
     * Also, $errors isn't used right now
     *
     * @return boolean
     */
    public function _self_test() {
        return true;
    }

    /**
     * Gets an array of section numbers and module/activity ids and an array of section numbers and
     * their human-readable labels.
     *
     * @param object $course Course object
     *
     * @return array $jsdata Multi-dimensional array
     */
    private function get_section_data($course) {
        global $DB;

        // Get an array of section ids and their child module ids.
        $modinfo = get_fast_modinfo($course);
        $sectionmodules = $modinfo->get_sections();

        // Get all section ids and their labels.
        $sectionnames = array();
        $allsections = $DB->get_records_sql('SELECT section FROM {course_sections} WHERE course=:courseid',
            array('courseid' => $course->id));

        foreach ($allsections as $section) {
            $sectionname = get_section_name($course, $section->section);
            $sectionnames[$section->section] = $sectionname;
        }

        $jsdata = array('sectionmodules' => $sectionmodules, 'sectionnames' => $sectionnames);

        return $jsdata;
    }

    /**
     * Gets the html content for the "Select All" and "Deselect All" links,
     * and the "Select All in Section" menu.
     *
     * @return string The html for this section.
     */
    private function get_select_section() {
        $selecttext = html_writer::start_tag('div', array('class' => 'block-massaction-select'));
        $selecttext .= html_writer::start_tag('ul');

        // Create "Select All" link.
        $selecttext .= $this->get_item_content('a', 'selectall',
            array('id' => 'block-massaction-selectall',
                  'href' => 'javascript:void(0);',
                  'title' => get_string('selectall', 'block_massaction')));

        // Open "Select All" menu.
        $selecttext .= $this->get_item_content('select', 'allitems',
            array('id' => 'block-massaction-selectsome'), array('value' => 'all'));

        // Create "Deselect All" link.
        $selecttext .= $this->get_item_content('a', 'selectnone',
            array('id' => 'block-massaction-selectnone',
                  'href' => 'javascript:void(0);',
                  'title' => get_string('selectnone', 'block_massaction')));

        $selecttext .= html_writer::end_tag('ul');
        $selecttext .= html_writer::end_tag('div');

        return $selecttext;
    }

    /**
     * Gets the html content for the action links, e.g. "Outdent", "Indent", etc.
     *
     * @return string The html for this section.
     */
    private function get_action_section() {
        global $CFG;

        $actiontext = html_writer::start_tag('div', array('class' => 'block-massaction-action'));
        $actiontext .= html_writer::start_tag('ul');
        $actiontext .= html_writer::start_tag('li');
        $actiontext .= get_string('withselected', 'block_massaction').':';
        $actiontext .= html_writer::end_tag('li');

        // Print the action links.
        $actionicons = array(
            'outdent' => 't/left',
            'indent'  => 't/right',
            'hide1'    => 't/show',
            'show'    => 't/hide',
            'delete'  => 't/delete'
        );

        if (!empty($CFG->allowstealth)) { // Insert option to create stealth activity.
            $fill = array('hide2' => 't/block');
            $pre = array_slice($actionicons, 0, 3, true);
            $post = array_slice($actionicons, 3, null, true);
            $actionicons = $pre + $fill + $post;
        }

        foreach ($actionicons as $action => $iconpath) {
            $actiontext .= $this->get_item_content('a', 'action_' . $action,
                array('id' => 'block-massaction-' . $action,
                      'class' => 'massaction-action',
                      'href' => 'javascript:void(0);'),
                array(), $iconpath);
        }

        $actiontext .= html_writer::end_tag('ul');
        $actiontext .= html_writer::end_tag('div');

        return $actiontext;
    }

    /**
     * Gets the html content for the operations menus.
     *
     * @param array $actions The operation actions that have a topic or section as their target.
     *
     * @return string The html for this section.
     */
    private function get_operation_section($actions) {
        $opstext = html_writer::start_tag('div', array('class' => 'block-massaction-operation'));
        $opstext .= html_writer::start_tag('ul');

        foreach ($actions as $action) {
            $opstext .= $this->get_item_content('select', 'action_' . $action,
                array('id' => 'block-massaction-' . $action));
        }

        $opstext .= html_writer::end_tag('ul');
        $opstext .= html_writer::end_tag('div');

        return $opstext;
    }

    /**
     * Gets the html content for the help section.
     *
     * @return string The html for this section.
     */
    private function get_help_section() {
        global $OUTPUT;

        $helptext = html_writer::start_tag('div', array('id' => 'block-massaction-help-icon'));
        $helptext .= $OUTPUT->help_icon('usage', 'block_massaction');
        $helptext .= html_writer::end_tag('div');

        return $helptext;
    }

    /**
     * Encapsulates the logic required to write the individual links and drop menus for the block.
     *
     * @param string $itemtag   The element's html tag, e.g. 'select', 'a', etc.
     * @param string $itemstr   The element's language string identifier, e.g. 'action_delete', etc.
     * @param array  $itemattrs The element's id and class, if any.
     * @param array  $itemval   The value for the select menu's first option.
     * @param string $iconpath  The pix_icon path for the link.
     *
     * @return string HTML content for the requested element.
     */
    private function get_item_content($itemtag, $itemstr, $itemattrs = array(), $itemval = array('value' => ''), $iconpath = '') {
        global $OUTPUT;

        $itemtext = html_writer::start_tag('li');
        $itemtext .= html_writer::start_tag($itemtag, $itemattrs);

        if ($itemtag == 'select') {
            $itemtext .= html_writer::start_tag('option', $itemval);
            $itemtext .= get_string($itemstr, 'block_massaction');
            $itemtext .= html_writer::end_tag('option');
        } else {
            if ($iconpath != '') {
                $itemtext .= $OUTPUT->pix_icon($iconpath, get_string($itemstr, 'block_massaction'));
            }

            $itemtext .= get_string($itemstr, 'block_massaction');
        }

        $itemtext .= html_writer::end_tag($itemtag);
        $itemtext .= html_writer::end_tag('li');

        return $itemtext;
    }

    /**
     * Creates the form html for the hidden form submitted when the user chooses the action to apply
     * to the selected modules.
     *
     * @param int    $courseid The course id
     * @param string $courseformat The format of the course, i.e. "weeks"
     * @param int    $instanceid The instance id; this is NOT the same thing as the course id
     * @param string $returnurl The url to redirect to after processing the submission
     *
     * @return string $formhtml The form html
     */
    private function get_form_html($courseid, $courseformat, $instanceid, $returnurl) {
        global $CFG;

        $formaction = $CFG->wwwroot.'/blocks/massaction/action.php';
        $formid = 'block-massaction-control-form';

        $formhtml = '
        <form id="'.$formid.'" name="'.$formid.'" action="'.$formaction.'" method="POST">
            <input type="hidden" id="block-massaction-action" name="action" value="">
            <input type="hidden" id="block-massaction-activities" name="activities" value="">
            <input type="hidden" id="block-massaction-courseid" name="courseid" value="'.$courseid.'">
            <input type="hidden" id="block-massaction-format" name="format" value="'.$courseformat.'">
            <input type="hidden" id="block-massaction-selected-section" name="selectedsection" value="all">
            <input type="hidden" id="block-massaction-selected-all" name="selectedall" value="false">
            <input type="hidden" id="block-massaction-target" name="target" value="">
            <input type="hidden" id="block-massaction-instanceid" name="instanceid" value="'.$instanceid.'">
            <input type="hidden" id="block-massaction-returnurl" name="returnurl" value="'.$returnurl.'">
        </form>';

        return $formhtml;
    }
}
