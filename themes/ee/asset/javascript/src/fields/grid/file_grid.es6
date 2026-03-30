/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

class FileGrid extends React.Component {

  static renderFields(context = document) {
    $('div[data-file-grid-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('fileGridReact')))
      ReactDOM.render(React.createElement(FileGrid, props, null), this)
    })
  }

  shouldAcceptFiles = (files) => {
    if (this.props.maxRows !== '') {
      if (files.length + this.getRowCount() > this.props.maxRows) {
        return EE.lang.file_grid_maximum_rows_hit.replace('%s', this.props.maxRows)
      }
    }
    return true
  }

  addFileToGrid = (response) => {
    let fileField = this.getGridInstance()._addRow()
      .find('.grid-file-upload')
      .first()

    EE.FileField.pickerCallback(response, {
      input_value: fileField.find('input:hidden').first(),
      input_img: fileField.find('img').first(),
      modal: $('.modal-file')
    })

    window.setTimeout(() => {
      $(this.dropZone)
        .closest('.js-file-grid')
        .trigger('fileGrid:rowsChanged')
    }, 0)
  }

  getGridInstance() {
    if ( ! this.gridInstance) {
      this.gridInstance = $(this.dropZone)
        .closest('.js-file-grid')
        .find('.grid-field')
        .data('GridInstance')
    }

    return this.gridInstance
  }

  getRowCount() {
    return this.getGridInstance()._getRows().length
  }

  render() {
    return <DragAndDropUpload
      {...this.props}
      onFileUploadSuccess={this.addFileToGrid}
      assignDropZoneRef={(dropZone) => { this.dropZone = dropZone }}
      shouldAcceptFiles={this.shouldAcceptFiles}
      marginTop={true}
      multiFile={true}
    />
  }
}

class FileGridGallery {
  static initFields(context = document) {
    $('.js-file-grid', context).each(function () {
      let existing = $(this).data('fileGridGallery')

      if (existing) {
        existing.refresh()
        return
      }

      $(this).data('fileGridGallery', new FileGridGallery(this))
    })
  }

  constructor(root) {
    this.root = $(root)
    this.grid = this.root.find('.grid-field').first()
    this.gallery = this.root.find('.js-file-grid-gallery')
    this.galleryGrid = this.root.find('.js-file-grid-gallery-grid')
    this.galleryEmpty = this.root.find('.js-file-grid-gallery-empty')
    this.viewToggles = this.root.find('.js-file-grid-view-toggle')

    this.rowKeyCounter = 0

    this.refresh = this.debounce(() => {
      this.renderGallery()
    }, 80)

    this.bindEvents()
    this.toggleView('table')
    this.renderGallery()
  }

  bindEvents() {
    this.viewToggles.on('click', (event) => {
      event.preventDefault()
      let view = $(event.currentTarget).data('fileGridView')
      this.toggleView(view)
    })

    this.root.on('fileGrid:rowsChanged', () => {
      this.refresh()
    })

    this.grid.on('grid:addRow', () => {
      this.refresh()
    })

    this.grid.find('.grid-field__table > tbody').on('sortstop', () => {
      this.refresh()
    })

    this.root.on('change', '.grid-file-upload .js-file-input', () => {
      this.refresh()
    })

    this.root.on('click', '.grid-field [rel="remove_row"], .grid-file-upload .remove, .grid-file-upload .filepicker, .grid-file-upload .edit-meta', () => {
      window.setTimeout(() => {
        this.refresh()
      }, 80)
    })
  }

  getGridInstance() {
    if ( ! this.gridInstance) {
      this.gridInstance = this.grid.data('GridInstance')
    }

    return this.gridInstance
  }

  toggleView(view) {
    let isGallery = view === 'gallery'

    this.root.toggleClass('file-grid--gallery-mode', isGallery)
    this.gallery.toggleClass('hidden', ! isGallery)

    this.viewToggles.each(function () {
      let button = $(this)
      let buttonIsActive = button.data('fileGridView') === view

      button.toggleClass('is-active', buttonIsActive)
      button.toggleClass('button--secondary', buttonIsActive)
      button.toggleClass('button--default', ! buttonIsActive)
    })

    if (isGallery) {
      this.renderGallery()
    }
  }

  renderGallery() {
    let gridInstance = this.getGridInstance()

    if ( ! gridInstance || ! gridInstance._getRows) {
      return
    }

    let rowsData = this.getRowsData()

    if (rowsData.length === 0) {
      this.galleryGrid.empty()
      this.galleryEmpty
        .removeClass('hidden')
        .text(this.getEmptyMessage())
      this.destroySortable()
      return
    }

    this.galleryEmpty.addClass('hidden').empty()

    this.galleryGrid.html(rowsData.map((rowData) => this.buildCard(rowData)).join(''))

    this.initSortable()
  }

