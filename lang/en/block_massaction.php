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
 * Mass Actions block English language strings.
 *
 * @package    block_massaction
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Mass action block';
$string['massaction:addinstance'] = 'Add a new Mass Actions block';
$string['massaction:use'] = 'Use the Mass Actions block';
$string['blockname'] = 'Mass Actions';
$string['blocktitle'] = 'Mass Actions';
$string['usage'] = 'Mass Actions Help';

$string['selectall'] = 'Select all';
$string['itemsin'] = 'items in';
$string['allitems'] = 'Select all in section:';
$string['selectnone'] = 'Deselect all';
$string['withselected'] = 'With selected';

$string['action_outdent'] = 'Outdent (move left)';
$string['action_indent'] = 'Indent (move right)';
$string['action_hide1'] = 'Hide';
$string['action_hide2'] = 'Hide & Make Available';
$string['action_show'] = 'Show';
$string['action_delete'] = 'Delete';
$string['action_move'] = 'Move to section';
$string['action_clone'] = 'Duplicate to section';

$string['week'] = 'W';
$string['topic'] = 'Topic';
$string['section'] = 'Topic';
$string['section_zero'] = 'General';
$string['selecttarget'] = 'Please select a target section to move items to';
$string['noitemselected'] = 'Please select at least one item to apply the mass-action';

$string['confirmation'] = 'Are you sure you want to delete {$a} items?';
$string['noaction'] = 'No action specified';
$string['invalidaction'] = 'Unknown action: {$a}';
$string['invalidmoduleid'] = 'Invalid module ID: {$a}';
$string['invalidcoursemodule'] = 'Invalid course module';
$string['invalidcourseid'] = 'Invalid course ID';
$string['confirmdeletiontitle'] = 'Confirm mass deletion';
$string['confirmdeletiontext'] = 'Are you sure you want to delete the following module(s)?';
$string['moduledeletionname'] = 'Module name';
$string['moduledeletiontype'] = 'Module type';
$string['sectionnotexist'] = 'Target section does not exist';
$string['missingparam'] = 'Error coding: missing required JSON param "{$a}"';

$string['usage_help'] = '<p>This block allows instructors to perform actions upon multiple resources or activities in the class view, rather than having to perform repeated actions on individual items.</p>
<p>To use this block, Javascript must be enabled in your browser, you must be in editing mode in the course home page, and AJAX must be disabled. Only the courses in the Week or Topics formats are supported.</p>
<p>Supported actions include mass deletion, mass hiding, mass showing, and mass moving. To select items to perform actions on, simply click the checkbox to the right of it in the course home page, or you may select all items, or select all items in a section using the block. To perform actions, click the action you would like to perform inside the block.</p>';

$string['jsdisabled'] = 'You must enable Javascript to use this block.';
