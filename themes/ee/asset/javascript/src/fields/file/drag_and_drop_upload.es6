/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

class DragAndDropUpload extends React.Component {
  static defaultProps = {
    concurrency: 5,
    showActionButtons: true,
    filebrowserEndpoint: EE.dragAndDrop.filepickerEndpoint,
    uploadEndpoint: EE.dragAndDrop.filepickerUploadEndpoint
  }

  constructor (props) {
    super(props)

    let directoryName = this.getDirectoryName(props.allowedDirectory)
    this.state = {
      files: [],
      directory: directoryName ? props.allowedDirectory : 'all',
      directoryName: directoryName,
      pendingFiles: null,
      error: null
    }
    this.queue = new ConcurrencyQueue({concurrency: this.props.concurrency})
  }

  componentDidMount () {
    this.bindDragAndDropEvents()
  }

  componentDidUpdate(prevProps, prevState) {
    this.toggleErrorState(false)

    if (this.state.directory != prevState.directory && this.state.pendingFiles) {
      this.handleDroppedFiles(this.state.pendingFiles)
    }

    if (this.state.error && ! prevState.error) {
      this.showErrorWithInvalidState(this.state.error)
    }

    if ( ! this.state.error && prevState.error) {
      this.toggleErrorState(false)
    }

    if (prevState.error) {
      this.setState({
        error: null
      })
    }
  }

