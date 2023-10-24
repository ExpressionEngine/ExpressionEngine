"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

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
var DragAndDropUpload = /*#__PURE__*/function (_React$Component) {
  _inherits(DragAndDropUpload, _React$Component);

  function DragAndDropUpload(props) {
    var _this;

    _classCallCheck(this, DragAndDropUpload);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(DragAndDropUpload).call(this, props));

    _defineProperty(_assertThisInitialized(_this), "checkChildDirectory", function (items, directory) {
      items.map(function (item) {
        var value;

        if (typeof item.value == 'number') {
          value = item.value;
        } else {
          value = item.value.substr(item.value.indexOf('.') + 1);
          value = parseInt(value);
        }

        if (value == directory) {
          return window.list = item;
        } else if (value != directory && Array.isArray(item.children) && item.children.length) {
          _this.checkChildDirectory(item.children, directory);
        }
      });
      return window.list;
    });

    _defineProperty(_assertThisInitialized(_this), "handleDroppedFiles", function (droppedFiles) {
      _this.setState({
        pendingFiles: null
      });

      var files = Array.from(droppedFiles); // files = files.filter(file => file.type != '')

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

    _defineProperty(_assertThisInitialized(_this), "setDirectory", function (directory) {
      if (directory == 'all' || directory == null) return null;

      if (typeof directory == 'number') {
        directory = directory;
      } else {
        directory = parseInt(directory.substr(directory.indexOf('.') + 1));
      }

      var item = _this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);

      var directory_id;

      if (directory == item.upload_location_id) {
        directory_id = 0;
      } else {
        directory_id = directory;
      }

      _this.setState({
        directory: directory || 'all',
        directory_id: directory_id,
        path: item.path || '',
        upload_location_id: item.upload_location_id || null
      });
    });

    _defineProperty(_assertThisInitialized(_this), "chooseExisting", function (directory) {
      var url = _this.props.filebrowserEndpoint.replace('requested_directory=all', 'requested_directory=' + directory).replace('field_upload_locations=all', 'field_upload_locations=' + _this.props.allowedDirectory);

      _this.presentFilepicker(url, false);

      window.globalDropzone = $(_this.dropZone);
    });

    _defineProperty(_assertThisInitialized(_this), "uploadNew", function (directory) {
      var that = _assertThisInitialized(_this);

      if (typeof directory == 'number') {
        directory = directory;
      } else {
        directory = parseInt(directory.substr(directory.indexOf('.') + 1));
      }

      var item = that.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);
      var directory_id;

      if (directory == item.upload_location_id) {
        directory_id = 0;
      } else {
        directory_id = directory;
      }

      that.setState({
        directory_id: directory_id,
        path: item.path || '',
        upload_location_id: item.upload_location_id || null
      });
      window.globalDropzone = $(_this.dropZone);
      var el = $(_this.dropZone).parents('div[data-file-field-react]');

      if (!el.length) {
        el = $(_this.dropZone).parents('div[data-file-grid-react]');
      }

      el.find('.f_open-filepicker').click();
      el.find('.f_open-filepicker').one('change', function (e) {
        var files = e.target.files;
        that.handleDroppedFiles(files);
      });
    });

    _defineProperty(_assertThisInitialized(_this), "hiddenUpload", function (el) {
      var that = _assertThisInitialized(_this);

      var upload_location_id = el.target.getAttribute('data-upload_location_id');
      var directory_id = el.target.getAttribute('data-directory_id');
      var directory;

      if (directory_id == 0) {
        directory = upload_location_id;
      } else {
        directory = directory_id;
      }

      var item = that.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);
      that.setState({
        directory_id: directory_id,
        path: item.path || '',
        upload_location_id: upload_location_id
      });
      $(_this.dropZone).parents('div[data-file-field-react]').find('.f_open-filepicker').click();
      $(_this.dropZone).parents('div[data-file-field-react]').find('.f_open-filepicker').on('change', function (e) {
        var files = e.target.files;
        that.handleDroppedFiles(files);
      });
    });

    _defineProperty(_assertThisInitialized(_this), "assignDropZoneRef", function (dropZone) {
      _this.dropZone = dropZone;

      if (_this.props.assignDropZoneRef) {
        _this.props.assignDropZoneRef(dropZone);
      }
    });

    _defineProperty(_assertThisInitialized(_this), "removeFile", function (file) {
      var fileIndex = _this.state.files.findIndex(function (thisFile) {
        return thisFile.name == file.name;
      });

      _this.state.files.splice(fileIndex, 1);

      _this.setState({
        files: _this.state.files
      });

      var el = $(_this.dropZone).parents('div[data-file-field-react]');

      if (!el.length) {
        el = $(_this.dropZone).parents('div[data-file-grid-react]');
      }

      el.find('.f_open-filepicker').val('');
    });

    _defineProperty(_assertThisInitialized(_this), "directoryHasChild", function (directory) {
      if (directory == 'all') return null;
      directory = EE.dragAndDrop.uploadDesinations.find(function (thisDirectory) {
        return thisDirectory.value == directory;
      });
      return directory;
    });

    window.list;
    window.globalDropzone;

    var directoryName = _this.getDirectoryName(props.allowedDirectory);

    var _item = _this.getDirectoryItem(props.allowedDirectory);

    _this.state = {
      files: [],
      directory: directoryName ? props.allowedDirectory : 'all',
      directoryName: directoryName,
      pendingFiles: null,
      error: null,
      path: _item.path,
      upload_location_id: _item.upload_location_id,
      //main folder ID
      directory_id: _item.directory_id //subfolder ID

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
      var directory = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);

      if (typeof directory === 'undefined') {
        return ' ';
      }

      return directory.label;
    }
  }, {
    key: "getDirectoryItem",
    value: function getDirectoryItem(directory) {
      if (directory == 'all') {
        var directory = {
          upload_location_id: null,
          path: '',
          directory_id: 0
        };
      } else {
        var directory = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);

        if (typeof directory === 'undefined') {
          return {
            upload_location_id: null,
            path: '',
            directory_id: 0
          };
        }

        if (directory.value == directory.upload_location_id) {
          directory.directory_id = 0;
        } else {
          directory.directory_id = parseInt(directory.value.substr(directory.value.indexOf('.') + 1));
        }
      }

      return directory;
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

        window.globalDropzone = $(_this2.dropZone);

        _this2.handleDroppedFiles(droppedFiles);
      });

      var highlight = function highlight(e) {
        _this2.dropZone.classList.add('file-field__dropzone--dragging');
      };

      var unhighlight = function unhighlight(e) {
        _this2.dropZone.classList.remove('file-field__dropzone--dragging');
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
        formData.append('directory_id', _this3.state.directory_id);
        formData.append('csrf_token', EE.CSRF_TOKEN);
        formData.append('upload_location_id', _this3.state.upload_location_id);
        formData.append('path', _this3.state.path);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', EE.dragAndDrop.endpoint, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.addEventListener('progress', function (e) {
          if ($('.file-upload-widget').hasClass('open-dd')) {
            $('.file-upload-widget').css({
              'height': 'auto',
              'position': 'static'
            });
          }

          if ($('.file-upload-widget').length && $('.file-upload-widget').hasClass('hidden')) {
            $('.file-upload-widget').show();
          }

          file.progress = e.loaded * 100.0 / e.total || 100;

          _this3.setState({
            files: _this3.state.files
          });
        });
        xhr.addEventListener('readystatechange', function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            var _response = JSON.parse(xhr.responseText);

            switch (_response.status) {
              case 'success':
                _this3.removeFile(file);

                resolve(file);

                if ($('div[data-file-field-react]').find('.file-field__items .list-item').length > 0) {
                  if ($('div[data-file-field-react]').parent().hasClass('file-upload-widget')) {
                    $('div[data-file-field-react]').parent().show();
                  }
                } else {
                  if ($('.file-upload-widget').length) {
                    $('.file-upload-widget').hide();
                    $('body .f_manager-wrapper > form').submit();
                  }

                  _this3.props.onFileUploadSuccess(JSON.parse(xhr.responseText), window.globalDropzone);
                }

                break;

              case 'duplicate':
                file.duplicate = true;
                file.fileId = _response.fileId;
                file.originalFileName = _response.originalFileName;

                if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                  window.globalDropzone.parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
                    $(this).attr('disabled', 'disabled');
                  });
                }

                if ($('.title-bar a.upload').length) {
                  $('.title-bar a.upload').addClass('disabled');
                }

                if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                  $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled');
                }

                reject(file);
                break;

              case 'error':
                file.error = _this3.stripTags(_response.error);

                if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                  $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
                    $(this).attr('disabled', 'disabled');
                  });
                }

                if ($('.title-bar a.upload').length) {
                  $('.title-bar a.upload').addClass('disabled');
                }

                if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                  $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled');
                }

                reject(file);
                break;

              default:
                if (typeof _response.message !== 'undefined') {
                  file.error = _response.message;
                } else {
                  file.error = EE.lang.file_dnd_unexpected_error;
                }

                if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                  $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
                    $(this).attr('disabled', 'disabled');
                  });
                }

                if ($('.title-bar a.upload').length) {
                  $('.title-bar a.upload').addClass('disabled');
                }

                if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                  $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled');
                }

                console.error(xhr);
                reject(file);
                break;
            }
          } // Unexpected error, probably post_max_size is too low
          else if (xhr.readyState == 4 && xhr.status != 200) {
            file.error = EE.lang.file_dnd_unexpected_error;

            try {
              var response = JSON.parse(xhr.responseText);

              if (typeof response.error != 'undefined') {
                file.error = response.error;
              } else if (typeof response.message !== 'undefined') {
                file.error = response.message;
              }
            } catch (err) {}

            if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
              $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
                $(this).attr('disabled', 'disabled');
              });
            }

            if ($('.title-bar a.upload').length) {
              $('.title-bar a.upload').addClass('disabled');
            }

            if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
              $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled');
            }

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

      if ($('div[data-file-field-react]').find('.file-field__items .list-item').length > 0) {
        if ($('div[data-file-field-react]').parent().hasClass('file-upload-widget')) {
          $('div[data-file-field-react]').parent().show();
        }
      } else {
        this.props.onFileUploadSuccess(response, window.globalDropzone);

        if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
          $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
            $(this).removeAttr('disabled');
          });
        }

        if ($('.title-bar a.upload').length) {
          $('.title-bar a.upload').removeClass('disabled');
        }

        if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
          $('.main-nav .main-nav__toolbar .js-dropdown-toggle').removeAttr('disabled');
        }

        if ($('.file-upload-widget').length) {
          if ($('.file-upload-widget').hasClass('open-dd')) {
            $('.file-upload-widget').removeClass('open-dd');
            $('.file-upload-widget').removeAttr('style');
          }

          $('.file-upload-widget').hide();
          $('body .f_manager-wrapper > form').submit();
        }
      }
    }
  }, {
    key: "showErrorWithInvalidState",
    value: function showErrorWithInvalidState(error) {
      this.toggleErrorState(true);
      var errorElement = $(this.dropZone).closest('.field-control').find('> em');

      if (errorElement.length == 0) {
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
      var subheading = this.state.directory == 'all' ? EE.lang.file_dnd_choose_directory : EE.lang.file_dnd_upload_to + ' ';

      if (this.state.pendingFiles) {
        heading = EE.lang.file_dnd_choose_file_directory;
        subheading = EE.lang.file_dnd_choose_directory_before_uploading;
      }

      var selectedDirectoryNotInList = false;

      if (this.state.directory != 'all') {
        var dir = this.state.directory;

        if (EE.dragAndDrop.uploadDesinations.length != 0) {
          selectedDirectoryNotInList = true;
          var dir_in_list = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, dir);

          if (typeof dir_in_list != 'undefined') {
            selectedDirectoryNotInList = false;
          }
        }
      }

      if (EE.dragAndDrop.uploadDesinations.length == 0 || selectedDirectoryNotInList) {
        heading = EE.lang.file_dnd_no_directories;
        subheading = EE.lang.file_dnd_no_directories_desc;
        this.props.showActionButtons = false;
      }

      var checkChildren = this.directoryHasChild(this.props.allowedDirectory);
      var uploadDirectoriesForDropdown = EE.dragAndDrop.uploadDesinations;

      if (typeof this.props.roleAllowedDirectoryIds !== 'undefined' && this.props.roleAllowedDirectoryIds.length > 0) {
        uploadDirectoriesForDropdown = [];
        var roleAllowedDirectoryIds = this.props.roleAllowedDirectoryIds;
        Object.values(EE.dragAndDrop.uploadDesinations).forEach(function (uploadDesination) {
          if (roleAllowedDirectoryIds.includes(uploadDesination.value)) {
            uploadDirectoriesForDropdown.push(uploadDesination);
          }
        });
      }

      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: "file-field" + (this.props.marginTop ? ' mt' : '') + (this.warningsExist() ? ' file-field--warning' : '') + (this.state.error ? ' file-field--invalid' : '')
      }, React.createElement("div", {
        style: {
          display: this.state.files.length == 0 ? 'block' : 'none'
        },
        className: "file-field__dropzone",
        ref: function ref(dropZone) {
          return _this5.assignDropZoneRef(dropZone);
        }
      }, !this.props.showActionButtons && React.createElement("p", {
        "class": "file-field_upload-icon"
      }, React.createElement("i", {
        "class": "fal fa-cloud-upload-alt"
      })), this.state.files.length == 0 && React.createElement(React.Fragment, null, React.createElement("div", {
        className: "file-field__dropzone-title"
      }, heading), React.createElement("div", {
        "class": "file-field__dropzone-button"
      }, subheading, this.state.directory == 'all' && uploadDirectoriesForDropdown.length > 0 && ':', this.state.directory != 'all' && React.createElement("b", null, this.getDirectoryName(this.state.directory)), "\xA0", this.state.directory != 'all' && this.props.allowedDirectory != 'all' && checkChildren && checkChildren.children.length > 0 && React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_choose_existing,
        action: this.state.directory == 'all',
        center: true,
        keepSelectedState: true,
        title: EE.lang.file_dnd_choose_directory_btn,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: [checkChildren],
        onSelect: function onSelect(directory) {
          return _this5.setDirectory(directory);
        },
        buttonClass: "button--default button--small",
        createNewDirectory: this.props.createNewDirectory,
        ignoreChild: false,
        addInput: false
      }), this.state.files.length == 0 && this.props.allowedDirectory == 'all' && uploadDirectoriesForDropdown.length > 0 && React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_choose_existing,
        action: this.state.directory == 'all',
        center: true,
        keepSelectedState: true,
        title: EE.lang.file_dnd_choose_directory_btn,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: uploadDirectoriesForDropdown,
        onSelect: function onSelect(directory) {
          return _this5.setDirectory(directory);
        },
        buttonClass: "button--default button--small",
        createNewDirectory: this.props.createNewDirectory,
        ignoreChild: false,
        addInput: false
      })), React.createElement("div", {
        "class": "file-field__dropzone-icon"
      }, React.createElement("i", {
        "class": "fal fa-cloud-upload-alt"
      })))), this.state.files.length > 0 && React.createElement(FileUploadProgressTable, {
        files: this.state.files,
        onFileErrorDismiss: function onFileErrorDismiss(e, file) {
          e.preventDefault();

          _this5.removeFile(file);

          if ($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
            $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function () {
              $(this).removeAttr('disabled');
            });
          }

          if ($('.title-bar a.upload').length) {
            $('.title-bar a.upload').removeClass('disabled');
          }

          if ($('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
            $('.main-nav .main-nav__toolbar .js-dropdown-toggle').removeAttr('disabled');
          }

          if ($('.file-upload-widget').length) {
            if ($('.file-upload-widget').hasClass('hidden')) {
              $('.file-upload-widget').hide();
            }

            $('body .f_manager-wrapper > form').submit();
          }
        },
        onResolveConflict: function onResolveConflict(file, response) {
          return _this5.resolveConflict(file, response);
        }
      })), React.createElement("div", {
        className: "file-field__buttons"
      }, this.props.showActionButtons && this.props.allowedDirectory != 'all' && checkChildren && checkChildren.children.length > 0 && React.createElement("div", {
        className: "button-segment"
      }, React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_choose_existing,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_choose_existing,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: [checkChildren],
        onSelect: function onSelect(directory) {
          return _this5.chooseExisting(directory);
        },
        rel: "modal-file",
        itemClass: "m-link",
        buttonClass: "button--default button--small",
        createNewDirectory: false,
        ignoreChild: true,
        addInput: false
      }), React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_upload_new,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_upload_new,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: [checkChildren],
        onSelect: function onSelect(directory) {
          return _this5.uploadNew(directory);
        },
        buttonClass: "button--default button--small",
        createNewDirectory: this.props.createNewDirectory,
        ignoreChild: false,
        addInput: true
      })), this.props.showActionButtons && this.props.allowedDirectory != 'all' && (!checkChildren || checkChildren.children.length <= 0) && React.createElement(React.Fragment, null, React.createElement("div", {
        className: "button-segment"
      }, React.createElement("a", {
        href: "#",
        className: "button button--default button--small m-link",
        rel: "modal-file",
        onClick: function onClick(e) {
          e.preventDefault();

          _this5.chooseExisting(_this5.state.directory);
        }
      }, EE.lang.file_dnd_choose_existing), React.createElement("a", {
        href: "#",
        className: "button button--default button--small m-link",
        onClick: function onClick(e) {
          e.preventDefault();

          _this5.uploadNew(_this5.state.directory);
        }
      }, EE.lang.file_dnd_upload_new), React.createElement("label", {
        htmlFor: "f_open-filepicker_id",
        className: "sr-only"
      }, EE.lang.hidden_input), React.createElement("input", {
        id: "f_open-filepicker_id",
        type: "file",
        className: "f_open-filepicker",
        style: {
          display: 'none'
        },
        multiple: "multiple"
      }))), this.props.showActionButtons && this.props.allowedDirectory == 'all' && React.createElement("div", {
        className: "button-segment"
      }, React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_choose_existing,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_choose_existing,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: uploadDirectoriesForDropdown,
        onSelect: function onSelect(directory) {
          return _this5.chooseExisting(directory);
        },
        rel: "modal-file",
        itemClass: "m-link",
        buttonClass: "button--default button--small",
        createNewDirectory: false,
        ignoreChild: true,
        addInput: false
      }), React.createElement(DropDownButton, {
        key: EE.lang.file_dnd_upload_new,
        action: true,
        keepSelectedState: false,
        title: EE.lang.file_dnd_upload_new,
        placeholder: EE.lang.file_dnd_filter_directories,
        items: uploadDirectoriesForDropdown,
        onSelect: function onSelect(directory) {
          return _this5.uploadNew(directory);
        },
        buttonClass: "button--default button--small",
        createNewDirectory: this.props.createNewDirectory,
        ignoreChild: false,
        addInput: true
      })), this.props.imitationButton && React.createElement(React.Fragment, null, React.createElement("a", {
        href: "#",
        style: {
          display: 'none'
        },
        onClick: function onClick(el) {
          return _this5.hiddenUpload(el);
        },
        "data-upload_location_id": '',
        "data-directory_id": '',
        "data-path": '',
        className: "imitation_button"
      }, "Imitation"), React.createElement("label", {
        htmlFor: "f_open-filepicker_id",
        className: "sr-only"
      }, EE.lang.hidden_input), React.createElement("input", {
        id: "f_open-filepicker_id",
        type: "file",
        className: "f_open-filepicker",
        style: {
          display: 'none'
        },
        multiple: "multiple"
      }))));
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