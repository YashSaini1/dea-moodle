/**
 * JavaScript for form editing sql_premium conditions.
 *
 * @module moodle-availability_sql_premium-form
 */
M.availability_sql_premium = M.availability_sql_premium || {};

/**
 * @class M.availability_sql_premium.form
 * @extends M.core_availability.plugin
 */
M.availability_sql_premium.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 */
M.availability_sql_premium.form.initInner = function() {};

M.availability_sql_premium.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="col-form-label pr-3"> ' + M.util.get_string('have_access_desc', 'availability_sql_premium') + '</span>' +
        ' <span class="availability-group form-group"><label>' +
        '<span class="accesshide">' + M.util.get_string('label_cm', 'availability_sql_premium') + ' </span>' +
        '<input type="hidden" value="1" name="access_must">' +
        '</label></span>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values.
    if (json.access_must !== undefined && node.one('input[name=access_must]')) {
        node.one('input[name="access_must"]').set('value', '' + json.access_must);
    }

    // Add event handlers (first time only).
    if (!M.availability_sql_premium.form.addedEvents) {
        M.availability_sql_premium.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_sql_premium select');
    }

    return node;
};

M.availability_sql_premium.form.fillValue = function(value, node) {
    value.access_must = parseInt(node.one('input[name=access_must]').get('value'), 10);
};

M.availability_sql_premium.form.fillErrors = function(errors, node) {
    var access_must = parseInt(node.one('input[name=access_must]').get('value'), 10);
    if (access_must < 0 && access_must > 1) {
        errors.push('availability_sql_premium:error_select_access_must');
    }
};