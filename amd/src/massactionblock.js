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
 * Main module for the massaction block.
 *
 * @module     block_massaction/massactionblock
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as checkboxmanager from 'block_massaction/checkboxmanager';
import * as Str from 'core/str';
import Log from 'core/log';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import events from "core_course/events";

export const usedMoodleCssClasses = {
    ACTIVITY_ITEM: '.activity-item',
    MODULE_ID_PREFIX: 'module-',
    BOX_ID_PREFIX: 'cmCheckbox'
};

export const cssIds = {
    BLOCK_CONTENT: 'block-massaction',
    BULK_EDITING_DISABLED: 'block-massaction-bulk-editing-disabled',
    SELECT_ALL_LINK: 'block-massaction-control-selectall',
    DESELECT_ALL_LINK: 'block-massaction-control-deselectall',
    HIDE_LINK: 'block-massaction-action-hide',
    SHOW_LINK: 'block-massaction-action-show',
    MAKE_AVAILABLE_LINK: 'block-massaction-action-makeavailable',
    DUPLICATE_LINK: 'block-massaction-action-duplicate',
    DELETE_LINK: 'block-massaction-action-delete',
    SHOW_DESCRIPTION_LINK: 'block-massaction-action-showdescription',
    HIDE_DESCRIPTION_LINK: 'block-massaction-action-hidedescription',
    CONTENT_CHANGED_NOTIFICATION_LINK: 'block-massaction-action-contentchangednotification',
    MOVELEFT_LINK: 'block-massaction-action-moveleft',
    MOVERIGHT_LINK: 'block-massaction-action-moveright',
    MOVETO_ICON_LINK: 'block-massaction-action-moveto',
    DUPLICATETO_ICON_LINK: 'block-massaction-action-duplicateto',
    DUPLICATE_TO_COURSE_ICON_LINK: 'block-massaction-action-duplicatetocourse',
    SECTION_SELECT: 'block-massaction-control-section-list-select',
    MOVETO_SELECT: 'block-massaction-control-section-list-moveto',
    DUPLICATETO_SELECT: 'block-massaction-control-section-list-duplicateto',
    HIDDEN_FIELD_REQUEST_INFORMATION: 'block-massaction-control-request',
    ACTION_FORM: 'block-massaction-control-form',
    SECTION_FILTER_DATA: `[data-block-massaction-data="availabletargetsections"]`
};

export const constants = {
    SECTION_SELECT_DESCRIPTION_VALUE: 'description',
    SECTION_NUMBER_ALL_PLACEHOLDER: 'all',
};

const actions = {
    HIDE: 'hide',
    SHOW: 'show',
    MAKE_AVAILABLE: 'makeavailable',
    DUPLICATE: 'duplicate',
    DELETE: 'delete',
    SHOW_DESCRIPTION: 'showdescription',
    HIDE_DESCRIPTION: 'hidedescription',
    MOVE_LEFT: 'moveleft',
    MOVE_RIGHT: 'moveright',
    CONTENT_CHANGED_NOTIFICATION: 'contentchangednotification',
    MOVE_TO: 'moveto',
    DUPLICATE_TO: 'duplicateto',
    DUPLICATE_TO_COURSE: 'duplicatetocourse',
};

/**
 * Initialize the mass-action block.
 */