  getRowsData() {
    let gridInstance = this.getGridInstance()

    if ( ! gridInstance || ! gridInstance._getRows) {
      return []
    }

    return gridInstance._getRows().toArray().map((row) => {
      let rowElement = $(row)
      let key = this.ensureRowKey(rowElement)
      let fileCell = rowElement.find('.grid-file-upload').first()
      let fileName = $.trim(fileCell.find('.fields-upload-chosen-name > div').first().text())

      if (fileName === '') {
        fileName = $.trim(fileCell.find('.fields-upload-chosen-name').first().text())
      }

      return {
        key,
        previewHtml: this.getPreviewHtml(fileCell),
        fileName: fileName || 'No file selected'
      }
    })
  }

  getPreviewHtml(fileCell) {
    let previewFigure = fileCell.find('.fields-upload-chosen-file figure').first().clone()

    if (previewFigure.length === 0) {
      return '<span class="file-grid-gallery__thumb-placeholder"><i class="fal fa-file"></i></span>'
    }

    previewFigure.find('[id]').removeAttr('id')
    previewFigure.find('img.js-file-image.hidden').remove()
    previewFigure.find('img').filter(function () {
      return ($(this).attr('src') || '').indexOf('missing.jpg') !== -1
    }).remove()

    if (previewFigure.children().length === 0 && $.trim(previewFigure.text()) === '') {
      return '<span class="file-grid-gallery__thumb-placeholder"><i class="fal fa-file"></i></span>'
    }

    return $('<div>').append(previewFigure).html()
  }

  ensureRowKey(rowElement) {
    let key = rowElement.attr('data-file-grid-row-key')

    if ( ! key) {
      this.rowKeyCounter += 1
      key = `file-grid-row-${this.rowKeyCounter}`
      rowElement.attr('data-file-grid-row-key', key)
    }

    return key
  }

  buildCard(rowData) {
    return `
      <div class="file-grid-gallery__card" data-row-key="${this.escapeHtml(rowData.key)}">
        <button type="button" class="file-grid-gallery__drag" title="Drag to reorder" aria-label="Drag to reorder">
          <i class="fal fa-grip-vertical"></i>
        </button>
        <div class="file-grid-gallery__thumb">${rowData.previewHtml}</div>
        <div class="file-grid-gallery__meta">
          <div class="file-grid-gallery__name">${this.escapeHtml(rowData.fileName)}</div>
        </div>
      </div>
    `
  }

  initSortable() {
    if (this.galleryGrid.data('ui-sortable')) {
      this.galleryGrid.sortable('destroy')
    }

    this.galleryGrid.sortable({
      items: '> .file-grid-gallery__card',
      handle: '.file-grid-gallery__drag',
      cancel: '',
      start: function (event, ui) {
        ui.item.addClass('is-sorting')
      },
      stop: (event, ui) => {
        ui.item.removeClass('is-sorting')
        this.applyGalleryOrder()
      }
    })
  }

  destroySortable() {
    if (this.galleryGrid.data('ui-sortable')) {
      this.galleryGrid.sortable('destroy')
    }
  }

  applyGalleryOrder() {
    let gridInstance = this.getGridInstance()

    if ( ! gridInstance || ! gridInstance._getRows) {
      return
    }

    let rowContainer = gridInstance.rowContainer
    let tableActions = gridInstance.tableActions && gridInstance.tableActions.length
      ? gridInstance.tableActions.first()
      : null

    let allRows = gridInstance._getRows().toArray()
    let rowMap = {}

    allRows.forEach((row) => {
      let rowElement = $(row)
      rowMap[this.ensureRowKey(rowElement)] = row
    })

    let orderedKeys = this.galleryGrid.children('.file-grid-gallery__card').map(function () {
      return $(this).data('rowKey')
    }).get()

    if (orderedKeys.length === 0) {
      return
    }

    let orderedRows = orderedKeys
      .map((key) => rowMap[key])
      .filter((row) => !! row)

    if (orderedRows.length === 0) {
      return
    }

    let remainingRows = allRows.filter((row) => orderedRows.indexOf(row) === -1)
    let finalRows = orderedRows.concat(remainingRows)

    finalRows.forEach((row) => {
      if (tableActions && tableActions.length) {
        tableActions.before(row)
      } else {
        rowContainer.append(row)
      }
    })

    gridInstance._updateRowCounter()
    $(document).trigger('entry:preview')
    this.refresh()
  }

  getEmptyMessage() {
    return 'No files selected yet.'
  }

  escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
  }

  debounce(func, wait) {
    let timeout

    return (...args) => {
      window.clearTimeout(timeout)
      timeout = window.setTimeout(() => func.apply(this, args), wait)
    }
  }
}

$(document).ready(function () {
  FileGrid.renderFields()
  FileGridGallery.initFields()
})

FluidField.on('file_grid', 'add', function(field) {
  EE.grid($('.grid-field', field))
  FileGrid.renderFields(field)
  FileGridGallery.initFields(field)
})
