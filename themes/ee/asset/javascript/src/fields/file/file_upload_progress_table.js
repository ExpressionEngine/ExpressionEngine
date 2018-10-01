"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function FileUploadProgressTable(props) {
  return React.createElement(
    "div",
    { className: "field-file-upload__table" },
    React.createElement(
      "div",
      { className: "tbl-wrap" },
      React.createElement(
        "table",
        { className: "tbl-fixed tables--uploads" },
        React.createElement(
          "tbody",
          null,
          React.createElement(
            "tr",
            null,
            React.createElement(
              "th",
              null,
              "File Name"
            ),
            React.createElement(
              "th",
              null,
              "Progress"
            )
          ),
          props.files.map(function (file) {
            return React.createElement(
              "tr",
              { key: file.name },
              React.createElement(
                "td",
                null,
                (file.error || file.duplicate) && React.createElement("span", { className: "icon--issue" }),
                file.name
              ),
              React.createElement(
                "td",
                null,
                file.error,
                file.error && React.createElement(
                  "span",
                  null,
                  "\xA0",
                  React.createElement(
                    "a",
                    { href: "#", onClick: function onClick(e) {
                        return props.onFileErrorDismiss(e, file);
                      } },
                    "Dismiss"
                  )
                ),
                file.duplicate && React.createElement(ResolveFilenameConflict, {
                  file: file,
                  onResolveConflict: function onResolveConflict(file, response) {
                    return props.onResolveConflict(file, response);
                  }
                }),
                file.progress && React.createElement(
                  "div",
                  { className: "progress-bar" },
                  React.createElement("div", { className: "progress", style: { width: file.progress + '%' } })
                )
              )
            );
          })
        )
      )
    )
  );
}

var ResolveFilenameConflict = function (_React$Component) {
  _inherits(ResolveFilenameConflict, _React$Component);

  function ResolveFilenameConflict() {
    var _ref;

    var _temp, _this, _ret;

    _classCallCheck(this, ResolveFilenameConflict);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return _ret = (_temp = (_this = _possibleConstructorReturn(this, (_ref = ResolveFilenameConflict.__proto__ || Object.getPrototypeOf(ResolveFilenameConflict)).call.apply(_ref, [this].concat(args))), _this), _this.resolveConflict = function (e, file) {
      e.preventDefault();

      var url = 'http://eecms.localhost/admin.php?/cp/addons/settings/filepicker/ajax-overwrite-or-rename&file_id=' + file.fileId + '&original_file_name=' + file.originalFileName;
      var modal = $('.modal-file');
      $('div.box', modal).html('<iframe></iframe>');
      var iframe = $('iframe', modal);
      iframe.css({
        border: 'none',
        width: '100%'
      });
      iframe.attr('src', url);
      modal.find('div.box').html(iframe);

      iframe.load(function () {
        var response = iframe.contents().find('body').text();
        try {
          response = JSON.parse(response);
          modal.trigger('modal:close');
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
    }, _temp), _possibleConstructorReturn(_this, _ret);
  }

  _createClass(ResolveFilenameConflict, [{
    key: "render",
    value: function render() {
      var _this2 = this;

      return React.createElement(
        "a",
        { href: "#", className: "m-link", rel: "modal-file", onClick: function onClick(e) {
            return _this2.resolveConflict(e, _this2.props.file);
          } },
        "Resolve Conflict"
      );
    }
  }]);

  return ResolveFilenameConflict;
}(React.Component);