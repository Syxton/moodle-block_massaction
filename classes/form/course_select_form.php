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
 * A form to select the course to duplicate multiple course modules to.
 *
 * @package    block_massaction
 * @copyright  2022, ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_massaction\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once('../../config.php');

require_login();

/**
 * A form to select the course to duplicate multiple course modules to.
 *
 * @package    block_massaction
 * @copyright  2022, ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_select_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('hidden', 'request', $this->_customdata['request']);
        $mform->setType('request', PARAM_RAW);
        $mform->addElement('hidden', 'instance_id', $this->_customdata['instance_id']);
        $mform->setType('instance_id', PARAM_INT);
        $mform->addElement('hidden', 'sourcecourseid', $this->_customdata['sourcecourseid']);
        $mform->setType('sourcecourseid', PARAM_INT);
        $mform->addElement('hidden', 'return_url', $this->_customdata['return_url']);
        $mform->setType('return_url', PARAM_URL);

        $sourcecourseid = $this->_customdata['sourcecourseid'];

        $mform->addElement('header', 'choosetargetcourse', get_string('choosetargetcourse', 'block_massaction'));

        $mform->addElement('course', 'targetcourseid', get_string('choosecoursetoduplicateto', 'block_massaction'),
            ['limittoenrolled' => true, 'exclude' => $sourcecourseid,
                'requiredcapabilities' => ['moodle/restore:restoretargetimport']]);

        $this->add_action_buttons(true, get_string('confirmcourseselect', 'block_massaction'));
    }

}
