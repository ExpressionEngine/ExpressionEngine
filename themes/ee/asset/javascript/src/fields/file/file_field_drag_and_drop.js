"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

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
var FileField =
/*#__PURE__*/
function (_React$Component) {
  _inherits(FileField, _React$Component);

  function FileField(props) {
    var _this;

    _classCallCheck(this, FileField);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(FileField).call(this, props));

    _defineProperty(_assertThisInitialized(_this), "setFile", function (response, mainDropzone) {
      var fileField = _this.getFieldContainer(mainDropzone);

      if (fileField.find('div[data-file-field-react]').length) {
        EE.FileField.pickerCallback(response, {
          input_value: fileField.find('input.js-file-input'),
          input_img: fileField.find('img.js-file-image'),
          modal: $('.modal-file')
        });
      }

      if (fileField.find('textarea.has-format-options').length) {
        EE.filePickerCallback(response, {
          input_value: mainDropzone,
          modal: $('.modal-file')
        });
      }

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

      this.getFieldContainer().on('click', '.button.remove', function () {
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
    value: function getFieldContainer(mainDropzone) {
      var thisField = $(this.props.thisField);

      if (mainDropzone !== undefined) {
        thisField = mainDropzone.parents('div[data-file-field-react]');

        if (!thisField.length) {
          thisField = mainDropzone;
        }
      } // If in a grid, return that


      if (thisField.closest('.grid-file-upload').length) {
        return thisField.closest('.grid-file-upload');
      }

      var fluidContainer = thisField.closest('.fluid__item-field'); // Is this file field inside of a fluid field? 
      // If it is, we need to get the fluid item container, 
      // not the container that holds the entire fluid field

      if (fluidContainer.length) {
        return fluidContainer;
      }

      return thisField.closest('.grid-file-upload, .field-control');
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
        multiFile: true
      }));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-file-field-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('fileFieldReact')));
        props.thisField = $(this);
        var files_field = props.thisField.data('input-value');
        ReactDOM.render(React.createElement(FileField, props, null), this);
        new MutableSelectField(files_field, EE.fileManager.fileDirectory);
      });
    }
  }]);

  return FileField;
}(React.Component);