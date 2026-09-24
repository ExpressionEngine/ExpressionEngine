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
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */
var FileGrid = /*#__PURE__*/function (_React$Component) {
  _inherits(FileGrid, _React$Component);

  function FileGrid() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, FileGrid);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(FileGrid)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "shouldAcceptFiles", function (files) {
      if (_this.props.maxRows !== '') {
        if (files.length + _this.getRowCount() > _this.props.maxRows) {
          return EE.lang.file_grid_maximum_rows_hit.replace('%s', _this.props.maxRows);
        }
      }

      return true;
    });

    _defineProperty(_assertThisInitialized(_this), "addFileToGrid", function (response) {
      var fileField = _this.getGridInstance()._addRow().find('.grid-file-upload').first();

      EE.FileField.pickerCallback(response, {
        input_value: fileField.find('input:hidden').first(),
        input_img: fileField.find('img').first(),
        modal: $('.modal-file')
      });
      window.setTimeout(function () {
        $(_this.dropZone).closest('.js-file-grid').trigger('fileGrid:rowsChanged');
      }, 0);
    });

    return _this;
  }

  _createClass(FileGrid, [{
    key: "getGridInstance",
    value: function getGridInstance() {
      if (!this.gridInstance) {
        this.gridInstance = $(this.dropZone).closest('.js-file-grid').find('.grid-field').data('GridInstance');
      }

      return this.gridInstance;
    }
  }, {
    key: "getRowCount",
    value: function getRowCount() {
      return this.getGridInstance()._getRows().length;
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
    value: function renderFields() {
      var context = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
      $('div[data-file-grid-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('fileGridReact')));
        ReactDOM.render(React.createElement(FileGrid, props, null), this);
      });
    }
  }]);

  return FileGrid;
}(React.Component);

