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

import checkboxManager from './checkboxmanager';
import * as Str from 'core/str';
import Ajax from 'core/ajax';
import Pending from 'core/pending';

let sectionBoxes = {};
let isRebuilding = false;

export const usedMoodleCssClasses = {
    SECTION_NAME: 'sectionname',
    MODULE_ID_PREFIX: 'module-',
    SPINNER: 'spinner',
    DNDUPLOAD: 'dndupload-progress-outer',
};

export const cssIds = {
    SELECT_ALL_LINK: 'block-massaction-control-selectall',
    DESELECT_ALL_LINK: 'block-massaction-control-deselectall',
    HIDE_LINK: 'block-massaction-action-hide',
    SHOW_LINK: 'block-massaction-action-show',
    MAKE_AVAILABLE_LINK: 'block-massaction-action-makeavailable',
    DUPLICATE_LINK: 'block-massaction-action-duplicate',
    DELETE_LINK: 'block-massaction-action-delete',
    MOVELEFT_LINK: 'block-massaction-action-moveleft',
    MOVERIGHT_LINK: 'block-massaction-action-moveright',
    MOVETO_ICON_LINK: 'block-massaction-action-moveto',
    DUPLICATETO_ICON_LINK: 'block-massaction-action-duplicateto',
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
    MOVE_LEFT: 'moveleft',
    MOVE_RIGHT: 'moveright',
    MOVE_TO: 'moveto',
    DUPLICATE_TO: 'duplicateto',
};

/**
 * Initialize the mass-action block.
 *
 * @param {int} courseId the id of the current course.
 */
export const init = async(courseId) => {

    const pendingPromise = new Pending('block_massaction/init');
    rebuildSections(courseId);

    /*
     * This is definitely not what you want to do, but there is probably no better way:
     * Observe all changes to the DOM of the body, and filter the correct mutations to get the following events:
     * - Drag&Drop moving of a module
     * - Drag&Drop upload of files
     * - Change of section names
     * - Drag&Drop moving of sections
     * In all cases: get new information from webservice and re-render the plugin.
     * Care: It doesn't react to renaming of modules, because it's only relevant for name/aria-label of the
     * checkboxes. This slight imperfection is being ignored in favour of performance.
     */
    const observer = new MutationObserver(function(mutations) {
        mutations = mutations.filter(mutation => mutation.type === 'childList');
        const mutationsSection = mutations
            .filter(mutation => mutation.addedNodes && mutation.addedNodes.length > 0);
        // Typical MutationObserver record pattern if two sections have been swapped:
        // The MutationRecord contains two newly added elements, both of them have a 'sectionname' class.
        if (mutationsSection.length === 2
            && mutationsSection.every(mutation => mutation.target.classList.contains(usedMoodleCssClasses.SECTION_NAME))) {
            rebuildSections(courseId);
            // We already triggered the rebuild, no need to search for further mutation observer events.
            return;
        }

        // Build the node tree recursively to later check if some of the objects has been removed.
        const mutationsActivities = mutations
            .filter(mutation => mutation.removedNodes && mutation.removedNodes.length > 0);
        let allRemovedNodes = [];
        mutationsActivities.forEach(item => {
            const descendants = getAllDescendants(item.removedNodes);
            if (descendants) {
                allRemovedNodes = allRemovedNodes.concat(descendants);
            }
        });

        if (allRemovedNodes.length > 0) {
            // First remove all text nodes (nodeType 3).
            allRemovedNodes.filter(node => node.nodeType !== 3).forEach(node => {
                // Then check if a spinner has been removed (indicates end of drag'n'drop move and changing of section name) or
                // if a dndupload-progress-outer item has been removed -> indicates a finished drag'n'drop file upload.
                if (node.classList && node.classList.contains(usedMoodleCssClasses.SPINNER)
                    || node.classList.contains(usedMoodleCssClasses.DNDUPLOAD)) {
                    rebuildSections(courseId);
                }
            });
        }
    });

    // Activate the mutation observer for tracking drag'n'drop changes.
    // Unfortunately there seems to be some moodle JS which is loaded AFTER the readystatechange event has been set to 'completed'.
    // Thus, we have to go for an additional timeout to wait for it.
    document.addEventListener('readystatechange', event => {
        if (event.target.readyState === 'complete') {
            setTimeout(() => observer.observe(document.body, {
                subtree: true,
                childList: true,
                attributes: false,
                characterData: false
            }), 1000);
        }
    });

    document.getElementById(cssIds.SELECT_ALL_LINK)?.addEventListener('click',
        () => setSectionSelection(true, constants.SECTION_NUMBER_ALL_PLACEHOLDER), false);

    document.getElementById(cssIds.DESELECT_ALL_LINK)?.addEventListener('click',
        () => setSectionSelection(false, constants.SECTION_NUMBER_ALL_PLACEHOLDER), false);

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

    document.getElementById(cssIds.MOVELEFT_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_LEFT), false);

    document.getElementById(cssIds.MOVERIGHT_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_RIGHT), false);

    document.getElementById(cssIds.MOVETO_ICON_LINK)?.addEventListener('click',
        () => submitAction(actions.MOVE_TO), false);

    document.getElementById(cssIds.DUPLICATETO_ICON_LINK)?.addEventListener('click',
        () => submitAction(actions.DUPLICATE_TO), false);

    pendingPromise.resolve();
};

