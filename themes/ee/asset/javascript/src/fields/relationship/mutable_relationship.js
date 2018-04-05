'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var MutableRelationshipField = function () {
  function MutableRelationshipField(field, options) {
    _classCallCheck(this, MutableRelationshipField);

    this.field = field;
    this.options = options;

    this.bindAdd();
  }

  _createClass(MutableRelationshipField, [{
    key: 'bindAdd',
    value: function bindAdd() {
      var _this = this;

      this.field.closest('[data-relationship-react]').parent().find('[rel=add_new][data-channel-id]').on('click', function (e) {
        e.preventDefault();
        _this.openPublishFormForChannel($(e.currentTarget).data('channelId'));
      });
    }
  }, {
    key: 'openPublishFormForChannel',
    value: function openPublishFormForChannel(channelId) {
      EE.cp.ModalForm.openForm({
        url: EE.relationship.publishCreateUrl.replace('###', channelId),
        full: true,
        iframe: true,
        success: this.options.success
      });
    }
  }]);

  return MutableRelationshipField;
}();