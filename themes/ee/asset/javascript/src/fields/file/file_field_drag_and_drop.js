'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

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

var FileField = function (_React$Component) {
  _inherits(FileField, _React$Component);

  function FileField(props) {
    _classCallCheck(this, FileField);

    var _this = _possibleConstructorReturn(this, (FileField.__proto__ || Object.getPrototypeOf(FileField)).call(this, props));

    _this.setFile = function (response) {
      var fileField = _this.getFieldContainer();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input:hidden').first(),
        input_img: fileField.find('img').first(),
        modal: $('.modal-file')
      });

      _this.setState({
        file: response
      });
    };

    _this.state = {
      file: props.file
    };
    return _this;
  }

  _createClass(FileField, [{
    key: 'componentDidMount',
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
    key: 'getFieldContainer',
    value: function getFieldContainer() {
      return $(this.dropZone).closest('.grid-file-upload, .field-control');
    }
  }, {
    key: 'render',
    value: function render() {
      var _this3 = this;

      if (this.state.file) {
        return null;
      }

      return React.createElement(DragAndDropUpload, _extends({}, this.props, {
        onFileUploadSuccess: this.setFile,
        assignDropZoneRef: function assignDropZoneRef(dropZone) {
          _this3.dropZone = dropZone;
        },
        marginTop: false,
        multiFile: false
      }));
    }
  }], [{
    key: 'renderFields',
    value: function renderFields(context) {
      $('div[data-file-field-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('fileFieldReact')));
        ReactDOM.render(React.createElement(FileField, props, null), this);
      });
    }
  }]);

  return FileField;
}(React.Component);