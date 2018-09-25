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

    this.state = {
      files: [],
      directory: props.allowedDirectory,
      directoryName: this.getDirectoryName(props.allowedDirectory)
    }
    this.queue = new ConcurrencyQueue({concurrency: 1})
  }

  static renderFields(context) {
    $('div[data-grid-images-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('gridImagesReact')))
      ReactDOM.render(React.createElement(GridImages, props, null), this)
    })
  }

  getDirectoryName(directory) {
    if (directory == 'all') return null;

    directory = this.props.uploadDestinations.find(
      thisDirectory => thisDirectory.value == directory
    )
    return directory.label
  }

  bindDragAndDropEvents() {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, preventDefaults, false)
    })

    function preventDefaults (e) {
      e.preventDefault()
      e.stopPropagation()
    }

    // Handle upload
    this.dropZone.addEventListener('drop', (e) => {
      if (this.state.directory == 'all') {
        // TODO: Show this using invalid state
        alert('Please choose a directory')
      }

      let files = Array.from(e.dataTransfer.files)

      files = files.filter(file => file.type != '')
      files = files.map(file => {
        file.progress = 0
        return file
      })

      this.setState({
        files: this.state.files.concat(files)
      })

      this.queue.enqueue(files, (file => this.makeUploadPromise(file)))
    })

    let highlight = (e) => {
      this.dropZone.classList.add('field-file-upload--drop')
    }

    let unhighlight = (e) => {
      this.dropZone.classList.remove('field-file-upload--drop')
    }

    ;['dragenter', 'dragover'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, highlight, false)
    })

    ;['dragleave', 'drop'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, unhighlight, false)
    })
  }

  makeUploadPromise(file) {
    return new Promise((resolve, reject) => {
      let formData = new FormData()
      formData.append('directory', this.state.directory)
      formData.append('file', file)
      formData.append('csrf_token', EE.CSRF_TOKEN)

      let xhr = new XMLHttpRequest()
      xhr.open('POST', this.props.endpoint, true)

      xhr.upload.addEventListener('progress', (e) => {
        let fileIndex = this.state.files.findIndex(
          thisFile => thisFile.name == file.name
        )
        this.state.files[fileIndex].progress = (e.loaded * 100.0 / e.total) || 100
        this.setState({
          files: this.state.files
        })
      })

      xhr.addEventListener('readystatechange', () => {
        if (xhr.readyState == 4 && xhr.status == 200) {
          let fileIndex = this.state.files.findIndex(thisFile => thisFile.name == file.name)
          this.state.files.splice(fileIndex, 1)
          this.setState({
            files: this.state.files
          })
          resolve(file)
        }
        else if (xhr.readyState == 4 && xhr.status != 200) {
          reject(file)
        }
      })

      formData.append('file', file)
      xhr.send(formData)
    })
  }

  setDirectory = (directory) => {
    this.setState({
      directory: directory || 'all'
    })
  }

  chooseExisting = (directory) => {
    directory = directory || this.state.directory
    console.log(directory)
  }

  uploadNew = (directory) => {
    directory = directory || this.state.directory
    console.log(directory)
  }

  componentDidMount () {
    this.bindDragAndDropEvents()
  }

  render() {
    let lang = this.props.lang
    return (
      <div>
        <div className="field-file-upload mt" ref={(dropZone) => { this.dropZone = dropZone; }}>
          {this.state.files.length > 0 && <GridImagesProgressTable files={this.state.files} />}
          {this.state.files.length == 0 && <div className="field-file-upload__content">
            {lang.grid_images_drop_files}
            <em>
              {this.state.directory == 'all' && lang.grid_images_choose_directory}
              {this.state.directory != 'all' && lang.grid_images_uploading_to.replace('%s', this.getDirectoryName(this.state.directory))}
            </em>
          </div>}
          {this.state.files.length == 0 && this.props.allowedDirectory == 'all' &&
            <div className="field-file-upload__controls">
              <FilterSelect key={lang.grid_images_choose_existing}
                center={true}
                keepSelectedState={true}
                title={lang.grid_images_choose_existing}
                placeholder='filter directories'
                items={this.props.uploadDestinations}
                onSelect={(directory) => this.setDirectory(directory)}
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
          <div className="filter-bar filter-bar--inline">
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
