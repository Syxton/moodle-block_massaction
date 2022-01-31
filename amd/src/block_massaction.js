/* jshint ignore:start */
define(['jquery', 'core/str'], function($, corestr) {

    return {
        init: function(data) {
            /*
             * We're importing a two-dimensional array of data, including section numbers and their
             * module/activity ids, section numbers and their human-readable labels, and the course
             * format. The array has this structure:
             *
             *  [
             *   courseformat: "topics",
             *   sectionmodules: {0: ["1", "2"], 1: ["3"], 2: ["4", "5", "6"], ...},
             *   sectionnames: ["Main", "Topic 1", "Topic 2", "Topic 3", ...]
             *  ]
             *
             * The "sectionmodules" array will only contain sections that have modules/activities.
             * Any sections that do not have any modules/activities will not be present in this
             * array. Having this information makes us much less dependent upon the DOM.
             *
             * The "sectionnames" array contains the labels for all sections in the course, without
             * regard for whether those sections have modules/activities. This is primarily used to
             * populate the three drop menus. This is not an explicitly associative array, but the
             * section labels are added in the order in which they display on the screen.
             *
             * Moodle always numbers the sections from zero to N. It's only the module id numbers that
             * will vary. In addition, the sections and their activities are always in the order in
             * which they appear in the browser. This is true even if the third section/topic/week/etc
             * is moved to be in, say, the second position; its id in these arrays will change from 2 to 1,
             * while the other section/week/topic/etc id will change from 1 to 2. The code uses sectionId
             * to refer to the section number (display order number) and should not be confused with the
             * section's id in the database.
             */
            this.data = data;

            // Draw the checkboxes next to the activities.
            this.drawCheckboxes(this.data);

            // Populate the drop menus.
            this.populateMenus(this.data);

            // Assign handlers.
            $('#block-massaction-selectall').on('click', this.data, this.selectAllHandler);
            $('#block-massaction-selectsome').on('change', this.data, this.selectAllHandler);
            $('#block-massaction-selectnone').on('click', this.data, this.selectAllHandler);

            $('#block-massaction-outdent').on('click', this.data, this.actionHandler);
            $('#block-massaction-indent').on('click', this.data, this.actionHandler);

            $('#block-massaction-hide').on('click', this.data, this.actionHandler);
            $('#block-massaction-show').on('click', this.data, this.actionHandler);

            $('#block-massaction-delete').on('click', this.data, this.actionHandler);

            $('#block-massaction-move').on('change', this.data, this.actionHandler);

            $('#block-massaction-clone').on('change', this.data, this.actionHandler);
        },
        drawCheckboxes: function(data) {
            var courseActivities = '';
            var inputControl = '';
            var jQueryIdentifier = '';
            var moduleId = 0;
            var moduleKey = 0;
            var sectionId = 0;

            // Iterate through our sections and their activities, drawing checkboxes for each activity.
            for (sectionId in data.sectionmodules) {
                /*
                 * Also check if the section exists in the DOM so we don't attempt to draw checkboxes
                 * for activities that do not exist in the DOM.
                 */
                if (sectionId !== null && $('#section-' + sectionId).length !== 0) {
                    /*
                     * We need the spans that house the edit controls in order to append our checkboxes
                     * to them later.
                     */
                    jQueryIdentifier = sectionId + ' ul.section li.activity ' + 'div.mod-indent-outer div';
                    courseActivities = $('#section-' + jQueryIdentifier).children('span.actions');

                    for (moduleKey in data.sectionmodules[sectionId]) {
                        moduleId = data.sectionmodules[sectionId][moduleKey];
                        inputControl = document.createElement('input');
                        inputControl.type = 'checkbox';
                        inputControl.id = 'massaction-input-' + moduleId;
                        inputControl.className = 'massaction-checkbox';

                        courseActivities[moduleKey].appendChild(inputControl);
                    }
                }
            }
        },
        populateMenus: function(data) {
            var dropMenus = ['block-massaction-selectsome',
                             'block-massaction-move',
                             'block-massaction-clone'];
            var menuId = 0;
            var menuItem = '';
            var sectionId = 0;

            // This loop creates and appends all the options to the three drop menus.
            for (sectionId in data.sectionnames) {
                for (menuId in dropMenus) {
                    menuItem = document.createElement('option');
                    menuItem.text = data.sectionnames[sectionId];
                    menuItem.value = sectionId;

                    /*
                     * We are disabling menu options in the "Select all in section" menu for
                     * sections that do not currently have any modules available to select.
                     */
                    if (dropMenus[menuId] === 'block-massaction-selectsome') {
                        if ((sectionId in data.sectionmodules)) {
                            menuItem.disabled = false;
                        } else {
                            menuItem.disabled = true;
                        }
                    } else {
                        // We aren't disabling any options in the other two menus.
                        menuItem.disabled = false;
                    }

                    $('#' + dropMenus[menuId]).append(menuItem);
                }
            }
        },
        selectAllHandler: function(eventData) {
            // Defaults. Assume we have a bad target and plan to do nothing.
            var checkAll = false;
            var checkNone = false;
            var checkSome = false;
            var sectionId = '';
            var moduleId = '';
            var moduleKey = '';

            /*
             * There is no default case in this switch because it is simply unnecessary. If
             * the value of eventData.currentTarget.id has been hacked and does not match
             * any of the three articulated cases, then we do not want to take any action, anyway.
             * Since that is accomplished both with and without the default case, I have opted
             * to omit the default case.
             */
            switch (eventData.currentTarget.id) {
                case 'block-massaction-selectsome':
                    checkSome = true;
                    // Falls through.

                case 'block-massaction-selectnone':
                    checkNone = true;
                    /*
                     * They clicked "Deselect all" or "Select all in section", which means that if they
                     * previously clicked "Select all", then they have over-ridden that choice.
                     * Set this input's value to false to track that change.
                     */
                    $('#block-massaction-selected-all').val('false');
                    // Falls through.

                case 'block-massaction-selectall':
                    if (!checkNone) {
                        checkAll = true;
                        $('#block-massaction-selected-all').val('true');
                    }

                    // Proceed to un|check all the boxes, as appropriate.
                    for (sectionId in eventData.data.sectionmodules) {
                        for (moduleKey in eventData.data.sectionmodules[sectionId]) {
                            moduleId = eventData.data.sectionmodules[sectionId][moduleKey];

                            /*
                             * Make sure this module exists in the DOM. No point setting state
                             * on a non-existent input.
                             */
                            if ($('#massaction-input-' + moduleId).length > 0) {
                                $('#massaction-input-' + moduleId).prop('checked', checkAll);
                            }
                        }
                    }

                    if (checkSome === true) {
                        sectionId = eventData.target.value;

                        for (moduleKey in eventData.data.sectionmodules[sectionId]) {
                            moduleId = eventData.data.sectionmodules[sectionId][moduleKey];

                                /*
                                 * Make sure this module exists in the DOM. No point setting state
                                 * on a non-existent input.
                                 */
                            if ($('#massaction-input-' + moduleId).length > 0) {
                                $('#massaction-input-' + moduleId).prop('checked', true);
                            }
                        }
                    } else {
                        /*
                         * The user did not select all in a section. So we have to set this
                         * menu's value back to 'all' in case they had previously made a menu
                         * selection and are now clicking 'Select all' or 'Deselect all'.
                         *
                         * This is critical for correct operation of the block later!
                         */
                        $('#block-massaction-selectsome').val('all');
                    }
                    break;
            }
        },
        actionHandler: function(eventData) {
            var activities = new Array();
            var activeTabId = 0;
            var courseFormat = eventData.data.courseformat;
            var moduleId = '';
            var moduleKey = null;
            var numberOfActivities = 0;
            var sectionId = $('#block-massaction-selectsome').val();
            var target = '';

            /*
             * When a course uses the OneTopic format and the user clicks 'Select all' or chooses
             * an option from the "Select all in section" menu, then we will need to know which
             * section was active. This enables us to determine whether they manually deselected
             * any of the modules/activities displayed on the screen. We don't have to perform this
             * check for any other section when the user has clicked 'Select all' because those
             * sections' modules/activities are not present in the DOM and therefore could not be
             * be manually deselected.
             */
            if (courseFormat === 'onetopic') {
                var activeTab = $('li.active:eq(0)');
                var textContent = activeTab[0].textContent;
                var activeSection = $("li[aria-label='" + textContent + "']").attr('id');
                activeSection = activeSection.split('-');
                activeTabId = activeSection[1];
            }

            $('#block-massaction-selected-section').val(sectionId);

            // Find out what the user wants to do.
            var actionTarget = eventData.currentTarget.id.split('-');
            var action = actionTarget[actionTarget.length - 1];

            switch (sectionId) {
                case 'all':
                    // The user did not select all in a section. Instead, the user
                    // either clicked 'Select All' or manually selected modules.
                    for (sectionId in eventData.data.sectionmodules) {
                        for (moduleKey in eventData.data.sectionmodules[sectionId]) {
                            moduleId = eventData.data.sectionmodules[sectionId][moduleKey];

                            if (((courseFormat === 'onetopic' && activeTabId === sectionId) ||
                                 courseFormat !== 'onetopic') &&
                                 $('#massaction-input-' + moduleId).length > 0 &&
                                 $('#massaction-input-' + moduleId).is(':checked')) {

                                activities.push(moduleId);
                            } else if ($('#block-massaction-selected-all').val() === 'true' &&
                                       courseFormat === 'onetopic' && activeTabId !== sectionId) {
                                activities.push(moduleId);
                            }
                        }
                    }
                    break;

                default:
                    // They selected all in a section.
                    for (moduleKey in eventData.data.sectionmodules[sectionId]) {
                        /*
                         * Ensure the activity is actually checked because it is possible they selected
                         * all in a section, then changed their minds and manually deselected one or more.
                         * Assuming they are all checked in the selected section would mean that, in such
                         * a situation, the user would have to manually change ther selection in the drop
                         * menu, as well.
                         */
                        moduleId = eventData.data.sectionmodules[sectionId][moduleKey];

                        if (((courseFormat === 'onetopic' && activeTabId === sectionId) ||
                             courseFormat !== 'onetopic') &&
                             $('#massaction-input-' + moduleId).length > 0 &&
                             $('#massaction-input-' + moduleId).is(':checked')) {

                            activities.push(moduleId);
                        } else if (courseFormat === 'onetopic' && activeTabId !== sectionId) {
                            activities.push(moduleId);
                        }
                    }
            }

            numberOfActivities = activities.length;

            /*
             * The default case for this switch has been omitted because if the value of action
             * is not one of the three enumerated in the switch, then there is nothing special
             * we need to do.
             */
            switch (action) {
                case 'delete':
                    if (numberOfActivities > 0) {
                        var confirmDelete = corestr.get_string('confirmation',
                                                               'block_massaction',
                                                               numberOfActivities);

                        $.when(confirmDelete).done(function(confirmDelete) {
                            if (!window.confirm(confirmDelete)) {
                                return false;
                            } else {
                                return true;
                            }
                        });
                    }
                    break;

                case 'move':
                case 'clone':
                    target = $('#block-massaction-' + action).val();
                    break;
            }

            $('#block-massaction-action').val(action);
            $('#block-massaction-activities').val(JSON.stringify(activities));
            $('#block-massaction-target').val(target);

            if (numberOfActivities > 0) {
                $('#block-massaction-control-form').submit();
            } else {
                var nothingSelected = corestr.get_string('noitemselected', 'block_massaction');
                $.when(nothingSelected).done(function(alertString) {
                    window.alert(alertString);
                });
            }
        }
    };
});
/* jshint ignore:end */
