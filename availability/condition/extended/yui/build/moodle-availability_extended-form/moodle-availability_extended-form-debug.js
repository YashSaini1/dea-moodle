YUI.add('moodle-availability_extended-form', function (Y, NAME) {

    /**
     * JavaScript for form editing extended conditions.
     *
     * @module moodle-availability_extended-form
     */
    M.availability_extended = M.availability_extended || {};

    /**
     * @class M.availability_extended.form
     * @extends M.core_availability.plugin
     */
    M.availability_extended.form = Y.Object(M.core_availability.plugin);

    /**
     * Initialises this plugin.
     *
     * @method initInner
     */
    M.availability_extended.form.initInner = function() {};

    M.availability_extended.form.getNode = function(json) {
        var html = '<span class="col-form-label pr-3"> ' + M.util.get_string('have_access_desc', 'availability_extended') + '</span>' +
            ' <span class="availability-group form-group"><label>' +
            '<span class="accesshide">' + M.util.get_string('label_cm', 'availability_extended') + ' </span>' +
            '<input type="hidden" value="1" name="access_must">' +
            '</label></span>';
        var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

        // Set initial values.
        if (json.access_must !== undefined && node.one('input[name=access_must]')) {
            node.one('input[name="access_must"]').set('value', '' + json.access_must);
        }

        // Add event handlers (first time only).
        if (!M.availability_extended.form.addedEvents) {
            M.availability_extended.form.addedEvents = true;
            var root = Y.one('.availability-field');
            root.delegate('change', function() {
                // Whichever dropdown changed, just update the form.
                M.core_availability.form.update();
            }, '.availability_extended select');
        }

        return node;
    };

    M.availability_extended.form.fillValue = function(value, node) {
        value.access_must = parseInt(node.one('input[name=access_must]').get('value'), 10);
    };

    M.availability_extended.form.fillErrors = function(errors, node) {
        var access_must = parseInt(node.one('input[name=access_must]').get('value'), 10);
        if (access_must < 0 && access_must > 1) {
            errors.push('availability_extended:error_select_access_must');
        }
    };

}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
