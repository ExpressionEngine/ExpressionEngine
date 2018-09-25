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

    _this.setDirectory = function (directory) {
      _this.setState({
        directory: directory || 'all'
      });
    };

    _this.chooseExisting = function (directory) {
      directory = directory || _this.state.directory;
      console.log(directory);
    };

    _this.uploadNew = function (directory) {
      directory = directory || _this.state.directory;
      console.log(directory);
    };

    _this.state = {
      files: [],
      directory: props.allowedDirectory,
      directoryName: _this.getDirectoryName(props.allowedDirectory)
    };
    _this.queue = new ConcurrencyQueue({ concurrency: 1 });
    return _this;
  }

  _createClass(GridImages, [{
    key: 'getDirectoryName',
    value: function getDirectoryName(directory) {
      if (directory == 'all') return null;

      directory = this.props.uploadDestinations.find(function (thisDirectory) {
        return thisDirectory.value == directory;
      });
      return directory.label;
    }
  }, {
    key: 'bindDragAndDropEvents',
    value: function bindDragAndDropEvents() {
      var _this2 = this;

      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
        _this2.dropZone.addEventListener(eventName, preventDefaults, false);
      });

      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }

      this.dropZone.addEventListener('drop', function (e) {
        var files = Array.from(e.dataTransfer.files);

        files = files.filter(function (file) {
          return file.type != '';
        });
        files = files.map(function (file) {
          file.progress = 0;
          return file;
        });

        _this2.setState({
          files: _this2.state.files.concat(files)
        });

        _this2.queue.enqueue(files, function (file) {
          return _this2.makeUploadPromise(file);
        });
      });
    }
  }, {
    key: 'makeUploadPromise',
    value: function makeUploadPromise(file) {
      var _this3 = this;

      return new Promise(function (resolve, reject) {
        var url = 'http://eecms.localhost/test.php';
        var xhr = new XMLHttpRequest();
        var formData = new FormData();
        xhr.open('POST', url, true);

        xhr.upload.addEventListener('progress', function (e) {
          var fileIndex = _this3.state.files.findIndex(function (thisFile) {
            return thisFile.name == file.name;
          });
          _this3.state.files[fileIndex].progress = e.loaded * 100.0 / e.total || 100;
          _this3.setState({
            files: _this3.state.files
          });
        });

        xhr.addEventListener('readystatechange', function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            var fileIndex = _this3.state.files.findIndex(function (thisFile) {
              return thisFile.name == file.name;
            });
            _this3.state.files.splice(fileIndex, 1);
            _this3.setState({
              files: _this3.state.files
            });
            resolve(file);
          } else if (xhr.readyState == 4 && xhr.status != 200) {
            reject(file);
          }
        });

        formData.append('file', file);
        xhr.send(formData);
      });
    }
  }, {
    key: 'componentDidMount',
    value: function componentDidMount() {
      this.bindDragAndDropEvents();
    }
  }, {
    key: 'render',
    value: function render() {
      var _this4 = this;

      var lang = this.props.lang;
      return React.createElement(
        'div',
        null,
        React.createElement(
          'div',
          { className: 'field-file-upload mt', ref: function ref(dropZone) {
              _this4.dropZone = dropZone;
            } },
          this.state.files.length > 0 && React.createElement(GridImagesProgressTable, { files: this.state.files }),
          this.state.files.length == 0 && React.createElement(
            'div',
            { className: 'field-file-upload__content' },
            lang.grid_images_drop_files,
            React.createElement(
              'em',
              null,
              this.state.directory == 'all' && lang.grid_images_choose_directory,
              this.state.directory != 'all' && lang.grid_images_uploading_to.replace('%s', this.getDirectoryName(this.state.directory))
            )
          ),
          this.state.files.length == 0 && this.props.allowedDirectory == 'all' && React.createElement(
            'div',
            { className: 'field-file-upload__controls' },
            React.createElement(FilterSelect, { key: lang.grid_images_choose_existing,
              center: true,
              keepSelectedState: false,
              title: lang.grid_images_choose_existing,
              placeholder: 'filter directories',
              items: this.props.uploadDestinations,
              onSelect: function onSelect(directory) {
                return _this4.chooseExisting(directory);
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
                _this4.chooseExisting();
              } },
            lang.grid_images_choose_existing
          ),
          '\xA0',
          React.createElement(
            'a',
            { href: '#', className: 'btn action', onClick: function onClick(e) {
                e.preventDefault();
                _this4.uploadNew();
              } },
            lang.grid_images_upload_new
          )
        ),
        this.props.allowedDirectory == 'all' && React.createElement(
          'div',
          { className: 'filter-bar filter-bar--inline' },
          React.createElement(FilterSelect, { key: lang.grid_images_choose_existing,
            action: true,
            keepSelectedState: false,
            title: lang.grid_images_choose_existing,
            placeholder: 'filter directories',
            items: this.props.uploadDestinations,
            onSelect: function onSelect(directory) {
              return _this4.chooseExisting(directory);
            }
          }),
          React.createElement(FilterSelect, { key: lang.grid_images_upload_new,
            action: true,
            keepSelectedState: false,
            title: lang.grid_images_upload_new,
            placeholder: 'filter directories',
            items: this.props.uploadDestinations,
            onSelect: function onSelect(directory) {
              return _this4.uploadNew(directory);
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