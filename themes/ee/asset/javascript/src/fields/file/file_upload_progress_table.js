"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */
function FileUploadProgressTable(props) {
  return React.createElement("div", {
    className: "field-file-upload__table"
  }, React.createElement("div", {
    className: "tbl-wrap"
  }, React.createElement("table", {
    className: "tbl-fixed tables--uploads"
  }, React.createElement("tbody", null, React.createElement("tr", null, React.createElement("th", null, EE.lang.file_dnd_file_name), React.createElement("th", null, EE.lang.file_dnd_progress)), props.files.map(function (file) {
    return React.createElement("tr", {
      key: file.name
    }, React.createElement("td", null, (file.error || file.duplicate) && React.createElement("span", {
      className: "icon--issue"
    }), file.name), React.createElement("td", null, file.error, file.error && React.createElement("span", null, "\xA0", React.createElement("a", {
      href: "#",
      onClick: function onClick(e) {
        return props.onFileErrorDismiss(e, file);
      }
    }, EE.lang.file_dnd_dismiss)), file.duplicate && React.createElement(ResolveFilenameConflict, {
      file: file,
      onResolveConflict: props.onResolveConflict,
      onFileUploadCancel: function onFileUploadCancel(e) {
        return props.onFileErrorDismiss(e, file);
      }
    }), !file.error && !file.duplicate && React.createElement("div", {
      className: "progress-bar"
    }, React.createElement("div", {
      className: "progress",
      style: {
        width: file.progress + '%'
      }
    }))));
  })))));
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

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "resolveConflict", function (e, file) {
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

      return React.createElement("a", {
        href: "#",
        className: "m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          return _this2.resolveConflict(e, _this2.props.file);
        }
      }, EE.lang.file_dnd_resolve_conflict);
    }
  }]);

  return ResolveFilenameConflict;
}(React.Component);