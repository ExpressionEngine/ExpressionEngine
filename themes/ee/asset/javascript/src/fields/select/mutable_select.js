"use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var MutableSelectField =
/*#__PURE__*/
function () {
  function MutableSelectField(fieldName, options) {
    _classCallCheck(this, MutableSelectField);

    this.fieldName = fieldName;
    this.options = options;
    this.addButton = 'a[rel="add_new"]';
    this.setField();
    this.toggleAddButton();
    this.bindAdd();
    this.bindEdit();
    this.bindRemove();
  }

  _createClass(MutableSelectField, [{
    key: "setField",
    value: function setField() {
      this.field = $('[data-input-value="' + this.fieldName + '"]');
    } // Don't show blue action button if there are no results

  }, {
    key: "toggleAddButton",
    value: function toggleAddButton() {
      var addButtons = this.field.parent().find(this.addButton);

      if (this.field.find('.field-no-results').size()) {
        addButtons.filter(function (i, el) {
          return $(el).hasClass('btn');
        }).hide();
      } else {
        addButtons.show();
      }
    }
  }, {
    key: "bindAdd",
    value: function bindAdd() {
      var _this = this;

      this.field.parent().on('click', this.addButton, function (e) {
        e.preventDefault();

        _this.openForm(_this.options.createUrl);
      });
    }
  }, {
    key: "bindEdit",
    value: function bindEdit() {
      var _this2 = this;

      this.field.parent().on('click', 'label > a', function (e) {
        e.preventDefault();
        var itemId = $(e.target).closest('[data-id]').data('id');

        _this2.openForm(_this2.options.editUrl.replace('###', itemId));
      });
    }
  }, {
    key: "bindRemove",
    value: function bindRemove() {
      var _this3 = this;

      this.field.parent().on('select:removeItem', '[data-id]', function (e, item) {
        EE.cp.Modal.openConfirmRemove(_this3.options.removeUrl, item.label, item.value, function (result) {
          return _this3.handleResponse(result);
        });
      });
    }
  }, {
    key: "openForm",
    value: function openForm(url) {
      var _this4 = this;

      EE.cp.ModalForm.openForm({
        url: url,
        createUrl: this.options.createUrl,
        load: function load(modal) {
          EE.cp.form_group_toggle(modal.find('[data-group-toggle]:input:checked'));
          SelectField.renderFields(modal);
          Dropdown.renderFields(modal);

          if (_this4.options.onFormLoad) {
            _this4.options.onFormLoad(modal);
          }
        },
        success: function success(result) {
          return _this4.handleResponse(result);
        }
      });
    }
  }, {
    key: "handleResponse",
    value: function handleResponse(result) {
      var _this5 = this;

      // A selectList key should contain the field markup
      if (result.selectList) {
        this.replaceField(result.selectList); // Otherwise, we have to fetch the field markup ourselves
      } else if (this.options.fieldUrl) {
        var selected = result.saveId ? [result.saveId] : []; // Gather the current field selection so that it may be applied to the
        // field upon reload. Checkboxes for server-rendered fields, hidden
        // inputs for the React fields.

        $('input[type=checkbox][name="' + this.fieldName + '[]"]:checked, input[type=hidden][name="' + this.fieldName + '[]"]').each(function () {
          selected.push($(this).val());
        });
        var postdata = {};
        postdata[this.fieldName] = selected;
        $.post(this.options.fieldUrl, postdata, function (result) {
          _this5.replaceField(result);
        });
      }
    }
  }, {
    key: "replaceField",
    value: function replaceField(html) {
      this.field.replaceWith(html);
      this.setField();
      SelectField.renderFields(this.field.parent());
      this.toggleAddButton();
    }
  }]);

  return MutableSelectField;
}();