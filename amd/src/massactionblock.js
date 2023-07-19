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

import * as checkboxmanager from './checkboxmanager';
import * as Str from 'core/str';
import Log from 'core/log';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export const usedMoodleCssClasses = {
    ACTIVITY_ITEM: '.activity-item',
    SECTION_NAME: 'sectionname',
    MODULE_ID_PREFIX: 'module-',
};

export const cssIds = {
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
    BOX_ID_PREFIX: 'block-massaction-module-selector-',
    CHECKBOX_CLASS: 'block-massaction-checkbox',
    HIDDEN_FIELD_REQUEST_INFORMATION: 'block-massaction-control-request',
    ACTION_FORM: 'block-massaction-control-form',
};

export const constants = {
    SECTION_SELECT_DESCRIPTION_VALUE: 'description',
    SECTION_NUMBER_ALL_PLACEHOLDER: 'all',
    CHECKBOX_DESCRIPTION_SUFFIX: ' Checkbox'
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
    // Initialize the checkbox manager as soon as the courseeditor is ready.
    editor.stateManager.getInitialPromise()
        .then(() => checkboxmanager.initCheckboxManager())
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