  getDirectoryName(directory) {
    if (directory == 'all') return null;

    directory = EE.dragAndDrop.uploadDesinations.find(
      thisDirectory => thisDirectory.value == directory
    )

    if (typeof(directory) === 'undefined') {
      return ' ';
    }
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
      let droppedFiles = e.dataTransfer.files

      if (this.state.directory == 'all') {
        return this.setState({
          pendingFiles: droppedFiles
        })
      }

      this.handleDroppedFiles(droppedFiles)
    })

    let highlight = (e) => {
      this.dropZone.classList.add('file-field__dropzone--dragging')
    }

    let unhighlight = (e) => {
      this.dropZone.classList.remove('file-field__dropzone--dragging')
    }

    ;['dragenter', 'dragover'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, highlight, false)
    })

    ;['dragleave', 'drop'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, unhighlight, false)
    })
  }

  handleDroppedFiles = (droppedFiles) => {
    this.setState({
      pendingFiles: null
    })

    let files = Array.from(droppedFiles)
    files = files.filter(file => file.type != '')

    if ( ! this.props.multiFile && files.length > 1) {
      return this.setState({
        error: EE.lang.file_dnd_single_file_allowed
      })
    }

    if (this.props.shouldAcceptFiles && typeof this.props.shouldAcceptFiles(files) == 'string') {
      let shouldAccept = this.props.shouldAcceptFiles(files)
      if (typeof shouldAccept == 'string') {
        return this.setState({
          error: shouldAccept
        })
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
  }

  makeUploadPromise(file) {
    return new Promise((resolve, reject) => {
      let formData = new FormData()
      formData.append('directory', this.state.directory)
      formData.append('file', file)
      formData.append('csrf_token', EE.CSRF_TOKEN)

      let xhr = new XMLHttpRequest()
      xhr.open('POST', EE.dragAndDrop.endpoint, true)
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

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
          try {
            var response = JSON.parse(xhr.responseText);
            if (typeof(response.error) != 'undefined') {
              file.error = response.error;
            }
          } catch(err) {}
          console.error(xhr)
          reject(file)
        }

        this.setState({
          files: this.state.files
        })
      })

      formData.append('file', file)
      xhr.send(formData)

      if ($("[data-publish] > form").length) {
        $("[data-publish] > form").trigger("entry:startAutosave");
      }
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
    let url = this.props.filebrowserEndpoint.replace('=all', '='+directory)
    this.presentFilepicker(url, false)
  }

  uploadNew = (directory) => {
    let url = this.props.uploadEndpoint+'&directory='+directory
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

  warningsExist() {
    let erroredFile = this.state.files.find(file => {
      return file.error || file.duplicate
    })
    return erroredFile != null || this.state.pendingFiles
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
    let heading = this.props.multiFile
      ? EE.lang.file_dnd_drop_files
      : EE.lang.file_dnd_drop_file

    let subheading = this.state.directory == 'all'
      ? EE.lang.file_dnd_choose_directory
      : EE.lang.file_dnd_upload_to + ' '

    if (this.state.pendingFiles) {
      heading = EE.lang.file_dnd_choose_file_directory
      subheading = EE.lang.file_dnd_choose_directory_before_uploading
    }

    var selectedDirectoryNotInList = false;
    if (this.state.directory != 'all') {
      var dir = this.state.directory;
      if (EE.dragAndDrop.uploadDesinations.length != 0) {
        selectedDirectoryNotInList = true;
        Object.values(EE.dragAndDrop.uploadDesinations).forEach(function (uploadDesination) {
          if (uploadDesination.value == dir) {
            selectedDirectoryNotInList = false;
          }
        });
      }
    }
    if (EE.dragAndDrop.uploadDesinations.length == 0 || selectedDirectoryNotInList) {
      heading = EE.lang.file_dnd_no_directories;
      subheading = EE.lang.file_dnd_no_directories_desc;
      this.props.showActionButtons = false;
    }

    return (
      <React.Fragment>
        <div className={"file-field" + (this.props.marginTop ? ' mt' : '') + (this.warningsExist() ? ' file-field--warning' : '') + (this.state.error ? ' file-field--invalid' : '')} >
          <div style={{display: this.state.files.length == 0 ? 'block' : 'none'}} className="file-field__dropzone" ref={(dropZone) => this.assignDropZoneRef(dropZone)}>
          {this.state.files.length == 0 && <>
            <div className="file-field__dropzone-title">{heading}</div>
            <div class="file-field__dropzone-button">
                {subheading}
                {this.state.directory == 'all' && ':'}
                {this.state.directory != 'all' && <b>{this.getDirectoryName(this.state.directory)}</b>}
                &nbsp;
                {this.state.files.length == 0 && this.props.allowedDirectory == 'all' &&
                    <DropDownButton key={EE.lang.file_dnd_choose_existing}
                        action={this.state.directory == 'all'}
                        center={true}
                        keepSelectedState={true}
                        title={EE.lang.file_dnd_choose_directory_btn}
                        placeholder={EE.lang.file_dnd_filter_directories}
                        items={EE.dragAndDrop.uploadDesinations}
                        onSelect={(directory) => this.setDirectory(directory)}
                        buttonClass="button--default button--small"
                    />
                }
            </div>

            <div class="file-field__dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            </>
          }
          </div>

          {this.state.files.length > 0 &&
            <FileUploadProgressTable
              files={this.state.files}
              onFileErrorDismiss={(e, file) => {
                e.preventDefault()
                this.removeFile(file)
              }}
              onResolveConflict={(file, response) => this.resolveConflict(file, response)}
            />}
        </div>

        <div className="file-field__buttons">
        {this.props.showActionButtons && this.props.allowedDirectory != 'all' &&
          <React.Fragment>
            <div className="button-segment">
            <a href="#" className="button button--default button--small m-link" rel="modal-file" onClick={(e) => {
              e.preventDefault()
              this.chooseExisting(this.state.directory)
            }}>{EE.lang.file_dnd_choose_existing}</a>

            <a href="#" className="button button--default button--small m-link" rel="modal-file" onClick={(e) => {
              e.preventDefault()
              this.uploadNew(this.state.directory)
            }}>{EE.lang.file_dnd_upload_new}</a>
            </div>
          </React.Fragment>
        }
        {this.props.showActionButtons && this.props.allowedDirectory == 'all' && (
          <div className="button-segment">
            <DropDownButton key={EE.lang.file_dnd_choose_existing}
              action={true}
              keepSelectedState={false}
              title={EE.lang.file_dnd_choose_existing}
              placeholder={EE.lang.file_dnd_filter_directories}
              items={EE.dragAndDrop.uploadDesinations}
              onSelect={(directory) => this.chooseExisting(directory)}
              rel="modal-file"
              itemClass="m-link"
              buttonClass="button--default button--small"
            />

            <DropDownButton key={EE.lang.file_dnd_upload_new}
              action={true}
              keepSelectedState={false}
              title={EE.lang.file_dnd_upload_new}
              placeholder={EE.lang.file_dnd_filter_directories}
              items={EE.dragAndDrop.uploadDesinations}
              onSelect={(directory) => this.uploadNew(directory)}
              rel="modal-file"
              itemClass="m-link"
              buttonClass="button--default button--small"
            />
          </div>
        )}
        </div>
      </React.Fragment>
    )
  }
}
