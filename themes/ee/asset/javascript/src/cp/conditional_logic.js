/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

var Conditional = window.Conditional = {

    // Event handlers stored here, direct access outside only from
    // Conditional.Publish class
    _eventHandlers: [],

    /**
     * Binds an event to a fieldtype
     *
     * Available events are:
     * 'display' - When a row is displayed
     * 'remove' - When a row is deleted
     *
     * @param	{string}	fieldtypeName	Class name of fieldtype so the
     *				correct cell object can be passed to the handler
     * @param	{string}	action			Name of action
     * @param	{func}		func			Callback function for event
     */
    on: function(fieldtypeName, action, func) {
        if (this._eventHandlers[action] == undefined) {
            this._eventHandlers[action] = [];
        }

        // Each fieldtype gets one method per handler
        this._eventHandlers[action][fieldtypeName] = func;
    }
};

/**
 * Conditional Publish class
 *
 * @param	{string}	field		Selector of table to instantiate as a Conditional
 */
Conditional.Publish = function(field, settings) {
    this.root = $(field);
    this.mainParentContainer = this.root.parents('#fieldset-condition_fields');
    if (this.mainParentContainer.length == 0) return;
    this.blankSet = $('.conditionset-temlates-row', this.mainParentContainer);
    this.activeSet = this.root.not(this.blankSet);
    this.setParent = $('#fieldset-condition_fields').find('.field-conditionset-wrapper');
    this.blankRow = $('.rule-blank-row', this.activeSet);
    this.rowContainer = this.activeSet.find('.rules');
    this.addButtonToolbar = $('[rel=add_row]', this.activeSet);
    this.deleteButtonsSelector = '.delete_rule [rel=remove_row]';
    this.deleteSetButtonsSelector = '.remove-set';
    this.rowSelector = '.rule';
    this.cellSelector = '.rule > div';

    this.init();

    this.eventHandlers = [];
}

