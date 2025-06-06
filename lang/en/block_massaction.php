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
 * Language file.
 *
 * @package    block_massaction
 * @copyright  2021 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['action_contentchangednotification'] = 'Send content changed notification';
$string['action_delete'] = 'Delete';
$string['action_duplicate'] = 'Duplicate';
$string['action_duplicatetocourse'] = 'Duplicate to another course';
$string['action_duplicatetosection'] = 'Duplicate to section';
$string['action_hide'] = 'Hide';
$string['action_hidedescription'] = 'Hide description';
$string['action_makeavailable'] = 'Make available';
$string['action_moveleft'] = 'Outdent (move left)';
$string['action_moveright'] = 'Indent (move right)';
$string['action_movetosection'] = 'Move to section';
$string['action_show'] = 'Show';
$string['action_showdescription'] = 'Show description';
$string['actionexecuted'] = 'The action you requested has been executed.';
$string['applicablecourseformats'] = 'Applicable course formats';
$string['applicablecourseformats_description'] = 'Mass Actions block will only be available for the selected course formats. <br />Preselected defaults are the ones tested and supported by the plugin maintainer. Add other formats at your own risk.';
$string['backgroundtaskinformation'] = 'The action you demanded is being executed in the background. You can continue your work while waiting for it to finish.';
$string['blockname'] = 'Mass Actions';
$string['blocktitle'] = 'Mass Actions';
$string['bulkeditingdisabled'] = 'To use this block, you need to enable bulk editing mode.';
$string['choosecoursetoduplicateto'] = 'Choose the course you want to duplicate the selected course modules to';
$string['choosesectiontoduplicateto'] = 'Choose the section you want the selected course modules to be duplicated to.';
$string['choosetargetcourse'] = 'Choose target course';
$string['choosetargetsection'] = 'Choose target section';
$string['confirmcourseselect'] = 'Choose course';
$string['confirmsectionselect'] = 'Choose section';
$string['deletecheck'] = 'Confirm mass deletion';
$string['deletecheckconfirm'] = 'Are you sure you want to delete the following module(s)?';
$string['deselectall'] = 'Deselect all';
$string['duplicatefailed'] = 'Could not duplicate course module from id {$a}';
$string['duplicatemaxactivities'] = 'Maximum amount of course modules to duplicate';
$string['duplicatemaxactivities_description'] = 'Maximum amount of course modules which can be duplicated at the same time without running the process as background task. If set to "0" all duplication operations will be run as background task.';
$string['enablebulkediting'] = 'Enable bulk editing';
$string['event:course_modules_duplicated'] = 'Course modules duplicated';
$string['event:course_modules_duplicated_failed'] = 'Course modules failed to duplicate';
$string['event:duplicated_completed_list'] = 'Completed {$a->list}.';
$string['event:duplicated_description'] = 'cmid from \'{$a->src}\' to \'{$a->dst}\'';
$string['event:duplicated_failed_description'] = 'Course module duplication failed. cmid: {$a->cmid} error: {$a->error}';
$string['event:duplicated_failed_list'] = 'Failed {$a->list}.';
$string['event:duplicated_summary'] = 'Course module duplication has been completed. Summary: {$a->countcomplete} Completed, {$a->countfailed} Failed.';
$string['invalidaction'] = 'Unknown action: {$a}';
$string['invalidcourseid'] = 'Invalid course ID';
$string['invalidcoursemodule'] = 'Invalid course module';
$string['invalidmoduleid'] = 'Invalid module ID: {$a}';
$string['jsonerror'] = 'Error coding: Invalid JSON format';
$string['keepsectionnum'] = 'Keep original section number';
$string['limittoenrolled'] = 'Limit target course list to courses in which the user is enrolled';
$string['limittoenrolled_description'] = 'If enabled the course selection of the feature "Duplicate to another course" will be limited to courses in which the user is enrolled. Enabling this is recommended for instances with many courses, because not limiting the courses is likely to result in performance issues and timeouts. Disabling this option is at one own\'s risk.';
$string['massaction:activityshowhide'] = 'MassAction: Show/Hide modules';
$string['massaction:addinstance'] = 'Add a new Mass Actions Block';
$string['massaction:delete'] = 'MassAction: Delete modules';
$string['massaction:descriptionshowhide'] = 'MassAction: Show/Hide Descriptions';
$string['massaction:duplicate'] = 'MassAction: Duplicate modules';
$string['massaction:duplicatetocourse'] = 'MassAction: Duplicate to Course';
$string['massaction:indent'] = 'MassAction: Indent modules';
$string['massaction:movetosection'] = 'MassAction: Move to Section';
$string['massaction:sendcontentchangednotifications'] = 'Send content changed notifications';
$string['massaction:use'] = 'Use the Mass Actions block';
$string['modulename'] = 'Activity name';
$string['moduletype'] = 'Activity type';
$string['multipleinstances'] = 'There must not be multiple instances of this block on the same page. <br />Please remove additional instances.';
$string['newsection'] = 'New Section';
$string['noaction'] = 'No action specified';
$string['noactionsavailable'] = 'You do not have the permissions to execute any of the possible operations this block is providing';
$string['nocaptobackup'] = 'You do not have sufficient permissions to perform a backup in the course';
$string['nocaptorestore'] = 'You do not have sufficient permissions to perform a restore in the course';
$string['noitemselected'] = 'Please select at least one item to apply the mass-action';
$string['nomovingtargetselected'] = 'Please select a target section';
$string['notargetcourseidspecified'] = 'No target course id has been specified';
$string['pluginname'] = 'Mass Actions';
$string['privacy:metadata'] = 'This block only offers the possibility to apply standard operations on multiple course modules at the same time.
Thus, no data is being stored by this block.';
$string['restricteditemselected'] = 'One or more of the selected activities are restricted and cannot be included in this action.';
$string['sectionnotexist'] = 'Target section does not exist';
$string['sectionrestricted'] = 'Source section is restricted: You are not allowed to apply an action to activities in section {$a}.';
$string['sectionselect'] = 'Section selection';
$string['sectionselect_help'] = 'You can only select sections which include at least one course module.
Additionally, when using the Tiles or One Topic course format you can only select sections which are currently visible.';
$string['selectall'] = 'Select all';
$string['selectallinsection'] = 'Select all in section';
$string['sourcecourseidlost'] = 'Source course id could not be found';
$string['unusable'] = 'The mass action functionality cannot be used in this course format or the current theme';
$string['usage'] = 'Usage of the Mass Actions Block';
$string['usage_help'] = '<p>This block allows instructors to perform actions upon multiple resources or activities in the class view, rather than having to perform repeated actions on individual items.</p>
<p>To use this block, Javascript must be enabled in your browser and you must be in editing mode in the course home page. Supported course formats are Week, Topic, Topcoll, Onetopic and Tiles.</p>
<p>Supported actions include deletion, in-/outdentation, hiding/revealing and moving.
To select items to perform actions on, simply click the checkbox to the left of it in the course home page, or you may select all items,
or select all items in a section using the block. To perform actions, click the action you would like to perform inside the block.</p>';
$string['withselected'] = 'With selected';