export const init = async() => {
    const pendingPromise = new Pending('block_massaction/init');

    const editor = getCurrentCourseEditor();
    // As soon as courseeditor is available, do some initial setup.
    editor.stateManager.getInitialPromise()
        .then(() => {
            // Initialize the checkbox manager.
            checkboxmanager.initCheckboxManager();

            // Show block depending on if the moodle bulk editing util has been activated.
            editor.stateManager.target.addEventListener(events.stateChanged, (event) => {
                // Listen to the event that bulk editing mode has been enabled/disabled.
                if (event.detail.action === 'bulk.enabled:updated') {
                    // Hide/show block content depending on the bulk editing enabled state.
                    document.getElementById(cssIds.BLOCK_CONTENT)?.classList.toggle('d-none');
                    document.getElementById(cssIds.BULK_EDITING_DISABLED)?.classList.toggle('d-none');
                }
            });

            // Register click handler for the button in the placeholder text if bulk editing is still disabled.
            const enableBulkButton = document.getElementById('block-massaction-enable-bulk-editing');
            // Remove the initial disabled attribute which is there to avoid too early clicks by users.
            enableBulkButton.disabled = false;
            enableBulkButton?.addEventListener('click', () => editor.dispatch('bulkEnable', true));
            return true;
        })
        .catch(error => Log.debug(error));

    document.getElementById(cssIds.SELECT_ALL_LINK)?.addEventListener('click',
        () => checkboxmanager.setSectionSelection(true, constants.SECTION_NUMBER_ALL_PLACEHOLDER), false);

    document.getElementById(cssIds.DESELECT_ALL_LINK)?.addEventListener('click',
        () => checkboxmanager.setSectionSelection(false, constants.SECTION_NUMBER_ALL_PLACEHOLDER), false);

    document.getElementById(cssIds.HIDE_LINK)?.addEventListener('click',
        () => submitAction(actions.HIDE), false);

    document.getElementById(cssIds.SHOW_LINK)?.addEventListener('click',
        () => submitAction(actions.SHOW), false);

    document.getElementById(cssIds.MAKE_AVAILABLE_LINK)?.addEventListener('click',
        () => submitAction(actions.MAKE_AVAILABLE), false);

    document.getElementById(cssIds.DUPLICATE_LINK)?.addEventListener('click',
        () => submitAction(actions.DUPLICATE), false);

    document.getElementById(cssIds.DELETE_LINK)?.addEventListener('click',
        () => submitAction(actions.DELETE), false);

    document.getElementById(cssIds.SHOW_DESCRIPTION_LINK)?.addEventListener('click',
        () => submitAction(actions.SHOW_DESCRIPTION), false);

    document.getElementById(cssIds.HIDE_DESCRIPTION_LINK)?.addEventListener('click',
        () => submitAction(actions.HIDE_DESCRIPTION), false);

    document.getElementById(cssIds.CONTENT_CHANGED_NOTIFICATION_LINK)?.addEventListener('click',
        () => submitAction(actions.CONTENT_CHANGED_NOTIFICATION), false);

    document.getElementById(cssIds.MOVELEFT_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_LEFT), false);

    document.getElementById(cssIds.MOVERIGHT_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_RIGHT), false);

    document.getElementById(cssIds.MOVETO_ICON_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_TO), false);

    document.getElementById(cssIds.DUPLICATETO_ICON_LINK)?.addEventListener('click',
        () => submitAction(actions.DUPLICATE_TO), false);

    document.getElementById(cssIds.DUPLICATE_TO_COURSE_ICON_LINK)?.addEventListener('click',
        () => submitAction(actions.DUPLICATE_TO_COURSE), false);

    pendingPromise.resolve();
};

/**
 * Submit the selected action to server.
 *
 * @param {string} action
 * @return {boolean} true if action was successful, false otherwise
 */
const submitAction = (action) => {
    const submitData = {
        'action': action,
        'moduleIds': []
    };

    submitData.moduleIds = checkboxmanager.getSelectedModIds();

    // Verify that at least one checkbox is checked.
    if (submitData.moduleIds.length === 0) {
        displayError(Str.get_string('noitemselected', 'block_massaction'));
        return false;
    }

    // Prep the submission.
    switch (action) {
        case actions.HIDE:
        case actions.SHOW:
        case actions.MAKE_AVAILABLE:
        case actions.DUPLICATE:
        case actions.DUPLICATE_TO_COURSE:
        case actions.CONTENT_CHANGED_NOTIFICATION:
        case actions.MOVE_LEFT:
        case actions.MOVE_RIGHT:
        case actions.DELETE:
        case actions.SHOW_DESCRIPTION:
        case actions.HIDE_DESCRIPTION:
            break;

        case actions.MOVE_TO:
            // Get the target section.
            submitData.moveToTarget = document.getElementById(cssIds.MOVETO_SELECT).value;
            if (submitData.moveToTarget.trim() === '') {
                displayError(Str.get_string('nomovingtargetselected', 'block_massaction'));
                return false;
            }
            break;

        case actions.DUPLICATE_TO:
            // Get the target section.
            submitData.duplicateToTarget = document.getElementById(cssIds.DUPLICATETO_SELECT).value;
            if (submitData.duplicateToTarget.trim() === '') {
                displayError(Str.get_string('nomovingtargetselected', 'block_massaction'));
                return false;
            }
            break;
        default:
            displayError('Unknown action: ' + action + '. Coding error.');
            return false;
    }
    // Set the form value and submit.
    document.getElementById(cssIds.HIDDEN_FIELD_REQUEST_INFORMATION).value = JSON.stringify(submitData);
    document.getElementById(cssIds.ACTION_FORM).submit();
    return true;
};

const displayError = (errorText) => {
    Promise.resolve([Str.get_string('error', 'core'), errorText, Str.get_string('back', 'core')])
        .then(text => Notification.alert(text[0], text[1], text[2]))
        .catch(error => Log.debug(error));
};