Conditional.Publish.prototype = {

    init: function() {
        // Store the original row count so we can properly increment new
        // row placeholder IDs in _addRow()
        this.original_row_count = this._getRowsInSet().length;
        this.original_set_count = this._getSets().length;

        if (!Object.keys(EE.conditionData).length || (typeof EE.conditionData == 'string' && !Object.keys(JSON.parse(EE.conditionData)).length)) {
            this._firstCloneSet();
        }

        if (Object.keys(EE.conditionData).length || this.original_set_count == 1) {
            this._bindAddSetButton();
        }

        this._showHideDeleteBtns();

        this._bindDeleteSetButton();
        this._bindDeleteButton();
        this._bindAddButton();

        this._checkHiddenEl();

        // Disable input elements in our blank template container so they
        // don't get submitted on form submission
        this.blankRow.find(':input').attr('disabled', 'disabled');
    },

    _getRowsInSet: function() {
        return this.rowContainer.children(this.rowSelector).not(this.blankRow);
    },

    _getSets: function() {
        return this.mainParentContainer.find(this.activeSet);
    },

    _checkHiddenEl: function() {
        var notEmptySet = $('#fieldset-condition_fields .field-conditionset-wrapper').find('.conditionset-item');
        var hiddenInput = notEmptySet.find('.rule.hidden');
        var hiddenMatchInput = $('#fieldset-condition_fields .conditionset-item.hidden').find('.match-react-element');

        $.each(hiddenInput, function(key, value) {

            // check if input in hidden container was init and have attr disable
            var timer = setInterval(function() {
                if ($(value).find('input').prop('disabled')) {
                    clearInterval(timer);
                } else {
                    $(value).find('input').attr('disabled', 'disabled');
                }
            },50);
        });

        $.each(hiddenMatchInput, function(key, value) {

            // check if input in hidden container was init and have attr disable
            var timer = setInterval(function() {
                if ($(value).find('input').prop('disabled')) {
                    clearInterval(timer);
                } else {
                    $(value).find('input').attr('disabled', 'disabled');
                }
            },50);
        });
    },

    _showHideDeleteBtns: function() {
        var that = this;
        var setsNotHidden = $('.field-conditionset-wrapper .conditionset-item:not(.hidden)');

        if (setsNotHidden.length > 1) {
            setsNotHidden.each(function() {
                $(this).find('.remove-set').show();
            });
        }

        setsNotHidden.each(function() {
            var rowsNotHidden = $(this).find('.rule:not(.hidden)');

            if (rowsNotHidden.length > 1) {
                rowsNotHidden.each(function() {
                    var el = $(this);
                    el.find('.delete_rule button').prop('disabled', false);
                    el.find('.delete_rule').show();
                });
            } else {
                rowsNotHidden.find('.delete_rule').hide();
            }
        });
    },

    /**
     * Add and Binds click listener to Add / Delete ROW buttons to add/delete the rows
     */
    _addRuleRow: function(cloneElementParent) {
        var that = this;

        // Clone our blank row
        var el = cloneElementParent.find('.rule-blank-row').clone();

        el.removeClass('rule-blank-row');
        el.removeClass('hidden');

        // Increment namespacing on inputs
        this.original_row_count++;

        el.html(
            el.html().replace(
                RegExp('new_rule_row_[0-9]{1,}', 'g'),
                'new_rule_row_' + this.original_row_count
            )
        );

        el.html(
            el.html().replace(
                RegExp('new_row_[0-9]{1,}', 'g'),
                'new_row_' + this.original_row_count
            )
        );

        // Add the new row ID to the field data
        $('> '+this.cellSelector, el).attr(
            'data-new-rule-row-id',
            'new_rule_row_' + this.original_row_count
        );

        // Append the row to the end of the row container
        cloneElementParent.find('.rules').append(el);

        if ($(cloneElementParent).find('.rule:not(.hidden)').length > 1) {
            $(cloneElementParent).find('.rule:not(.hidden) [rel="remove_row"]').show();
            $(cloneElementParent).find('.rule:not(.hidden) [rel="remove_row"]').prop('disabled', false);
        } else {
            $(cloneElementParent).find('.rule:not(.hidden) [rel="remove_row"]').hide();
        }

        that._checkHiddenEl();
        that._showHideDeleteBtns();

        // Bind the new row's inputs to AJAX form validation
        if (EE.cp && EE.cp.formValidation !== undefined) {
            EE.cp.formValidation.bindInputs(el);
        }

        return el;
    },

    _bindAddButton: function() {
        var that = this;

        // Remove any existing click listeners the body has registered for this element
        // this is necessary for AJAX modals that may be opened and closed many times
        $('body').off('click', '.condition-btn');
        $('body').on('click', '.condition-btn', function(event) {
            var cloneElementParent = $(this).parents('.field-conditionset');
            event.preventDefault();
            that._addRuleRow(cloneElementParent);
            Dropdown.renderFields();
        });
    },

    _bindDeleteButton: function() {
        var that = this;

        $('body').on('click', that.deleteButtonsSelector, function(event) {
            event.preventDefault();

            var row = $(this).parents('.rule');
            var rowParent = row.parents('.rules');
            // Remove the row
            row.remove();

            if (rowParent.find('.rule:not(.hidden)').length == 1) {
                rowParent.find('.rule:not(.hidden)').find('[rel="remove_row"]').hide();
            }

            // Mark Conditional Row field as valid if all rows with invalid cells are cleared
            if ($('div.invalid', that.root).length == 0 &&
                EE.cp &&
                EE.cp.formValidation !== undefined) {
                EE.cp.formValidation.markFieldValid($('input, select, textarea', that.blankRow).eq(0));
            }
        });
    },

    /**
     * Add and Binds click listener to Add / Delete Set buttons to add/delete the set
     */

    _addSetBlock: function() {
        // Clone our blank row
        var that = this

        var set = this.blankSet.clone();

        set.removeClass('conditionset-temlates-row');
        set.removeClass('hidden');

        // Increment namespacing on inputs
        this.original_set_count++;

        set.html(
            set.html().replace(
                RegExp('new_conditionset_block[0-9]{1,}', 'g'),
                'new_conditionset_block_' + this.original_set_count
            ).replace(
                RegExp('new_set_[0-9]{1,}', 'g'),
                'new_set_' + this.original_set_count
            )
        );

        // Add the new row ID to the field data
        $(set).attr(
            'id',
            'new_conditionset_block_' + this.original_set_count
        );

        // Enable remove button
        set.find('[rel=remove_row]').removeAttr('disabled');

        // Append the row to the end of the row container

        this.setParent.append(set);

        $(set).find('.condition-btn').trigger('click');

        // Bind the new row's inputs to AJAX form validation
        if (EE.cp && EE.cp.formValidation !== undefined) {
            EE.cp.formValidation.bindInputs(set);
        }

        return set;
    },

    _bindAddSetButton: function() {
        var that = this;

        $('body').on('click', 'a.add-set', function(event) {
            event.preventDefault();
            that._addSetBlock();

            if (that.original_set_count > 1) {
                $('.remove-set').show();
            }

            Dropdown.renderFields();
        });
    },

    _bindDeleteSetButton: function() {
        var that = this;

        $('body').on('click', that.deleteSetButtonsSelector, function(event) {
            event.preventDefault();

            var set = $(this).parents('.conditionset-item');

            // Remove the set
            set.remove();
            var set_count = that.mainParentContainer.find('.conditionset-item').not('.conditionset-temlates-row');

            if (set_count.length == 1) {
                $('.remove-set', set_count).hide();
            }

            // Mark Conditional Row field as valid if all rows with invalid cells are cleared
            if ($('div.invalid', set_count).length == 0 &&
                EE.cp &&
                EE.cp.formValidation !== undefined) {
                EE.cp.formValidation.markFieldValid($('input, select, textarea', that.blankSet).eq(0));
            }
        });
    },

    /**
     * Check and clone set if conditions have not yet been added
     */
    _firstCloneSet: function() {
        var that = this;

        if (this.original_set_count == 0) {
            this._addSetBlock();
            var parentSet = $('.conditionset-item:not(.hidden)');

            var newTimer = setInterval(function() {
                if ($(parentSet).find('.condition-btn').length) {
                    // Only trigger adding a new condition if the set has no visible rules
                    if($(parentSet).find('.rules .rule:not(.hidden)').length == 0) {
                        $(parentSet).find('.condition-btn').trigger('click');
                    }
                    clearInterval(newTimer);
                }
            },20);
        }
    }
}

