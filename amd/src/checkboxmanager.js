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
import {setSectionSelection} from './massactionblock';
import {cssIds, constants, usedMoodleCssClasses} from './massactionblock';

/* A registry of checkbox IDs, of the format:
 *  'section_number' => [{'moduleId'   : <module-ID>,
 *                       'boxId'       : <checkbox_id>}]
 */
const sectionBoxes = {};

/**
 * The checkbox manager takes a given 'sections' data structure object and inserts a checkbox for each of the given
 * course modules in this data object into the DOM.
 * The checkbox manager returns another data object containing the ids of the added checkboxes.
 *
 * @param {[]} sections the sections structure injected by the PHP method or the corresponding webservice call.
 * @param {[]} moduleNames array of module information objects: {modid: MODID, name: MOD_NAME}
 * @returns {{}} sectionBoxes a data structure containing the ids of the added checkboxes for the course modules.
 */
const checkboxmanager = (sections, moduleNames) => {
    // Filter available sections and modules.
    const sectionsUnfiltered = sections;
    sections = filterVisibleSections(sections);
    updateSelectionAndMoveToDropdowns(sections, sectionsUnfiltered);
    addCheckboxes(sections, moduleNames);
    return sectionBoxes;
};

/**
 * Add checkboxes to all sections.
 *
 * @param {[]} sections the sections data object
 * @param {[]} moduleNames array of module information objects: {modid: MODID, name: MOD_NAME}
 */
const addCheckboxes = (sections, moduleNames) => {
    sections.forEach(section => {
        sectionBoxes[section.number] = [];
        const moduleIds = section.modules.split(',');
        if (moduleIds && moduleIds.length > 0 && moduleIds[0] !== '') {
            const moduleNamesFiltered = moduleNames.filter(modinfo => moduleIds.includes(modinfo.modid.toString()));
            moduleNamesFiltered.forEach(modinfo => {
                addCheckboxToModule(section.number, modinfo.modid.toString(), modinfo.name);
            });
        }
    });
};


/**
 * Add a checkbox to a module element
 *
 * @param {number} sectionNumber number of the section of the current course module
 * @param {number} moduleId id of the current course module
 * @param {string} moduleName name of the course module specified by moduleId
 */
const addCheckboxToModule = (sectionNumber, moduleId, moduleName) => {
    const boxId = cssIds.BOX_ID_PREFIX + moduleId;
    const moduleElement = document.getElementById(usedMoodleCssClasses.MODULE_ID_PREFIX + moduleId);

    // Avoid creating duplicate checkboxes.
    if (document.getElementById(boxId) === null) {
        // Add the checkbox.
        const checkBoxElement = document.createElement('input');
        checkBoxElement.type = 'checkbox';
        checkBoxElement.className = cssIds.CHECKBOX_CLASS;
        checkBoxElement.id = boxId;

        if (moduleElement !== null) {
            const checkboxDescription = moduleName + constants.CHECKBOX_DESCRIPTION_SUFFIX;
            checkBoxElement.ariaLabel = checkboxDescription;
            checkBoxElement.name = checkboxDescription;
            // Finally add the created checkbox element.
            moduleElement.insertBefore(checkBoxElement, moduleElement.firstChild);
        }
    }

    // Add the newly created checkbox to our data structure.
    sectionBoxes[sectionNumber].push({
        'moduleId': moduleId,
        'boxId': boxId,
    });
};

/**
 * Filter the sections data object depending on the visibility of the course modules contained in
 * the data object. This is neccessary, because some course formats only show specific section(s)
 * in editing mode.
 *
 * @param {[]} sections the sections data object
 * @returns {[]} the filtered sections object
 */
const filterVisibleSections = (sections) => {
    // Filter all sections with modules which no checkboxes have been created for.
    // This case should only occur in course formats where some sections are hidden.
    return sections.filter(section => section.modules.split(',')
        .every(moduleid => document.getElementById(usedMoodleCssClasses.MODULE_ID_PREFIX + moduleid) !== null));
};

/**
 * Update the selection, moveto and duplicateto dropdowns of the massaction block according to the
 * previously filtered sections.
 *
 * @param {[]} sections the sections object filtered before by {@link filterVisibleSections}
 * @param {[]} sectionsUnfiltered the same data object as 'sections', but still containing all sections
 * no matter if containing modules or are visible in the current course format or not
 */
const updateSelectionAndMoveToDropdowns = (sections, sectionsUnfiltered) => {
    // Easy way to check if the name of a section or the order of sections have been changed.
    // If we have a change, we need to rebuild the dropdowns from templates.
    const sectionNamesInSelect =
        Array.prototype.map.call(document.getElementById(cssIds.SECTION_SELECT).options, option => option.text);
    // Remove placeholder (first option item in select), would disturb in the next comparison.
    sectionNamesInSelect.shift();
    const sectionsHaveChanged =
        JSON.stringify(sectionsUnfiltered.map(section => section.name)) !== JSON.stringify(sectionNamesInSelect);

    if (sectionsHaveChanged) {
        Templates.renderForPromise('block_massaction/section_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.SECTION_SELECT, html, js);
                disableInvisibleAndEmptySections(sections);
                // Re-register event listener.
                document.getElementById(cssIds.SECTION_SELECT).addEventListener('click',
                    (event) => setSectionSelection(true, event.target.value), false);
                return true;
            })
            .catch(ex => displayException(ex));

        Templates.renderForPromise('block_massaction/moveto_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.MOVETO_SELECT, html, js);
                return true;
            })
            .catch(ex => displayException(ex));

        Templates.renderForPromise('block_massaction/duplicateto_select', {'sections': sectionsUnfiltered})
            .then(({html, js}) => {
                Templates.replaceNode('#' + cssIds.DUPLICATETO_SELECT, html, js);
                return true;
            })
            .catch(ex => displayException(ex));
    } else {
        // Only disable invisible and empty sections without going through the whole rebuilding process first.
        disableInvisibleAndEmptySections(sections);
    }
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

export default checkboxmanager;
