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
var MutableRelationshipField =
/*#__PURE__*/
function () {
  function MutableRelationshipField(field, options) {
    _classCallCheck(this, MutableRelationshipField);

    this.field = field;
    this.options = options;
    this.bindAdd();
  }

  _createClass(MutableRelationshipField, [{
    key: "bindAdd",
    value: function bindAdd() {
      var _this = this;

      this.field.closest('[data-relationship-react]').parent().find('[rel=add_new][data-channel-id]').on('click', function (e) {
        e.preventDefault();
        var channelLink = $(e.currentTarget);

        _this.openPublishFormForChannel(channelLink.data('channelId'), channelLink.data('channelTitle')); // Close sub menu


        if (channelLink.closest('.sub-menu').length) {
          channelLink.closest('.filters').find('.open').removeClass('open').siblings('.sub-menu').hide();
        }
      });
    }
  }, {
    key: "openPublishFormForChannel",
    value: function openPublishFormForChannel(channelId, channelTitle) {
      var _this2 = this;

      EE.cp.ModalForm.openForm({
        url: EE.relationship.publishCreateUrl.replace('###', channelId),
        full: true,
        iframe: true,
        success: this.options.success,
        load: function load(modal) {
          var entryTitle = _this2.field.closest('[data-publish]').find('input[name=title]').val();

          var title = EE.relationship.lang.creatingNew.replace('#to_channel#', channelTitle).replace('#from_channel#', EE.publish.channel_title);

          if (entryTitle) {
            title += '<b>: ' + entryTitle + '</b>';
          }

          EE.cp.ModalForm.setTitle(title);
        }
      });
    }
  }]);

  return MutableRelationshipField;
}();