function initRules () {
    var el = $('.conditionset-item');
    return new Conditional.Publish(el);
}


$(document).ready(function() {
    if (EE.conditionData) {
        initRules();
    }

    function checkFieldID(fieldName) {
        var fieldID;

        $.each(EE.fieldsInfo, function(i, val) {
            if (fieldName == val['field_label']) {
                fieldID = val['field_id'];
            }
        });
        return fieldID;
    }

    EE.cp.show_hide_rule_operator_field = function(element, input) {

        if ( ! $(element).length) {
            return;
        }

        var fieldName = element.label.replace(/<.*/g, "");
        var parentRow = $(input).parents('.rule');
        var evaluationRules;
        var operator = {};
        var selectedItem;

        parentRow.find('.condition-rule-value-wrap input').removeAttr('disabled');
        parentRow.find('.condition-rule-operator-wrap .condition-rule-operator').remove();
        parentRow.find('.condition-rule-value-wrap input').remove();

        $.each(EE.fieldsInfo, function(i, val) {
            if (fieldName == val['field_label']) {
                evaluationRules = val['evaluationRules'];
            }
        });

        var fieldID = checkFieldID(fieldName);

        $.each(evaluationRules, function(item, value){
            operator[item] =  value['text'];

            if (value['default']) {
                selectedItem = item
            }
        });

        var evaluation_rule_name = parentRow.find('.condition-rule-field-wrap .condition-rule-field').attr('data-input-value').replace('condition_field_id', 'evaluation_rule');
        var value_name = parentRow.find('.condition-rule-field-wrap .condition-rule-field').attr('data-input-value').replace('condition_field_id', 'value');

        var options = {
            name: evaluation_rule_name,
            items: operator,
            initialItems: operator,
            selected: selectedItem,
            disabled: false,
            tooMany: 20,
            limit: 100,
            groupToggle: null,
            emptyText: "Select a Field",
            conditionalRule: 'operator',
        };

        var dataDropdownReact = btoa(JSON.stringify(options));

        parentRow.find('.condition-rule-operator-wrap').append('<div data-input-value="'+evaluation_rule_name+'" class="condition-rule-operator" data-dropdown-react='+dataDropdownReact+'></div>');
        parentRow.find('.condition-rule-value-wrap').append('<input type="text" name="'+value_name+'">');

        Dropdown.renderFields();
        parentRow.find('.condition-rule-operator-wrap .empty-select').hide();
        parentRow.find('.condition-rule-operator-wrap .condition-rule-operator').show();

        EE.cp.show_hide_value_field(fieldID, selectedItem, parentRow);
    }

    EE.cp.check_operator_value = function(item, input) {
        var operatorVal = item.value;
        var parentRow = $(input).parents('.rule');
        var ruleLabel = parentRow.find('.condition-rule-field-wrap .select__dropdown-item--selected span:not(.short-name)').text();

        ruleLabel = ruleLabel.replace(/{.*/g, "");

        var rulefieldID = checkFieldID(ruleLabel);

        EE.cp.show_hide_value_field(rulefieldID, operatorVal, parentRow);
    } 

    EE.cp.show_hide_value_field = function(firstSelectVal, secondSelectVal, parentRow) {
        var evaluationRules;
        var evaluationValues;
        var operator = {};

        $.each(EE.fieldsInfo, function(i, val) {
            if (firstSelectVal == val['field_id']) {
                evaluationRules = val['evaluationRules'];
                evaluationValues = val['evaluationValues'];
            }
        });

        if (Object.keys(evaluationValues).length) {
            $.each(evaluationValues, function(item, value){
                operator[item] =  value;
            });

            var selectedItem = Object.keys(operator)[0];
        }

        $.each(evaluationRules, function(el, val) {
            var value_name = parentRow.find('.condition-rule-field-wrap .condition-rule-field').attr('data-input-value').replace('condition_field_id', 'value');

            if (secondSelectVal == el) {
                if (val['type'] == null) {
                    parentRow.find('.condition-rule-value-wrap').children().remove();
                } else if (val['type'] == 'select') {

                    parentRow.find('.condition-rule-value-wrap').children().remove();

                    var valueOptions = {
                        name: value_name,
                        items: operator,
                        initialItems: operator,
                        selected: selectedItem,
                        disabled: false,
                        tooMany: 20,
                        limit: 100,
                        groupToggle: null,
                        emptyText: "Select a Field",
                    };

                    var dataDropdownReact = btoa(JSON.stringify(valueOptions));

                    parentRow.find('.condition-rule-value-wrap').append('<div data-input-value="'+value_name+'" class="condition-rule-value" data-dropdown-react='+dataDropdownReact+'></div>');

                    Dropdown.renderFields();
                } else {
                    parentRow.find('.condition-rule-value-wrap').children().remove();
                    parentRow.find('.condition-rule-value-wrap').append('<input type="text" name="'+value_name+'">');
                }
            }
        })
    }

    $('body').on('mousemove', '.condition-rule-field-wrap .js-dropdown-toggle', function(e) {
        var X = e.offsetX;
        var Y = e.offsetY;
        var top = Y + 20 + 'px';
        var left = X + 20 + 'px';
        if ($(this).find('.tooltiptext').length) {
            $(this).find('.tooltiptext').css({
                display: 'block',
                top: top,
                left: left
            });
        }
    });

    $('body').on('mouseout', '.condition-rule-field-wrap .js-dropdown-toggle', function(e) {
        if ($(this).find('.tooltiptext').length) {
            $(this).find('.tooltiptext').css({display: "none"});
        }
    });

    // Check which field_type is selected
    $('body').on('change', "input[name='field_type']", function() {
        var hiddenList = $('#fieldset-field_instructions, #fieldset-field_required, #fieldset-field_search, #fieldset-field_is_hidden, #fieldset-enable_frontedit');

        var textInputSelectors = 'input[type=hidden], textarea, button.toggle-btn';

        if ($(this).val() == "notes") {
            hiddenList.each(function(){
                var el = $(this);
                el.hide();
                $('#fieldset-enable_frontedit').prev('h2').hide();
            });
        } else if($(this).val() == "relationship") {
            hiddenList.each(function(){
                var el = $(this);
                el.show();
                $('#fieldset-enable_frontedit').prev('h2').show();
                
                $(textInputSelectors, el).prop('disabled', false);
            });
            $('#fieldset-field_search').hide();
        } else {
            $('#fieldset-field_search').show();
            hiddenList.each(function(){
                var el = $(this);
                el.show();
                $('#fieldset-enable_frontedit').prev('h2').show();
                
                $(textInputSelectors, el).prop('disabled', false);
            });

        }
    });

    $("input[name='field_type']").trigger("change");

    $('body').on('change', 'input[name ^="grid"][name $="[col_type]"]', function(){
        if ($(this).val() == "relationship") {
            var el = $(this);
            var el_parent = el.parents('.fields-grid-common');
            el_parent.find('fieldset[id ^="fieldset-grid"][id $="[col_search]"]').hide();
        } else {
            var el = $(this);
            var el_parent = el.parents('.fields-grid-common');
            el_parent.find('fieldset[id ^="fieldset-grid"][id $="[col_search]"]').show();
        }
    })

    if ($('input[name ^="grid"][name $="[col_type]"').length) {
        $('input[name ^="grid"][name $="[col_type]"').trigger("change");
    }
});

})(jQuery);
