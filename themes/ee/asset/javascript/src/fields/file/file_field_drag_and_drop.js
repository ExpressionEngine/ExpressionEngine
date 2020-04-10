"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

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
var FileField =
/*#__PURE__*/
function (_React$Component) {
  _inherits(FileField, _React$Component);

  function FileField(props) {
    var _this;

    _classCallCheck(this, FileField);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(FileField).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "setFile", function (response) {
      var fileField = _this.getFieldContainer();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input.js-file-input'),
        input_img: fileField.find('img.js-file-image'),
        modal: $('.modal-file')
      });

      _this.setState({
        file: response
      });
    });

    _this.state = {
      file: props.file
    };
    return _this;
  }

  _createClass(FileField, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.getFieldContainer().on('click', 'li.remove a', function () {
        _this2.setState({
          file: null
        });
      }).on('hasFile', 'input:hidden', function (data) {
        _this2.setState({
          file: data
        });
      });
    }
  }, {
    key: "getFieldContainer",
    value: function getFieldContainer() {
      return $(this.props.thisField).closest('.grid-file-upload, .field-control');
    }
  }, {
    key: "render",
    value: function render() {
      if (this.state.file) {
        return null;
      }

      return React.createElement(DragAndDropUpload, _extends({}, this.props, {
        onFileUploadSuccess: this.setFile,
        marginTop: false,
        multiFile: false
      }));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-file-field-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('fileFieldReact')));
        props.thisField = $(this);
        ReactDOM.render(React.createElement(FileField, props, null), this);
      });
    }
  }]);

  return FileField;
}(React.Component);