/**
 * Select all module checkboxes in section(s).
 *
 * @param {boolean} value the checked value to set the checkboxes to
 * @param {string} sectionNumber the section number of the section which all modules should be checked/unchecked. Use "all" to
 * select/deselect modules in all sections.
 */
export const setSectionSelection = (value, sectionNumber) => {
    const boxIds = [];

    if (typeof sectionNumber !== 'undefined' && sectionNumber === constants.SECTION_SELECT_DESCRIPTION_VALUE) {
        // Description placeholder has been selected, do nothing.
        return;
    } else if (typeof sectionNumber !== 'undefined' && sectionNumber === constants.SECTION_NUMBER_ALL_PLACEHOLDER) {
        // See if we are toggling all sections.
        for (const sectionId in sectionBoxes) {
            for (let j = 0; j < sectionBoxes[sectionId].length; j++) {
                boxIds.push(sectionBoxes[sectionId][j].boxId);
            }
        }
    } else {
        // We select all boxes of the given section.
        sectionBoxes[sectionNumber].forEach(box => boxIds.push(box.boxId));
    }
    // Un/check the boxes.
    for (let i = 0; i < boxIds.length; i++) {
        document.getElementById(boxIds[i]).checked = value;
    }
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

    // Get the checked box IDs.
    for (let sectionNumber in sectionBoxes) {
        for (let i = 0; i < sectionBoxes[sectionNumber].length; i++) {
            const checkbox = document.getElementById(sectionBoxes[sectionNumber][i].boxId);
            if (checkbox.checked) {
                submitData.moduleIds.push(sectionBoxes[sectionNumber][i].moduleId);
            }
        }
    }

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
        case actions.MOVE_LEFT:
        case actions.MOVE_RIGHT:
            break;

        case actions.DELETE:
            // Confirm.
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
    Promise.resolve([Str.get_string('error', 'core'), errorText, Str.get_string('back', 'core')]).then(text => {
        require(['core/notification'], function(notification) {
            notification.alert(text[0], text[1], text[2]).then().catch();
        });
        return null;
    }).catch();
};

/**
 * This method rebuilds the data structure stored in 'sections'. This is neccessary whenever a drag'n'drop
 * operation is being done in the course which leads to a change of the section information. It calls a
 * webservice to retrieve the updated section (and modules) data.
 *
 * This method implements a mechanism to only send a single request at once. Multiple requests arriving while
 * the promise for the request hasn't been resolved yet are being ignored.
 *
 * @param {number} courseId the course id of the course to get the section information from the webservice
 */
const rebuildSections = (courseId) => {
    // Only rebuild if we're not yet trying to get the new data from the webservice.
    if (isRebuilding) {
        return;
    }

    isRebuilding = true;
    // Setting a hardcoded timeout like this is ugly, but due to a hardcoded timeout in the yui library
    // there probably is no better way to handle this.
    setTimeout(() => {
        const promises = Ajax.call(
            [
                {
                    methodname: 'block_massaction_get_sections',
                    args: {'courseId': courseId},
                },
                {
                    methodname: 'block_massaction_get_modulesinfo',
                    args: {'courseId': courseId},
                }
            ]);

        Promise.all([promises[0], promises[1]]).then(data => {
            // The array data[0] contains sections information, data[1] the module names.
            sectionBoxes = checkboxManager(data[0], data[1]);
            isRebuilding = false;
            return true;
        }).catch(() => {
            isRebuilding = false;
            return false;
        });
    }, 1000);
};

/**
 * Utility method to get an array of all descendent nodes of a given nodeList recursively.
 *
 * @param {NodeList} nodes The NodeList to convert to a flat array of nodes.
 * @return {[Node]} array of recursively found nodes.
 */
const getAllDescendants = (nodes) => {
    const allNodes = [];
    const getDescendants = (node) => {
        for (let i = 0; i < node.childNodes.length; i++) {
            const child = node.childNodes[i];
            getDescendants(child);
            allNodes.push(child);
        }
    };
    for (let j = 0; j < nodes.length; j++) {
        getDescendants(nodes[j]);
    }
    return allNodes;
};
