/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class FileGrid extends React.Component {

  static renderFields(context) {
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
  }

  getGridInstance() {
    if ( ! this.gridInstance) {
      this.gridInstance = $(this.dropZone)
        .closest('.js-file-grid')
        .find('.grid-input-form')
        .data('GridInstance')
    }

    return this.gridInstance
  }

  getRowCount() {
    return this.getGridInstance()._getRows().size()
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

$(document).ready(function () {
  FileGrid.renderFields()
})

FluidField.on('file_grid', 'add', function(field) {
  EE.grid($('table[data-grid-settings]', field))
  FileGrid.renderFields(field)
})
