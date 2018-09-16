'use strict';

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

    _this.chooseExisting = function (directory) {
      directory = directory || _this.props.allowedDirectory;
      console.log(directory);
    };

    _this.uploadNew = function (directory) {
      directory = directory || _this.props.allowedDirectory;
      console.log(directory);
    };

    return _this;
  }

  _createClass(GridImages, [{
    key: 'render',
    value: function render() {
      var _this2 = this;

      var lang = this.props.lang;
      return React.createElement(
        'div',
        null,
        React.createElement(
          'div',
          { className: 'field-file-upload mt' },
          React.createElement(
            'div',
            { className: 'field-file-upload__content' },
            lang.grid_images_drop_files,
            React.createElement(
              'em',
              null,
              lang.grid_images_uploading_to
            )
          ),
          this.props.allowedDirectory == 'all' && React.createElement(
            'div',
            { 'class': 'field-file-upload__controls' },
            React.createElement(FilterSelect, { key: lang.grid_images_choose_existing,
              center: true,
              keepSelectedState: false,
              title: lang.grid_images_choose_existing,
              placeholder: 'filter directories',
              items: this.props.uploadDestinations,
              onSelect: function onSelect(directory) {
                return _this2.chooseExisting(directory);
              }
            })
          )
        ),
        this.props.allowedDirectory != 'all' && React.createElement(
          'div',
          null,
          React.createElement(
            'a',
            { href: '#', className: 'btn action', onClick: function onClick(e) {
                e.preventDefault();
                _this2.chooseExisting();
              } },
            lang.grid_images_choose_existing
          ),
          '\xA0',
          React.createElement(
            'a',
            { href: '#', className: 'btn action', onClick: function onClick(e) {
                e.preventDefault();
                _this2.uploadNew();
              } },
            lang.grid_images_upload_new
          )
        ),
        this.props.allowedDirectory == 'all' && React.createElement(
          'div',
          { 'class': 'filter-bar filter-bar--inline' },
          React.createElement(FilterSelect, { key: lang.grid_images_choose_existing,
            action: true,
            keepSelectedState: false,
            title: lang.grid_images_choose_existing,
            placeholder: 'filter directories',
            items: this.props.uploadDestinations,
            onSelect: function onSelect(directory) {
              return _this2.chooseExisting(directory);
            }
          }),
          React.createElement(FilterSelect, { key: lang.grid_images_upload_new,
            action: true,
            keepSelectedState: false,
            title: lang.grid_images_upload_new,
            placeholder: 'filter directories',
            items: this.props.uploadDestinations,
            onSelect: function onSelect(directory) {
              return _this2.uploadNew(directory);
            }
          })
        )
      );
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