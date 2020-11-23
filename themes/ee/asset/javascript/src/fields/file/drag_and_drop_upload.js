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
var DragAndDropUpload =
/*#__PURE__*/
function (_React$Component) {
  _inherits(DragAndDropUpload, _React$Component);

  function DragAndDropUpload(props) {
    var _this;

    _classCallCheck(this, DragAndDropUpload);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(DragAndDropUpload).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "handleDroppedFiles", function (droppedFiles) {
      _this.setState({
        pendingFiles: null
      });

      var files = Array.from(droppedFiles);
      files = files.filter(function (file) {
        return file.type != '';
      });

      if (!_this.props.multiFile && files.length > 1) {
        return _this.setState({
          error: EE.lang.file_dnd_single_file_allowed
        });
      }

      if (_this.props.shouldAcceptFiles && typeof _this.props.shouldAcceptFiles(files) == 'string') {
        var shouldAccept = _this.props.shouldAcceptFiles(files);

        if (typeof shouldAccept == 'string') {
          return _this.setState({
            error: shouldAccept
          });
        }
      }

      files = files.map(function (file) {
        file.progress = 0;

        if (_this.props.contentType == 'image' && !file.type.match(/^image\//)) {
          file.error = EE.lang.file_dnd_images_only;
        }

        return file;
      });

      _this.setState({
        files: _this.state.files.concat(files)
      });

      files = files.filter(function (file) {
        return !file.error;
      });

      _this.queue.enqueue(files, function (file) {
        return _this.makeUploadPromise(file);
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "setDirectory", function (directory) {
      _this.setState({
        directory: directory || 'all'
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "chooseExisting", function (directory) {
      var url = _this.props.filebrowserEndpoint.replace('=all', '=' + directory);

      _this.presentFilepicker(url, false);
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "uploadNew", function (directory) {
      var url = _this.props.uploadEndpoint + '&directory=' + directory;

      _this.presentFilepicker(url, true);
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "assignDropZoneRef", function (dropZone) {
      _this.dropZone = dropZone;

      if (_this.props.assignDropZoneRef) {
        _this.props.assignDropZoneRef(dropZone);
      }
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "removeFile", function (file) {
      var fileIndex = _this.state.files.findIndex(function (thisFile) {
        return thisFile.name == file.name;
      });

      _this.state.files.splice(fileIndex, 1);

      _this.setState({
        files: _this.state.files
      });
    });

    var directoryName = _this.getDirectoryName(props.allowedDirectory);

    _this.state = {
      files: [],
      directory: directoryName ? props.allowedDirectory : 'all',
      directoryName: directoryName,
      pendingFiles: null,
      error: null
    };
    _this.queue = new ConcurrencyQueue({
      concurrency: _this.props.concurrency
    });
    return _this;
  }

  _createClass(DragAndDropUpload, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.bindDragAndDropEvents();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      this.toggleErrorState(false);

      if (this.state.directory != prevState.directory && this.state.pendingFiles) {
        this.handleDroppedFiles(this.state.pendingFiles);
      }

      if (this.state.error && !prevState.error) {
        this.showErrorWithInvalidState(this.state.error);
      }

      if (!this.state.error && prevState.error) {
        this.toggleErrorState(false);
      }

      if (prevState.error) {
        this.setState({
          error: null
        });
      }
    }
  }, {
    key: "getDirectoryName",
    value: function getDirectoryName(directory) {
      if (directory == 'all') return null;
      directory = EE.dragAndDrop.uploadDesinations.find(function (thisDirectory) {
        return thisDirectory.value == directory;
      });
      return directory ? directory.label : directory;
    }
  }, {
    key: "bindDragAndDropEvents",
    value: function bindDragAndDropEvents() {
      var _this2 = this;

      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
        _this2.dropZone.addEventListener(eventName, preventDefaults, false);
      });

      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      } // Handle upload


      this.dropZone.addEventListener('drop', function (e) {
        var droppedFiles = e.dataTransfer.files;

        if (_this2.state.directory == 'all') {
          return _this2.setState({
            pendingFiles: droppedFiles
          });
        }

        _this2.handleDroppedFiles(droppedFiles);
      });

      var highlight = function highlight(e) {
        _this2.dropZone.classList.add('field-file-upload--drop');
      };

      var unhighlight = function unhighlight(e) {
        _this2.dropZone.classList.remove('field-file-upload--drop');
      };

      ['dragenter', 'dragover'].forEach(function (eventName) {
        _this2.dropZone.addEventListener(eventName, highlight, false);
      });
      ['dragleave', 'drop'].forEach(function (eventName) {
        _this2.dropZone.addEventListener(eventName, unhighlight, false);
      });
    }
  }, {
    key: "makeUploadPromise",
    value: function makeUploadPromise(file) {
      var _this3 = this;

      return new Promise(function (resolve, reject) {
        var formData = new FormData();
        formData.append('directory', _this3.state.directory);
        formData.append('csrf_token', EE.CSRF_TOKEN);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', EE.dragAndDrop.endpoint, true);
        xhr.upload.addEventListener('progress', function (e) {
          file.progress = e.loaded * 100.0 / e.total || 100;

          _this3.setState({
            files: _this3.state.files
          });
        });
        xhr.addEventListener('readystatechange', function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);

            switch (response.status) {
              case 'success':
                _this3.removeFile(file);

                _this3.props.onFileUploadSuccess(JSON.parse(xhr.responseText));

                resolve(file);
                break;

              case 'duplicate':
                file.duplicate = true;
                file.fileId = response.fileId;
                file.originalFileName = response.originalFileName;
                reject(file);
                break;

              case 'error':
                file.error = _this3.stripTags(response.error);
                reject(file);
                break;

              default:
                file.error = EE.lang.file_dnd_unexpected_error;
                console.error(xhr);
                reject(file);
                break;
            }
          } // Unexpected error, probably post_max_size is too low
          else if (xhr.readyState == 4 && xhr.status != 200) {
              file.error = EE.lang.file_dnd_unexpected_error;
              console.error(xhr);
              reject(file);
            }

          _this3.setState({
            files: _this3.state.files
          });
        });
        formData.append('file', file);
        xhr.send(formData);
      });
    }
  }, {
    key: "stripTags",
    value: function stripTags(string) {
      var div = document.createElement('div');
      div.innerHTML = string;
      return div.textContent || div.innerText || "";
    }
  }, {
    key: "presentFilepicker",
    value: function presentFilepicker(url, iframe) {
      var _this4 = this;

      var link = $('<a/>', {
        href: url,
        rel: 'modal-file'
      }).FilePicker({
        iframe: iframe,
        callback: function callback(data, references) {
          return _this4.props.onFileUploadSuccess(data);
        }
      });
      link.click();
    }
  }, {
    key: "warningsExist",
    value: function warningsExist() {
      var erroredFile = this.state.files.find(function (file) {
        return file.error || file.duplicate;
      });
      return erroredFile != null || this.state.pendingFiles;
    }
  }, {
    key: "resolveConflict",
    value: function resolveConflict(file, response) {
      this.removeFile(file);
      this.props.onFileUploadSuccess(response);
    }
  }, {
    key: "showErrorWithInvalidState",
    value: function showErrorWithInvalidState(error) {
      this.toggleErrorState(true);
      var errorElement = $(this.dropZone).closest('.field-control').find('> em');

      if (errorElement.size() == 0) {
        errorElement = $('<em/>');
      }

      $(this.dropZone).closest('.field-control').append(errorElement.text(error));
    }
  }, {
    key: "toggleErrorState",
    value: function toggleErrorState(toggle) {
      $(this.dropZone).closest('fieldset, .fieldset-faux').toggleClass('fieldset-invalid', toggle);

      if (!toggle) {
        $(this.dropZone).closest('.field-control').find('> em').remove();
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var heading = this.props.multiFile ? EE.lang.file_dnd_drop_files : EE.lang.file_dnd_drop_file;
      var subheading = this.state.directory == 'all' ? EE.lang.file_dnd_choose_directory : EE.lang.file_dnd_uploading_to.replace('%s', this.getDirectoryName(this.state.directory));

      if (this.state.pendingFiles) {
        heading = EE.lang.file_dnd_choose_file_directory;
        subheading = EE.lang.file_dnd_choose_directory_before_uploading;
      }

      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: "field-file-upload" + (this.props.marginTop ? ' mt' : '') + (this.warningsExist() ? ' field-file-upload---warning' : '') + (this.state.error ? ' field-file-upload---invalid' : ''),
        ref: function ref(dropZone) {
          return _this5.assignDropZoneRef(dropZone);
        }
      }, this.state.files.length > 0 && React.createElement(FileUploadProgressTable, {
        files: this.state.files,
        onFileErrorDismiss: function onFileErrorDismiss(e, file) {
          e.preventDefault();

          _this5.removeFile(file);
        },
        onResolveConflict: function onResolveConflict(file, response) {
          return _this5.resolveConflict(file, response);
        }
      }), this.state.files.length == 0 && React.createElement("div", {
        className: "field-file-upload__content"
      }, heading, React.createElement("em", null, subheading)), this.state.files.length == 0 && this.props.allowedDirectory == 'all' && React.createElement("div", {
        className: "field-file-upload__controls"
      }, React.createElement(FilterSelect, {
        key: EE.lang.file_dnd_choose_existing,
        action: this.state.directory == 'all',
        center: true,
        keepSelectedState: true,
        title: EE.lang.file_dnd_choose_directory_btn,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: EE.dragAndDrop.uploadDesinations,
        onSelect: function onSelect(directory) {
          return _this5.setDirectory(directory);
        }
      }))), this.props.showActionButtons && this.props.allowedDirectory != 'all' && React.createElement(React.Fragment, null, React.createElement("a", {
        href: "#",
        className: "btn action m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          e.preventDefault();

          _this5.chooseExisting(_this5.state.directory);
        }
      }, EE.lang.file_dnd_choose_existing), "\xA0", React.createElement("a", {
        href: "#",
        className: "btn action m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          e.preventDefault();

          _this5.uploadNew(_this5.state.directory);
        }
      }, EE.lang.file_dnd_upload_new)), this.props.showActionButtons && this.props.allowedDirectory == 'all' && React.createElement("div", {
        className: "filter-bar filter-bar--inline"
      }, React.createElement(FilterSelect, {
        key: EE.lang.file_dnd_choose_existing,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_choose_existing,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: EE.dragAndDrop.uploadDesinations,
        onSelect: function onSelect(directory) {
          return _this5.chooseExisting(directory);
        },
        rel: "modal-file",
        itemClass: "m-link"
      }), React.createElement(FilterSelect, {
        key: EE.lang.file_dnd_upload_new,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_upload_new,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: EE.dragAndDrop.uploadDesinations,
        onSelect: function onSelect(directory) {
          return _this5.uploadNew(directory);
        },
        rel: "modal-file",
        itemClass: "m-link"
      })));
    }
  }]);

  return DragAndDropUpload;
}(React.Component);

_defineProperty(DragAndDropUpload, "defaultProps", {
  concurrency: 5,
  showActionButtons: true,
  filebrowserEndpoint: EE.dragAndDrop.filepickerEndpoint,
  uploadEndpoint: EE.dragAndDrop.filepickerUploadEndpoint
});