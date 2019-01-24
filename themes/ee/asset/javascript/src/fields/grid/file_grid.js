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
var FileGrid =
/*#__PURE__*/
function (_React$Component) {
  _inherits(FileGrid, _React$Component);

  function FileGrid() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, FileGrid);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(FileGrid)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "shouldAcceptFiles", function (files) {
      if (_this.props.maxRows !== '') {
        if (files.length + _this.getRowCount() > _this.props.maxRows) {
          return EE.lang.file_grid_maximum_rows_hit.replace('%s', _this.props.maxRows);
        }
      }

      return true;
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "addFileToGrid", function (response) {
      var fileField = _this.getGridInstance()._addRow().find('.grid-file-upload').first();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input:hidden').first(),
        input_img: fileField.find('img').first(),
        modal: $('.modal-file')
      });
    });

    return _this;
  }

  _createClass(FileGrid, [{
    key: "getGridInstance",
    value: function getGridInstance() {
      if (!this.gridInstance) {
        this.gridInstance = $(this.dropZone).closest('.js-file-grid').find('.grid-input-form').data('GridInstance');
      }

      return this.gridInstance;
    }
  }, {
    key: "getRowCount",
    value: function getRowCount() {
      return this.getGridInstance()._getRows().size();
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      return React.createElement(DragAndDropUpload, _extends({}, this.props, {
        onFileUploadSuccess: this.addFileToGrid,
        assignDropZoneRef: function assignDropZoneRef(dropZone) {
          _this2.dropZone = dropZone;
        },
        shouldAcceptFiles: this.shouldAcceptFiles,
        marginTop: true,
        multiFile: true
      }));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-file-grid-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('fileGridReact')));
        ReactDOM.render(React.createElement(FileGrid, props, null), this);
      });
    }
  }]);

  return FileGrid;
}(React.Component);

$(document).ready(function () {
  FileGrid.renderFields();
});
FluidField.on('file_grid', 'add', function (field) {
  EE.grid($('table[data-grid-settings]', field));
  FileGrid.renderFields(field);
});