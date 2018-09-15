/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class GridImages extends React.Component {
  constructor (props) {
    super(props)
  }

  static renderFields(context) {
    $('div[data-grid-images-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('gridImagesReact')))
      ReactDOM.render(React.createElement(GridImages, props, null), this)
    })
  }

  render () {
    let lang = this.props.lang
    return (
      <div>
        <div className="field-file-upload mt">
          <div className="field-file-upload__content">
            {lang.grid_images_drop_files}
            <em>{lang.grid_images_uploading_to}</em>
          </div>
        </div>
        <a href="#" className="btn action" rel="modal-file-chooser">{lang.grid_images_choose_existing}</a>&nbsp;
        <a href="#" className="btn action" rel="modal-file-uploader">{lang.grid_images_upload_new}</a>
      </div>
    )
  }
}

$(document).ready(function () {
  GridImages.renderFields()
})

FluidField.on('grid_images', 'add', function(field) {
  GridImages.renderFields(field)
})
