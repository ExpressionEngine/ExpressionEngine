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

  chooseExisting = (directory) => {
    directory = directory || this.props.allowedDirectory
    console.log(directory)
  }

  uploadNew = (directory) => {
    directory = directory || this.props.allowedDirectory
    console.log(directory)
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
          {this.props.allowedDirectory == 'all' &&
            <div class="field-file-upload__controls">
              <FilterSelect key={lang.grid_images_choose_existing}
                center={true}
                keepSelectedState={false}
                title={lang.grid_images_choose_existing}
                placeholder='filter directories'
                items={this.props.uploadDestinations}
                onSelect={(directory) => this.chooseExisting(directory)}
              />
            </div>
          }
        </div>

        {this.props.allowedDirectory != 'all' &&
          <div>
            <a href="#" className="btn action" onClick={(e) => {
              e.preventDefault()
              this.chooseExisting()
            }}>{lang.grid_images_choose_existing}</a>&nbsp;
            <a href="#" className="btn action" onClick={(e) => {
              e.preventDefault()
              this.uploadNew()
            }}>{lang.grid_images_upload_new}</a>
          </div>
        }
        {this.props.allowedDirectory == 'all' && (
          <div class="filter-bar filter-bar--inline">
            <FilterSelect key={lang.grid_images_choose_existing}
              action={true}
              keepSelectedState={false}
              title={lang.grid_images_choose_existing}
              placeholder='filter directories'
              items={this.props.uploadDestinations}
              onSelect={(directory) => this.chooseExisting(directory)}
            />

            <FilterSelect key={lang.grid_images_upload_new}
              action={true}
              keepSelectedState={false}
              title={lang.grid_images_upload_new}
              placeholder='filter directories'
              items={this.props.uploadDestinations}
              onSelect={(directory) => this.uploadNew(directory)}
            />
          </div>
        )}
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
