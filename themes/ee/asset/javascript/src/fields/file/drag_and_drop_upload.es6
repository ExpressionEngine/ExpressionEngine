/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class DragAndDropUpload extends React.Component {
  static defaultProps = {
    concurrency: 5
  }

  constructor (props) {
    super(props)

    let directoryName = this.getDirectoryName(props.allowedDirectory)
    this.state = {
      files: [],
      directory: directoryName ? props.allowedDirectory : 'all',
      directoryName: directoryName
    }
    this.queue = new ConcurrencyQueue({concurrency: this.props.concurrency})
  }

  static renderFields(context) {
    $('div[data-grid-images-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('gridImagesReact')))
      ReactDOM.render(React.createElement(GridImages, props, null), this)
    })
  }

  componentDidMount () {
    this.bindDragAndDropEvents()
  }

  componentDidUpdate() {
    this.toggleErrorState(false)
  }

  getDirectoryName(directory) {
    if (directory == 'all') return null;

    directory = EE.dragAndDrop.uploadDesinations.find(
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
        return this.showErrorWithInvalidState(EE.lang.file_dnd_choose_directory)
      }

      let files = Array.from(e.dataTransfer.files)
      files = files.filter(file => file.type != '')

      if (this.props.shouldAcceptFiles && typeof this.props.shouldAcceptFiles(files) == 'string') {
        let shouldAccept = this.props.shouldAcceptFiles(files)
        if (typeof shouldAccept == 'string') {
          return this.showErrorWithInvalidState(shouldAccept)
        }
      }

      files = files.map(file => {
        file.progress = 0
        if (this.props.contentType == 'image' && ! file.type.match(/^image\//)) {
          file.error = EE.lang.file_dnd_images_only
        }
        return file
      })

      this.setState({
        files: this.state.files.concat(files)
      })

      files = files.filter(file => ! file.error)
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
      xhr.open('POST', EE.dragAndDrop.endpoint, true)

      xhr.upload.addEventListener('progress', (e) => {
        file.progress = (e.loaded * 100.0 / e.total) || 100
        this.setState({
          files: this.state.files
        })
      })

      xhr.addEventListener('readystatechange', () => {
        if (xhr.readyState == 4 && xhr.status == 200) {
          let response = JSON.parse(xhr.responseText)

          switch (response.status) {
            case 'success':
              this.removeFile(file)
              this.props.onFileUploadSuccess(JSON.parse(xhr.responseText))
              resolve(file)
              break
            case 'duplicate':
              file.duplicate = true
              file.fileId = response.fileId
              file.originalFileName = response.originalFileName
              reject(file)
              break
            case 'error':
              file.error = this.stripTags(response.error)
              reject(file)
              break
            default:
              file.error = EE.lang.file_dnd_unexpected_error
              console.error(xhr)
              reject(file)
              break
          }
        }
        // Unexpected error, probably post_max_size is too low
        else if (xhr.readyState == 4 && xhr.status != 200) {
          file.error = EE.lang.file_dnd_unexpected_error
          console.error(xhr)
          reject(file)
        }

        this.setState({
          files: this.state.files
        })
      })

      formData.append('file', file)
      xhr.send(formData)
    })
  }

  stripTags(string) {
    let div = document.createElement('div')
    div.innerHTML = string
    return div.textContent || div.innerText || ""
  }

  setDirectory = (directory) => {
    this.setState({
      directory: directory || 'all'
    })
  }

  chooseExisting = (directory) => {
    let url = EE.dragAndDrop.filepickerEndpoint.replace('=all', '='+directory)
    this.presentFilepicker(url, false)
  }

  uploadNew = (directory) => {
    let url = EE.dragAndDrop.filepickerUploadEndpoint+'&directory='+directory
    this.presentFilepicker(url, true)
  }

  presentFilepicker(url, iframe) {
    let link = $('<a/>', {
      href: url,
      rel: 'modal-file'
    }).FilePicker({
      iframe: iframe,
      callback: (data, references) => this.props.onFileUploadSuccess(data)
    })
    link.click()
  }

  assignDropZoneRef = (dropZone) => {
    this.dropZone = dropZone
    if (this.props.assignDropZoneRef) {
      this.props.assignDropZoneRef(dropZone)
    }
  }

  removeFile = (file) => {
    let fileIndex = this.state.files.findIndex(thisFile => thisFile.name == file.name)
    this.state.files.splice(fileIndex, 1)
    this.setState({
      files: this.state.files
    })
  }

  errorsExist() {
    let erroredFile = this.state.files.find(file => {
      return file.error || file.duplicate
    })
    return erroredFile != null
  }

  resolveConflict(file, response) {
    this.removeFile(file)
    this.props.onFileUploadSuccess(response)
  }

  showErrorWithInvalidState(error) {
    this.toggleErrorState(true)

    let errorElement = $(this.dropZone)
      .closest('.field-control')
      .find('> em')

    if (errorElement.size() == 0) {
      errorElement = $('<em/>')
    }

    $(this.dropZone).closest('.field-control').append(errorElement.text(error))
  }

  toggleErrorState(toggle) {
    $(this.dropZone)
      .toggleClass('field-file-upload---invalid', toggle)
      .closest('fieldset, .fieldset-faux')
      .toggleClass('fieldset-invalid', toggle)

    if ( ! toggle) {
      $(this.dropZone)
        .closest('.field-control')
        .find('> em')
        .remove()
    }
  }

  render() {
    return (
      <React.Fragment>
        <div className={"field-file-upload" + (this.props.marginTop ? ' mt' : '') + (this.errorsExist() ? ' field-file-upload---warning' : '')}
          ref={(dropZone) => this.assignDropZoneRef(dropZone)}>
          {this.state.files.length > 0 &&
            <FileUploadProgressTable
              files={this.state.files}
              onFileErrorDismiss={(e, file) => {
                e.preventDefault()
                this.removeFile(file)
              }}
              onResolveConflict={(file, response) => this.resolveConflict(file, response)}
            />}
          {this.state.files.length == 0 && <div className="field-file-upload__content">
            {EE.lang.file_dnd_drop_files}
            <em>
              {this.state.directory == 'all' && EE.lang.file_dnd_choose_directory}
              {this.state.directory != 'all' && EE.lang.file_dnd_uploading_to.replace('%s', this.getDirectoryName(this.state.directory))}
            </em>
          </div>}
          {this.state.files.length == 0 && this.props.allowedDirectory == 'all' &&
            <div className="field-file-upload__controls">
              <FilterSelect key={EE.lang.file_dnd_choose_existing}
                center={true}
                keepSelectedState={true}
                title={EE.lang.file_dnd_choose_directory_btn}
                placeholder={EE.lang.file_dnd_filter_directories}
                items={EE.dragAndDrop.uploadDesinations}
                onSelect={(directory) => this.setDirectory(directory)}
              />
            </div>
          }
        </div>

        {this.props.allowedDirectory != 'all' &&
          <div>
            <a href="#" className="btn action m-link" rel="modal-file" onClick={(e) => {
              e.preventDefault()
              this.chooseExisting(this.state.directory)
            }}>{EE.lang.file_dnd_choose_existing}</a>&nbsp;
            <a href="#" className="btn action m-link" rel="modal-file" onClick={(e) => {
              e.preventDefault()
              this.uploadNew(this.state.directory)
            }}>{EE.lang.file_dnd_upload_new}</a>
          </div>
        }
        {this.props.allowedDirectory == 'all' && (
          <div className="filter-bar filter-bar--inline">
            <FilterSelect key={EE.lang.file_dnd_choose_existing}
              action={true}
              keepSelectedState={false}
              title={EE.lang.file_dnd_choose_existing}
              placeholder={EE.lang.file_dnd_filter_directories}
              items={EE.dragAndDrop.uploadDesinations}
              onSelect={(directory) => this.chooseExisting(directory)}
              rel="modal-file"
              itemClass="m-link"
            />

            <FilterSelect key={EE.lang.file_dnd_upload_new}
              action={true}
              keepSelectedState={false}
              title={EE.lang.file_dnd_upload_new}
              placeholder={EE.lang.file_dnd_filter_directories}
              items={EE.dragAndDrop.uploadDesinations}
              onSelect={(directory) => this.uploadNew(directory)}
              rel="modal-file"
              itemClass="m-link"
            />
          </div>
        )}
      </React.Fragment>
    )
  }
}

$(document).ready(function () {
  DragAndDropUpload.renderFields()
})

FluidField.on('file', 'add', function(field) {
  DragAndDropUpload.renderFields(field)
})
