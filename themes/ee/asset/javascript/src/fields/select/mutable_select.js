'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var MutableSelectField = function () {
  function MutableSelectField(fieldName, options) {
    _classCallCheck(this, MutableSelectField);

    this.fieldName = fieldName;
    this.options = options;
    this.setField();
    this.setAddButton(this.field.parent().find('a.btn.action'));

    this.bindAdd();
    this.bindEdit();
    this.bindRemove();
  }

  _createClass(MutableSelectField, [{
    key: 'setField',
    value: function setField() {
      this.field = $('[data-input-value="' + this.fieldName + '"]');
    }
  }, {
    key: 'setAddButton',
    value: function setAddButton(button) {
      this.addButton = button;
    }
  }, {
    key: 'bindAdd',
    value: function bindAdd() {
      var _this = this;

      this.addButton.on('click', function (e) {
        e.preventDefault();
        _this.openForm(_this.options.createUrl);
      });
    }
  }, {
    key: 'bindEdit',
    value: function bindEdit() {
      var _this2 = this;

      this.field.parent().on('click', 'label > a', function (e) {
        e.preventDefault();
        var itemId = $(e.target).closest('[data-id]').data('id');
        _this2.openForm(_this2.options.editUrl.replace('###', itemId));
      });
    }
  }, {
    key: 'bindRemove',
    value: function bindRemove() {
      var _this3 = this;

      this.field.parent().on('select:removeItem', '[data-id]', function (e, item) {
        EE.cp.Modal.openConfirmRemove(_this3.options.removeUrl, item.label, item.value, function (result) {
          _this3.replaceField(result.selectList);
        });
      });
    }
  }, {
    key: 'openForm',
    value: function openForm(url) {
      var _this4 = this;

      EE.cp.ModalForm.openForm({
        url: url,
        load: function load(modal) {
          EE.cp.form_group_toggle(modal.find('[data-group-toggle]:input:checked'));

          SelectField.renderFields(modal);
          Dropdown.renderFields(modal);

          if (_this4.options.onFormLoad) {
            _this4.options.onFormLoad(modal);
          }
        },
        success: function success(result) {
          _this4.replaceField(result.selectList);
        }
      });
    }
  }, {
    key: 'replaceField',
    value: function replaceField(html) {
      this.field.replaceWith(html);
      this.setField();
      SelectField.renderFields(this.field.parent());
    }
  }]);

  return MutableSelectField;
}();