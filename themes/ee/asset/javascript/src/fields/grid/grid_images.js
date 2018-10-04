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

var GridImages = function (_React$Component) {
  _inherits(GridImages, _React$Component);

  function GridImages() {
    var _ref;

    var _temp, _this, _ret;

    _classCallCheck(this, GridImages);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return _ret = (_temp = (_this = _possibleConstructorReturn(this, (_ref = GridImages.__proto__ || Object.getPrototypeOf(GridImages)).call.apply(_ref, [this].concat(args))), _this), _this.shouldAcceptFiles = function (files) {
      if (_this.props.maxRows !== '') {
        if (files.length + _this.getRowCount() > _this.props.maxRows) {
          return EE.lang.grid_images_maximum_rows_hit.replace('%s', _this.props.maxRows);
        }
      }
      return true;
    }, _this.addFileToGrid = function (response) {
      var fileField = _this.getGridInstance()._addRow().find('.grid-file-upload').first();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input:hidden').first(),
        input_img: fileField.find('img').first(),
        modal: $('.modal-file')
      });
    }, _temp), _possibleConstructorReturn(_this, _ret);
  }

  _createClass(GridImages, [{
    key: 'getGridInstance',
    value: function getGridInstance() {
      if (!this.gridInstance) {
        this.gridInstance = $(this.dropZone).closest('.js-grid-images').find('.grid-input-form').data('GridInstance');
      }

      return this.gridInstance;
    }
  }, {
    key: 'getRowCount',
    value: function getRowCount() {
      return this.getGridInstance()._getRows().size();
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      return React.createElement(DragAndDropUpload, _extends({}, this.props, {
        onFileUploadSuccess: this.addFileToGrid,
        assignDropZoneRef: function assignDropZoneRef(dropZone) {
          _this2.dropZone = dropZone;
        },
        shouldAcceptFiles: this.shouldAcceptFiles,
        marginTop: true
      }));
    }
  }], [{
    key: 'renderFields',
    value: function renderFields(context) {
      $('div[data-grid-images-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('gridImagesReact')));
        ReactDOM.render(React.createElement(GridImages, props, null), this);
      });
    }
  }]);

  return GridImages;
}(React.Component);

$(document).ready(function () {
  GridImages.renderFields();
});

FluidField.on('grid_images', 'add', function (field) {
  GridImages.renderFields(field);
});