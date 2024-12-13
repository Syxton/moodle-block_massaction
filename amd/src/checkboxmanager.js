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
 * Checkbox manager amd module: Adds checkboxes to the activities for selecting and
 * generates a data structure of the activities and checkboxes.
 *
 * @module     block_massaction/checkboxmanager
 * @copyright  2022 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import {exception as displayException} from 'core/notification';
import {cssIds, constants, usedMoodleCssClasses} from './massactionblock';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import events from 'core_course/events';

let localStateUpdating = false;
let sectionsChanged = false;
let sections = [];
let moduleNames = [];

/* A registry of checkbox IDs, of the format:
 *  'section_number' => [{'moduleId'   : <module-ID>,
 *                       'boxId'       : <checkbox_id>}]
 */
const sectionBoxes = {};

/**
 * The checkbox manager takes a given 'sections' data structure object and inserts a checkbox for each of the given
 * course modules in this data object into the DOM.
 * The checkbox manager returns another data object containing the ids of the added checkboxes.
 */
export const initCheckboxManager = () => {
    const courseEditor = getCurrentCourseEditor();

    const eventsToListen = {
        SECTION_UPDATED: 'section:updated',
        CHANGE_FINISHED: 'transaction:end'
    };

    courseEditor.stateManager.target.addEventListener(events.stateChanged, (event) => {
        if (event.detail.action === eventsToListen.SECTION_UPDATED) {
            // Listen to section updated events. We do not want to immediately react to the event, but wait for
            // everything to finish updating.
            sectionsChanged = true;
        }
        if (event.detail.action === eventsToListen.CHANGE_FINISHED) {
            // Before every change to the state there is a transaction:start event. After the change is being commited,
            // we receive an transaction:end event. That is the point we want to react to changes of the state.
            rebuildLocalState();
        }
    });
    // Trigger rendering of sections dropdowns a first time.
    sectionsChanged = true;
    // Get initial state.
    rebuildLocalState();
};

/**
 * This method rebuilds the local state maintained in this module based on the course editor state.
 *
 * It will be called whenever a change to the courseeditor state is being detected.
 */
const rebuildLocalState = () => {
    if (localStateUpdating) {
        return;
    }
    localStateUpdating = true;

    // First we rebuild our data structures depending on the course editor state.
    for (const prop of Object.getOwnPropertyNames(sectionBoxes)) {
        delete sectionBoxes[prop];
    }

    const courseEditor = getCurrentCourseEditor();
    const state = courseEditor.stateManager.state;
    const exporter = courseEditor.getExporter();

    // Get all modules, sections and subsections in display order.
    const courseItems = exporter.allItemsArray(state);

    // Build sections array.
    sections = [];
    courseItems.forEach(item => {
        if (item.type === 'section') {
            // Get section info.
            let sectioninfo = {...state.section.get(item.id)};
            // Rename subsections for display purposes.
            sectioninfo.title = getTitleOfSection(sectioninfo);
            sections.push(sectioninfo);
        }
    });

    // Get all module names and parameters.
    moduleNames = [...courseEditor.stateManager.state.cm.values()];

    // Now we use the new information to rebuild dropdowns and re-apply checkboxes.
    const sectionsUnfiltered = sections;
    sections = filterVisibleSections(sections);
    updateSelectionAndMoveToDropdowns(sections, sectionsUnfiltered);
    addCheckboxesToDataStructure();
    localStateUpdating = false;
};

/**
 * Returns the title of a given section.
 * If the section is a subsection, a prefix with a dash is added.
 *
 * @param {Object} section the section object from the course editor
 * @returns {string} title of the section object corrected for subsections
 */
export const getTitleOfSection = (section) => {
    let title = section.title;
    if (section.component === 'mod_subsection') {
        title = ' - ' + title;
    }
    return title;
};

/**
 * Returns the currently selected module ids.
 *
 * @returns {[]} Array of module ids currently being selected
 */
export const getSelectedModIds = () => {
    const moduleIds = [];
    for (let sectionNumber in sectionBoxes) {
        for (let i = 0; i < sectionBoxes[sectionNumber].length; i++) {
            const checkbox = document.getElementById(sectionBoxes[sectionNumber][i].boxId);
            if (checkbox.checked) {
                moduleIds.push(sectionBoxes[sectionNumber][i].moduleId);
            }
        }
    }

    return moduleIds;
};

/**
 * Select all module checkboxes in section(s).
 *
 * @param {boolean} value the checked value to set the checkboxes to
 * @param {string} sectionNumber the section number of the section which all modules should be checked/unchecked. Use "all" to
 *  select/deselect modules in all sections.
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
    // Reset dropdown to standard placeholder so we trigger a change event when selecting a section, then deselecting
    // everything and again select the same section.
    document.getElementById(cssIds.SECTION_SELECT).value = constants.SECTION_SELECT_DESCRIPTION_VALUE;
};

/**
 * Scan all available checkboxes and add them to the data structure.
 */
