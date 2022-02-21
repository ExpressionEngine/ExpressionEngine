"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } Object.defineProperty(subClass, "prototype", { value: Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }), writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */
function FileUploadProgressTable(props) {
  return /*#__PURE__*/React.createElement("div", {
    className: "file-field__items list-group"
  }, props.files.map(function (file) {
    return /*#__PURE__*/React.createElement("div", {
      key: file.name,
      className: "list-item"
    }, /*#__PURE__*/React.createElement("div", {
      className: "list-item__content-left"
    }, (file.error || file.duplicate) && /*#__PURE__*/React.createElement("i", {
      "class": "fas fa-exclamation-triangle file-field__file-icon file-field__file-icon-warning"
    }), !file.error && !file.duplicate && /*#__PURE__*/React.createElement("i", {
      "class": "fas fa-file-archive file-field__file-icon"
    })), /*#__PURE__*/React.createElement("div", {
      className: "list-item__content"
    }, /*#__PURE__*/React.createElement("div", null, file.name, " ", !file.error && !file.duplicate && /*#__PURE__*/React.createElement("span", {
      "class": "float-right meta-info"
    }, Math.round(file.progress), "% / 100%")), /*#__PURE__*/React.createElement("div", {
      className: "list-item__secondary"
    }, file.error && /*#__PURE__*/React.createElement("span", {
      className: "error-text"
    }, file.error), file.duplicate && /*#__PURE__*/React.createElement("span", {
      className: "error-text"
    }, EE.lang.file_dnd_conflict), !file.error && !file.duplicate && /*#__PURE__*/React.createElement("div", {
      className: "progress-bar"
    }, /*#__PURE__*/React.createElement("div", {
      className: "progress",
      style: {
        width: file.progress + '%'
      }
    })))), /*#__PURE__*/React.createElement("div", {
      className: "list-item__content-right"
    }, file.error && /*#__PURE__*/React.createElement("a", {
      className: "button button--default",
      href: "#",
      onClick: function onClick(e) {
        return props.onFileErrorDismiss(e, file);
      }
    }, EE.lang.file_dnd_dismiss), file.duplicate && /*#__PURE__*/React.createElement(ResolveFilenameConflict, {
      file: file,
      onResolveConflict: props.onResolveConflict,
      onFileUploadCancel: function onFileUploadCancel(e) {
        return props.onFileErrorDismiss(e, file);
      }
    })));
  }));
}

var ResolveFilenameConflict = /*#__PURE__*/function (_React$Component) {
  _inherits(ResolveFilenameConflict, _React$Component);

  var _super = _createSuper(ResolveFilenameConflict);

  function ResolveFilenameConflict() {
    var _this;

    _classCallCheck(this, ResolveFilenameConflict);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

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
      iframe.load(function () {
        var response = iframe.contents().find('body').text();

        try {
          response = JSON.parse(response);
          modal.trigger('modal:close');

          if (response.cancel) {
            return _this.props.onFileUploadCancel(e, file);
          }

          return _this.props.onResolveConflict(file, response);
        } catch (e) {
          var height = iframe.contents().find('body').height();
          $('.box', modal).height(height);
          iframe.height(height);
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

      return /*#__PURE__*/React.createElement("a", {
        href: "#",
        className: "button button--default m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          return _this2.resolveConflict(e, _this2.props.file);
        }
      }, /*#__PURE__*/React.createElement("i", {
        "class": "fas fa-info-circle icon-left"
      }), EE.lang.file_dnd_resolve_conflict);
    }
  }]);

  return ResolveFilenameConflict;
}(React.Component);