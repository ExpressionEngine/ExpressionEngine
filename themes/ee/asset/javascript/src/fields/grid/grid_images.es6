/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class GridImages extends React.Component {

  static renderFields(context) {
    $('div[data-grid-images-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('gridImagesReact')))
      ReactDOM.render(React.createElement(GridImages, props, null), this)
    })
  }

  addFileToGrid = (response) => {
    let gridInstance = $(this.dropZone)
      .closest('.js-grid-images')
      .find('.grid-input-form')
      .data('GridInstance')

    let fileField = gridInstance._addRow()
      .find('.grid-file-upload')
      .first()

    EE.FileField.pickerCallback(response, {
      input_value: fileField.find('input:hidden').first(),
      input_img: fileField.find('img').first(),
      modal: $('.modal-file')
    })
  }

  render() {
    return <DragAndDropUpload
      {...this.props}
      onFileUploadSuccess={this.addFileToGrid}
      assignDropZoneRef={(dropZone) => { this.dropZone = dropZone }}
    />
  }
}

$(document).ready(function () {
  GridImages.renderFields()
})

FluidField.on('grid_images', 'add', function(field) {
  GridImages.renderFields(field)
})
