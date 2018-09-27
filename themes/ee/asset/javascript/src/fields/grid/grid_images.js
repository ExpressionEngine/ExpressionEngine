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

  function GridImages(props) {
    _classCallCheck(this, GridImages);

    var _this = _possibleConstructorReturn(this, (GridImages.__proto__ || Object.getPrototypeOf(GridImages)).call(this, props));

    _this.addFileToGrid = function (file, response) {
      var gridInstance = $(_this.dropZone).closest('.js-grid-images').find('.grid-input-form').data('GridInstance');

      var fileField = gridInstance._addRow().find('.grid-file-upload').first();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input:hidden').first(),
        input_img: fileField.find('img').first()
      });
    };

    return _this;
  }

  _createClass(GridImages, [{
    key: 'render',
    value: function render() {
      var _this2 = this;

      return React.createElement(DragAndDropUpload, _extends({}, this.props, {
        onFileUploadSuccess: function onFileUploadSuccess(file, response) {
          return _this2.addFileToGrid(file, response);
        },
        assignDropZoneRef: function assignDropZoneRef(dropZone) {
          _this2.dropZone = dropZone;
        }
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