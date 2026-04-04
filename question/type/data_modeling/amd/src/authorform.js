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
 * JavaScript for handling UI actions in the question authoring form.
 *
 * @module qtype_sqlrunner/authorform
 * @copyright  Richard Lobb, 2015, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'qtype_sqlrunner/userinterfacewrapper'], function($, ui) {
    /**
     * Set up the author edit form UI plugins and event handlers.
     * The template parameters and Ace language are passed to each
     * text area from PHP by setting its data-params and
     * data-lang attributes.
     */
    function initEditForm() {
        /**
         * Set up the UI controller for a given textarea (one of template,
         * answer or answerpreload).
         * We don't attempt to process changes in template parameters,
         * as these need to be merged with those of the prototype. But we do handle
         * changes in the language.
         * @param {string} taId The ID of the textarea element.
         * @param {string} uiname The name of the UI controller (may be empty or none).
         */
        function setUi(taId, uiname) {
            var ta = $(document.getElementById(taId)),  // The jquery text area element(s).
                lang,
                currentLang = ta.attr('data-lang'),     // Language set by PHP.
                paramsJson = ta.attr('data-params'),    // Ui params set by PHP.
                params = {},
                uiWrapper;

            // Set data attributes in the text area for UI components that need
            // global extra or testcase data (e.g. gapfiller UI).
            try {
                params = JSON.parse(paramsJson);
            } catch(err) {}
            uiname = uiname.toLowerCase();
            if (uiname === 'none') {
                uiname = '';
            }
            lang = currentLang;

            uiWrapper = ta.data('current-ui-wrapper'); // Currently-active UI wrapper on this ta.
            if (uiWrapper && uiWrapper.uiname === uiname) {
                return; // We already have what we want - give up.
            }

            ta.attr('data-lang', lang);

            if (!uiWrapper) {
                uiWrapper = new ui.InterfaceWrapper(uiname, taId);
            } else {
                // Wrapper has already been set up - just reload the reqd UI.
                params.lang = lang;
                uiWrapper.loadUi(uiname, params);
            }
        }

        /**
         * Set the correct Ui controller on both the sample answer and the answer preload.
         * As a special case, we don't turn on the Ui controller in the answer
         * and answer preload fields when using Html-Ui and the ui-parameter
         * enable_in_editor is false.
         */
        function setUis() {
            setUi('id_answer', 'ace');
        }

        /*************************************************************
         *
         * Body of initEditFormWhenReady starts here.
         *
         *************************************************************/
        // In order to initialise the Ui plugin when the answer preload section is
        // expanded, we monitor attribute mutations in the Answer Preload
        // header.
        setUis();
    }
    return {initEditForm: initEditForm};
});