YUI.add('moodle-availability_coaching_completion-form', function (Y, NAME) {

/**
 * JavaScript for form editing completion conditions.
 *
 * @module moodle-availability_coaching_completion-form
 */
M.availability_coaching_completion = M.availability_coaching_completion || {};

/**
 * @class M.availability_coaching_completion.form
 * @extends M.core_availability.plugin
 */
M.availability_coaching_completion.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} cms Array of objects containing cmid => name
 */
M.availability_coaching_completion.form.initInner = function(cms) {
    this.cms = cms;
};

M.availability_coaching_completion.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="col-form-label pr-3"> ' + M.util.get_string('title', 'availability_coaching_completion') + '</span>' +
        '<span class="availability-group form-group"><label>' +
        '<span class="accesshide">' + M.util.get_string('label_cm', 'availability_coaching_completion') + ' </span>' +
        '<select id="select_module" class="custom-select" name="cm" title="' + M.util.get_string('label_cm', 'availability_coaching_completion') + '">' +
        '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.cms.length; i++) {
        var cm = this.cms[i];
        // String has already been escaped using format_string.
        html += '<option value="' + cm.id + '">' + cm.name + '</option>';
    }

    html += '</select></label><label>' +
        '<span>' + M.util.get_string('label_cm', 'availability_coaching_completion') + ' </span>' +
        '<input id="percent_text" name="percent" type="hidden" oninput=" if (this.value > 100){\n' +
        '                this.value = this.value.slice(0, 2);\n' +
        '            } else if (this.value < 0) {\n' +
        '                this.value = 0;\n' +
        '            }" value="0" maxlength="3" min="0" max="100">'
    '</label></span>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    const script_tag = document.createElement('script');
    script_tag.innerHTML =
        "if(typeof init_select === 'undefined'){" +
        "   function init_select() {\n" +
        "   let modules_select = document.getElementById('select_module');\n" +
        "   let percent_input = document.getElementById('percent_text');\n" +
        "   modules_select.addEventListener('change', (e) => {\n" +
        "       if (modules_select.value != -2){\n" +
        "           if (percent_input.getAttribute('type') != 'hidden'){\n" +
        "               percent_input.setAttribute('type', 'hidden');\n" +
        "            }\n" +
        "           return;\n" +
        "           }\n" +
        "\n" +
        "       percent_input.setAttribute('type', 'number');\n" +
        "   });" +
        "}}\n" +
        "init_select();";
    node.appendChild(script_tag);

    // Set initial values.
    if (json.cm !== undefined && node.one('select[name=cm] > option[value=' + json.cm + ']')) {
        node.one('select[name=cm]').set('value', '' + json.cm);
    }

    if (json.cm === -2) {
        if(typeof json.percent === 'undefined' || json.percent === null){
            json.percent = 0;
        }
        node.one('#percent_text').set('value', '' + json.percent);
        node.one('#percent_text').set('type', 'number');
    }

    // Add event handlers (first time only).
    if (!M.availability_coaching_completion.form.addedEvents) {
        M.availability_coaching_completion.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_coaching_completion select, .availability_coaching_completion input[name="percent"]');
    }

    return node;
};

M.availability_coaching_completion.form.fillValue = function(value, node) {
    value.cm = parseInt(node.one('select[name=cm]').get('value'), 10);
    value.percent = parseInt(node.one('#percent_text').get('value'), 10);
};

M.availability_coaching_completion.form.fillErrors = function(errors, node) {
    var cmid = parseInt(node.one('select[name=cm]').get('value'), 10);
    if (cmid === 0) {
        errors.push('availability_coaching_completion:error_selectcmid');
    }

    if (cmid === -2){
        var percent = parseInt(node.one('#percent_text').get('value'), 10);
        if (percent < 0 || percent > 100){
            errors.push('availability_coaching_completion:error_inputted_percent');
        }
    }
};

}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