const addCheckboxesToDataStructure = () => {
    sections.forEach(section => {
        sectionBoxes[section.number] = [];
        const moduleIds = section.cmlist;

        if (moduleIds && moduleIds.length > 0 && moduleIds[0] !== '') {
            const moduleNamesFiltered = moduleNames.filter(modinfo => moduleIds.includes(modinfo.id.toString()));
            moduleNamesFiltered.forEach(modinfo => {
                if (modinfo.module !== 'subsection') {
                    // Checkbox should already be created by moodle massactions. Just add it to our data structure.
                    const boxId = usedMoodleCssClasses.BOX_ID_PREFIX + modinfo.id.toString();
                    sectionBoxes[section.number].push({
                        'moduleId': modinfo.id.toString(),
                        'boxId': boxId,
                    });
                }
            });
        }
    });
};

/**
 * Filter the sections data object depending on the visibility of the course modules contained in
 * the data object. This is necessary, because some course formats only show specific section(s)
 * in editing mode.
 *
 * @param {[]} sections the sections data object
 * @returns {[]} the filtered sections object
 */
const filterVisibleSections = (sections) => {
    // Filter all sections with modules which no checkboxes have been created for.
    // This case should only occur in course formats where some sections are hidden.
    return sections.filter(section => section.cmlist.length !== 0)
        .filter(section => section.cmlist
            .every(moduleid => document.getElementById(usedMoodleCssClasses.MODULE_ID_PREFIX + moduleid) !== null));
};

/**
 * Update the selection, moveto and duplicateto dropdowns of the massaction block according to the
 * previously filtered sections.
 *
 * This method also has to be called whenever there is a module change event (moving around, adding file by Drag&Drop etc.).
 *
 * @param {[]} sections the sections object filtered before by {@link filterVisibleSections}
 * @param {[]} sectionsUnfiltered the same data object as 'sections', but still containing all sections
 */
const updateSelectionAndMoveToDropdowns = (sections, sectionsUnfiltered) => {
    if (sectionsChanged) {
        Templates.renderForPromise('block_massaction/section_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.SECTION_SELECT, html, js);
                disableInvisibleAndEmptySections(sections);
                // Re-register event listener.
                document.getElementById(cssIds.SECTION_SELECT).addEventListener('change',
                    (event) => setSectionSelection(true, event.target.value), false);
                return true;
            })
            .catch(ex => displayException(ex));

        Templates.renderForPromise('block_massaction/moveto_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.MOVETO_SELECT, html, js);
                disableUnavailableSections(cssIds.MOVETO_SELECT);
                return true;
            })
            .catch(ex => displayException(ex));

        Templates.renderForPromise('block_massaction/duplicateto_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.DUPLICATETO_SELECT, html, js);
                disableUnavailableSections(cssIds.DUPLICATETO_SELECT);
                return true;
            })
            .catch(ex => displayException(ex));
    } else {
        // If there has not been an event about a section change we do not have to rebuild the sections dropdowns.
        // However, there is a chance a section is being emptied or not empty anymore due to drag&dropping of modules.
        // So we have to recalculate if we have to enable/disable the sections.
        disableInvisibleAndEmptySections(sections);
    }
    // Reset the flag.
    sectionsChanged = false;
};

/**
 * Sets the disabled/enabled status of sections in the section select dropdown:
 * Enabled if section is visible and contains modules.
 * Disabled if section is not visible or doesn't contain any modules.
 *
 * @param {[]} sections the section data structure
 */
const disableInvisibleAndEmptySections = (sections) => {
    Array.prototype.forEach.call(document.getElementById(cssIds.SECTION_SELECT).options, option => {
        // Disable every element which doesn't have a visible section, except the placeholder ('description').
        if (option.value !== constants.SECTION_SELECT_DESCRIPTION_VALUE
                && !sections.some(section => parseInt(option.value) === section.number)) {
            option.disabled = true;
        } else {
            option.disabled = false;
        }
    });
};

/**
 * Sets the disabled/enabled status of sections in the section select dropdown:
 * Disabled if the section is not available due to some restrictions in block_massaction itself (provided by hooks).
 *
 * @param {string} elementId elementId to apply the restriction
 */
const disableUnavailableSections = (elementId) => {
    if (document.getElementById(elementId) !== null) {
        const sectionsAvailableInfo = document.querySelector(cssIds.SECTION_FILTER_DATA).dataset.availabletargetsections;
        const sectionsAvailable = Array.prototype.map.call(sectionsAvailableInfo.split(','), (sectionnum) => parseInt(sectionnum));
        Array.prototype.forEach.call(document.getElementById(elementId).options, option => {
            // Disable every element which is not in the sectionsAvailable list.
            if (sectionsAvailable.includes(parseInt(option.value))) {
                option.disabled = false;
            } else {
                option.disabled = true;
            }
        });
    }
};
