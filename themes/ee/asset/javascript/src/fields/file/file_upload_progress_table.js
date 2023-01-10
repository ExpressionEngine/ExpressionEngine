"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */
function FileUploadProgressTable(props) {
  return React.createElement("div", {
    className: "file-field__items list-group"
  }, props.files.map(function (file) {
    return React.createElement("div", {
      key: file.name,
      className: "list-item"
    }, React.createElement("div", {
      className: "list-item__content-left"
    }, (file.error || file.duplicate) && React.createElement("i", {
      "class": "fal fa-exclamation-triangle file-field__file-icon file-field__file-icon-warning"
    }), !file.error && !file.duplicate && React.createElement("i", {
      "class": "fal fa-file-archive file-field__file-icon"
    })), React.createElement("div", {
      className: "list-item__content"
    }, React.createElement("div", null, file.name, " ", !file.error && !file.duplicate && React.createElement("span", {
      "class": "float-right meta-info"
    }, Math.round(file.progress), "% / 100%")), React.createElement("div", {
      className: "list-item__secondary"
    }, file.error && React.createElement("span", {
      className: "error-text"
    }, file.error), file.duplicate && React.createElement("span", {
      className: "error-text"
    }, EE.lang.file_dnd_conflict), !file.error && !file.duplicate && React.createElement("div", {
      className: "progress-bar"
    }, React.createElement("div", {
      className: "progress",
      style: {
        width: file.progress + '%'
      }
    })))), React.createElement("div", {
      className: "list-item__content-right"
    }, file.error && React.createElement("a", {
      className: "button button--default",
      href: "#",
      onClick: function onClick(e) {
        return props.onFileErrorDismiss(e, file);
      }
    }, EE.lang.file_dnd_dismiss), file.duplicate && React.createElement(ResolveFilenameConflict, {
      file: file,
      onResolveConflict: props.onResolveConflict,
      onFileUploadCancel: function onFileUploadCancel(e) {
        return props.onFileErrorDismiss(e, file);
      }
    })));
  }));
}

var ResolveFilenameConflict =
/*#__PURE__*/
function (_React$Component) {
  _inherits(ResolveFilenameConflict, _React$Component);

  function ResolveFilenameConflict() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, ResolveFilenameConflict);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(ResolveFilenameConflict)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "resolveConflict", function (e, file) {
      e.preventDefault();
      var modal = $('.modal-file');
      $('div.box', modal).html('<iframe></iframe>');
      var iframe = $('iframe', modal);
      iframe.css({
        border: 'none',
        width: '100%'
      });
      var params = {
        file_id: file.fileId,
        original_name: file.originalFileName
      };
      var url = EE.dragAndDrop.resolveConflictEndpoint + '&' + $.param(params);
      iframe.attr('src', url);
      modal.find('div.box').html(iframe);
      iframe.on('load', function () {
        var response = iframe.contents().find('body').text();

        try {
          response = JSON.parse(response);
          modal.trigger('modal:close');

          if (response.cancel) {
            if ($('.file-upload-widget').length) {
              $('.file-upload-widget').hide();
            }

            return _this.props.onFileUploadCancel(e, file);
          }

          return _this.props.onResolveConflict(file, response);
        } catch (e) {
          var height = iframe.contents().find('body').height();
          $('.box', modal).height('600px');
          iframe.height('600px');
          iframe.show();
        }

        $(iframe[0].contentWindow).on('unload', function () {
          iframe.hide();
          $('.box', modal).height('auto');
          $(modal).height('auto');
        });
      });
    });

    return _this;
  }

  _createClass(ResolveFilenameConflict, [{
    key: "render",
    value: function render() {
      var _this2 = this;

      return React.createElement("a", {
        href: "#",
        className: "button button--default m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          return _this2.resolveConflict(e, _this2.props.file);
        }
      }, React.createElement("i", {
        "class": "fal fa-info-circle icon-left"
      }), EE.lang.file_dnd_resolve_conflict);
    }
  }]);

  return ResolveFilenameConflict;
}(React.Component);