var FileGridGallery = /*#__PURE__*/function () {
  _createClass(FileGridGallery, null, [{
    key: "initFields",
    value: function initFields() {
      var context = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
      $('.js-file-grid', context).each(function () {
        var existing = $(this).data('fileGridGallery');

        if (existing) {
          existing.refresh();
          return;
        }

        $(this).data('fileGridGallery', new FileGridGallery(this));
      });
    }
  }]);

  function FileGridGallery(root) {
    var _this3 = this;

    _classCallCheck(this, FileGridGallery);

    this.root = $(root);
    this.grid = this.root.find('.grid-field').first();
    this.gallery = this.root.find('.js-file-grid-gallery');
    this.galleryGrid = this.root.find('.js-file-grid-gallery-grid');
    this.galleryEmpty = this.root.find('.js-file-grid-gallery-empty');
    this.viewToggles = this.root.find('.js-file-grid-view-toggle');
    this.rowKeyCounter = 0;
    this.refresh = this.debounce(function () {
      _this3.renderGallery();
    }, 80);
    this.bindEvents();
    this.toggleView('table');
    this.renderGallery();
  }

  _createClass(FileGridGallery, [{
    key: "bindEvents",
    value: function bindEvents() {
      var _this4 = this;

      this.viewToggles.on('click', function (event) {
        event.preventDefault();
        var view = $(event.currentTarget).data('fileGridView');

        _this4.toggleView(view);
      });
      this.root.on('fileGrid:rowsChanged', function () {
        _this4.refresh();
      });
      this.grid.on('grid:addRow', function () {
        _this4.refresh();
      });
      this.grid.find('.grid-field__table > tbody').on('sortstop', function () {
        _this4.refresh();
      });
      this.root.on('change', '.grid-file-upload .js-file-input', function () {
        _this4.refresh();
      });
      this.root.on('click', '.grid-field [rel="remove_row"], .grid-file-upload .remove, .grid-file-upload .filepicker, .grid-file-upload .edit-meta', function () {
        window.setTimeout(function () {
          _this4.refresh();
        }, 80);
      });
    }
  }, {
    key: "getGridInstance",
    value: function getGridInstance() {
      if (!this.gridInstance) {
        this.gridInstance = this.grid.data('GridInstance');
      }

      return this.gridInstance;
    }
  }, {
    key: "toggleView",
    value: function toggleView(view) {
      var isGallery = view === 'gallery';
      this.root.toggleClass('file-grid--gallery-mode', isGallery);
      this.gallery.toggleClass('hidden', !isGallery);
      this.viewToggles.each(function () {
        var button = $(this);
        var buttonIsActive = button.data('fileGridView') === view;
        button.toggleClass('is-active', buttonIsActive);
        button.toggleClass('button--secondary', buttonIsActive);
        button.toggleClass('button--default', !buttonIsActive);
      });

      if (isGallery) {
        this.renderGallery();
      }
    }
  }, {
    key: "renderGallery",
    value: function renderGallery() {
      var _this5 = this;

      var gridInstance = this.getGridInstance();

      if (!gridInstance || !gridInstance._getRows) {
        return;
      }

      var rowsData = this.getRowsData();

      if (rowsData.length === 0) {
        this.galleryGrid.empty();
        this.galleryEmpty.removeClass('hidden').text(this.getEmptyMessage());
        this.destroySortable();
        return;
      }

      this.galleryEmpty.addClass('hidden').empty();
      this.galleryGrid.html(rowsData.map(function (rowData) {
        return _this5.buildCard(rowData);
      }).join(''));
      this.initSortable();
    }
  }, {
    key: "getRowsData",
    value: function getRowsData() {
      var _this6 = this;

      var gridInstance = this.getGridInstance();

      if (!gridInstance || !gridInstance._getRows) {
        return [];
      }

      return gridInstance._getRows().toArray().map(function (row) {
        var rowElement = $(row);

        var key = _this6.ensureRowKey(rowElement);

        var fileCell = rowElement.find('.grid-file-upload').first();
        var fileName = $.trim(fileCell.find('.fields-upload-chosen-name > div').first().text());

        if (fileName === '') {
          fileName = $.trim(fileCell.find('.fields-upload-chosen-name').first().text());
        }

        return {
          key: key,
          previewHtml: _this6.getPreviewHtml(fileCell),
          fileName: fileName || 'No file selected'
        };
      });
    }
  }, {
    key: "getPreviewHtml",
    value: function getPreviewHtml(fileCell) {
      var previewFigure = fileCell.find('.fields-upload-chosen-file figure').first().clone();

      if (previewFigure.length === 0) {
        return '<span class="file-grid-gallery__thumb-placeholder"><i class="fal fa-file"></i></span>';
      }

      previewFigure.find('[id]').removeAttr('id');
      previewFigure.find('img.js-file-image.hidden').remove();
      previewFigure.find('img').filter(function () {
        return ($(this).attr('src') || '').indexOf('missing.jpg') !== -1;
      }).remove();

      if (previewFigure.children().length === 0 && $.trim(previewFigure.text()) === '') {
        return '<span class="file-grid-gallery__thumb-placeholder"><i class="fal fa-file"></i></span>';
      }

      return $('<div>').append(previewFigure).html();
    }
  }, {
    key: "ensureRowKey",
    value: function ensureRowKey(rowElement) {
      var key = rowElement.attr('data-file-grid-row-key');

      if (!key) {
        this.rowKeyCounter += 1;
        key = "file-grid-row-".concat(this.rowKeyCounter);
        rowElement.attr('data-file-grid-row-key', key);
      }

      return key;
    }
  }, {
    key: "buildCard",
    value: function buildCard(rowData) {
      return "\n      <div class=\"file-grid-gallery__card\" data-row-key=\"".concat(this.escapeHtml(rowData.key), "\">\n        <button type=\"button\" class=\"file-grid-gallery__drag\" title=\"Drag to reorder\" aria-label=\"Drag to reorder\">\n          <i class=\"fal fa-grip-vertical\"></i>\n        </button>\n        <div class=\"file-grid-gallery__thumb\">").concat(rowData.previewHtml, "</div>\n        <div class=\"file-grid-gallery__meta\">\n          <div class=\"file-grid-gallery__name\">").concat(this.escapeHtml(rowData.fileName), "</div>\n        </div>\n      </div>\n    ");
    }
  }, {
    key: "initSortable",
    value: function initSortable() {
      var _this7 = this;

      if (this.galleryGrid.data('ui-sortable')) {
        this.galleryGrid.sortable('destroy');
      }

      this.galleryGrid.sortable({
        items: '> .file-grid-gallery__card',
        handle: '.file-grid-gallery__drag',
        cancel: '',
        start: function start(event, ui) {
          ui.item.addClass('is-sorting');
        },
        stop: function stop(event, ui) {
          ui.item.removeClass('is-sorting');

          _this7.applyGalleryOrder();
        }
      });
    }
  }, {
    key: "destroySortable",
    value: function destroySortable() {
      if (this.galleryGrid.data('ui-sortable')) {
        this.galleryGrid.sortable('destroy');
      }
    }
  }, {
    key: "applyGalleryOrder",
    value: function applyGalleryOrder() {
      var _this8 = this;

      var gridInstance = this.getGridInstance();

      if (!gridInstance || !gridInstance._getRows) {
        return;
      }

      var rowContainer = gridInstance.rowContainer;
      var tableActions = gridInstance.tableActions && gridInstance.tableActions.length ? gridInstance.tableActions.first() : null;

      var allRows = gridInstance._getRows().toArray();

      var rowMap = {};
      allRows.forEach(function (row) {
        var rowElement = $(row);
        rowMap[_this8.ensureRowKey(rowElement)] = row;
      });
      var orderedKeys = this.galleryGrid.children('.file-grid-gallery__card').map(function () {
        return $(this).data('rowKey');
      }).get();

      if (orderedKeys.length === 0) {
        return;
      }

      var orderedRows = orderedKeys.map(function (key) {
        return rowMap[key];
      }).filter(function (row) {
        return !!row;
      });

      if (orderedRows.length === 0) {
        return;
      }

      var remainingRows = allRows.filter(function (row) {
        return orderedRows.indexOf(row) === -1;
      });
      var finalRows = orderedRows.concat(remainingRows);
      finalRows.forEach(function (row) {
        if (tableActions && tableActions.length) {
          tableActions.before(row);
        } else {
          rowContainer.append(row);
        }
      });

      gridInstance._updateRowCounter();

      $(document).trigger('entry:preview');
      this.refresh();
    }
  }, {
    key: "getEmptyMessage",
    value: function getEmptyMessage() {
      return 'No files selected yet.';
    }
  }, {
    key: "escapeHtml",
    value: function escapeHtml(value) {
      return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
  }, {
    key: "debounce",
    value: function debounce(func, wait) {
      var _this9 = this;

      var timeout;
      return function () {
        for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
          args[_key2] = arguments[_key2];
        }

        window.clearTimeout(timeout);
        timeout = window.setTimeout(function () {
          return func.apply(_this9, args);
        }, wait);
      };
    }
  }]);

  return FileGridGallery;
}();

$(document).ready(function () {
  FileGrid.renderFields();
  FileGridGallery.initFields();
});
FluidField.on('file_grid', 'add', function (field) {
  EE.grid($('.grid-field', field));
  FileGrid.renderFields(field);
  FileGridGallery.initFields(field);
});