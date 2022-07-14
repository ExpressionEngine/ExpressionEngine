/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
    window.list;
    window.globalDropzone;

    let directoryName = this.getDirectoryName(props.allowedDirectory);

    let item  = this.getDirectoryItem(props.allowedDirectory);

    this.state = {
      files: [],
      directory: directoryName ? props.allowedDirectory : 'all',
      directoryName: directoryName,
      pendingFiles: null,
      error: null,
      path: item.path,
      upload_location_id: item.upload_location_id, //main folder ID
      directory_id: item.directory_id, //subfolder ID
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

    var directory = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);

    return directory.label
  }

  getDirectoryItem(directory) {
    if (directory == 'all') {
      var directory = {
        upload_location_id: null,
        path: '',
        directory_id: 0
      }
    } else {
      var directory = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);
      if (directory.value == directory.upload_location_id) {
        directory.directory_id = 0
      } else {
        directory.directory_id = parseInt(directory.value.substr(directory.value.indexOf('.') + 1))
      }
    }
    return directory
  }

  checkChildDirectory = (items, directory) => {
    items.map(item => {
      var value;
      if (typeof item.value == 'number') {
        value = item.value
      } else {
        value = item.value.substr(item.value.indexOf('.') + 1)
        value = parseInt(value)
      }
      if (value == directory) {
        return window.list = item;
      }else if(value != directory && (Array.isArray(item.children) && item.children.length)) {
        this.checkChildDirectory(item.children, directory);
      }
    })

    return window.list;
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
      window.globalDropzone = $(this.dropZone)
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
    // files = files.filter(file => file.type != '')

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
      formData.append('directory_id', this.state.directory_id)
      formData.append('file', file)
      formData.append('csrf_token', EE.CSRF_TOKEN)
      formData.append('upload_location_id', this.state.upload_location_id)
      formData.append('path', this.state.path)

      let xhr = new XMLHttpRequest()
      xhr.open('POST', EE.dragAndDrop.endpoint, true)
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.addEventListener('progress', (e) => {
        if ( $('.file-upload-widget').hasClass('open-dd') ) {
          $('.file-upload-widget').css({
            'height': 'auto',
            'position': 'static'
          })
        }
        if( $('.file-upload-widget').length && $('.file-upload-widget').hasClass('hidden')) {
          $('.file-upload-widget').show();
        }
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
              resolve(file)
              if( $('.file-upload-widget').length) {
                $('.file-upload-widget').hide();
                $('body .f_manager-wrapper > form').submit();
              }

              this.props.onFileUploadSuccess(JSON.parse(xhr.responseText), window.globalDropzone)
              break
            case 'duplicate':
              file.duplicate = true
              file.fileId = response.fileId
              file.originalFileName = response.originalFileName
              if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                window.globalDropzone.parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
                  $(this).attr('disabled','disabled');
                })
              }
              if( $('.title-bar a.upload').length) {
                $('.title-bar a.upload').addClass('disabled')
              }
              if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled')
              }
              reject(file)
              break
            case 'error':
              file.error = this.stripTags(response.error)
              if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
                  $(this).attr('disabled','disabled');
                })
              }
              if( $('.title-bar a.upload').length) {
                $('.title-bar a.upload').addClass('disabled')
              }
              if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled')
              }
              reject(file)
              break
            default:
              if (typeof(response.message) !== 'undefined') {
                file.error = response.message;
              } else {
                file.error = EE.lang.file_dnd_unexpected_error;
              }
              if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
                  $(this).attr('disabled','disabled');
                })
              }
              if( $('.title-bar a.upload').length) {
                $('.title-bar a.upload').addClass('disabled')
              }
              if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled')
              }
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
            } else if (typeof(response.message) !== 'undefined') {
              file.error = response.message;
            }
          } catch(err) {}
              if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
                  $(this).attr('disabled','disabled');
                })
              }
              if( $('.title-bar a.upload').length) {
                $('.title-bar a.upload').addClass('disabled')
              }
              if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                $('.main-nav .main-nav__toolbar .js-dropdown-toggle').attr('disabled', 'disabled')
              }
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
    if (directory == 'all') return null;

    if (typeof directory == 'number') {
      directory = directory
    } else {
      directory = parseInt(directory.substr(directory.indexOf('.') + 1))
    }

    var item = this.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);
    var directory_id;
    if (directory == item.upload_location_id) {
      directory_id = 0;
    } else {
      directory_id = directory;
    }

    this.setState({
      directory: directory || 'all',
      directory_id: directory_id,
      path: item.path || '',
      upload_location_id: item.upload_location_id || null
    })
  }

  chooseExisting = (directory) => {
    let url = this.props.filebrowserEndpoint.replace('requested_directory=all', 'requested_directory=' + directory).replace('field_upload_locations=all', 'field_upload_locations=' + this.props.allowedDirectory);
    this.presentFilepicker(url, false)
    window.globalDropzone = $(this.dropZone)
  }

  uploadNew = (directory) => {
    var that = this;

    if (typeof directory == 'number') {
      directory = directory
    } else {
      directory = parseInt(directory.substr(directory.indexOf('.') + 1))
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
    })
    window.globalDropzone = $(this.dropZone);

    let el = $(this.dropZone).parents('div[data-file-field-react]');
    if (!el.length) {
      el = $(this.dropZone).parents('div[data-file-grid-react]')
    }

    el.find('.f_open-filepicker').click();
    el.find('.f_open-filepicker').change(function(e){
      var files = e.target.files;
      that.handleDroppedFiles(files)
    });
  }

  hiddenUpload = (el) => {
    var that = this;

    var upload_location_id = el.target.getAttribute('data-upload_location_id');
    var directory_id = el.target.getAttribute('data-directory_id');
    var directory;
    if (directory_id == 0 ) {
      directory = upload_location_id;
    } else {
      directory = directory_id;
    }

    var item = that.checkChildDirectory(EE.dragAndDrop.uploadDesinations, directory);

    that.setState({
      directory_id: directory_id,
      path: item.path || '',
      upload_location_id: upload_location_id
    })

    $(this.dropZone).parents('div[data-file-field-react]').find('.f_open-filepicker').click();
    $(this.dropZone).parents('div[data-file-field-react]').find('.f_open-filepicker').on('change', function(e){
      var files = e.target.files;
      that.handleDroppedFiles(files)
    });
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
    let el = $(this.dropZone).parents('div[data-file-field-react]');
    if (!el.length) {
      el = $(this.dropZone).parents('div[data-file-grid-react]')
    }
    el.find('.f_open-filepicker').val('');
  }

  warningsExist() {
    let erroredFile = this.state.files.find(file => {
      return file.error || file.duplicate
    })
    return erroredFile != null || this.state.pendingFiles
  }

  resolveConflict(file, response) {
    this.removeFile(file)
    this.props.onFileUploadSuccess(response, window.globalDropzone)
    if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
      $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
        $(this).removeAttr('disabled');
      })
    }
    if( $('.title-bar a.upload').length) {
      $('.title-bar a.upload').removeClass('disabled')
    }
    if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
      $('.main-nav .main-nav__toolbar .js-dropdown-toggle').removeAttr('disabled')
    }
    if( $('.file-upload-widget').length) {
      $('.file-upload-widget').hide();
      $('body .f_manager-wrapper > form').submit();
    }
  }

  showErrorWithInvalidState(error) {
    this.toggleErrorState(true)

    let errorElement = $(this.dropZone)
      .closest('.field-control')
      .find('> em')

    if (errorElement.length == 0) {
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

    return (
      <React.Fragment>
        <div className={"file-field" + (this.props.marginTop ? ' mt' : '') + (this.warningsExist() ? ' file-field--warning' : '') + (this.state.error ? ' file-field--invalid' : '')} >
          <div style={{display: this.state.files.length == 0 ? 'block' : 'none'}} className="file-field__dropzone" ref={(dropZone) => this.assignDropZoneRef(dropZone)}>
          {!this.props.showActionButtons &&
              <p class="file-field_upload-icon"><i class="fal fa-cloud-upload-alt"></i></p>
          }
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
                        createNewDirectory={this.props.createNewDirectory}
                        ignoreChild={false}
                        addInput={false}
                    />
                }
            </div>

            <div class="file-field__dropzone-icon"><i class="fal fa-cloud-upload-alt"></i></div>
            </>
          }
          </div>

          {this.state.files.length > 0 &&
            <FileUploadProgressTable
              files={this.state.files}
              onFileErrorDismiss={(e, file) => {
                e.preventDefault()
                this.removeFile(file)
                if($(window.globalDropzone).parents('.field-control').find('.button-segment').length) {
                  $(window.globalDropzone).parents('.field-control').find('.button-segment button.js-dropdown-toggle').each(function(){
                    $(this).removeAttr('disabled');
                  })
                }
                if( $('.title-bar a.upload').length) {
                  $('.title-bar a.upload').removeClass('disabled')
                }
                if( $('.main-nav .main-nav__toolbar .js-dropdown-toggle').length) {
                  $('.main-nav .main-nav__toolbar .js-dropdown-toggle').removeAttr('disabled')
                }
                if( $('.file-upload-widget').length) {
                  if ($('.file-upload-widget').hasClass('hidden')) {
                    $('.file-upload-widget').hide();
                  }

                  $('body .f_manager-wrapper > form').submit();
                }
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

            <a href="#" className="button button--default button--small m-link" onClick={(e) => {
              e.preventDefault()
              this.uploadNew(this.state.directory)
            }}>{EE.lang.file_dnd_upload_new}</a>
            <input type="file" className="f_open-filepicker" style={{display: 'none'}} data-upload_location_id={''} data-path={''} multiple="multiple"/>
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
              createNewDirectory={false}
              ignoreChild={true}
              addInput={false}
            />

            <DropDownButton key={EE.lang.file_dnd_upload_new}
              action={true}
              keepSelectedState={false}
              title={EE.lang.file_dnd_upload_new}
              placeholder={EE.lang.file_dnd_filter_directories}
              items={EE.dragAndDrop.uploadDesinations}
              onSelect={(directory) => this.uploadNew(directory)}
              buttonClass="button--default button--small"
              createNewDirectory={this.props.createNewDirectory}
              ignoreChild={false}
              addInput={true}
            />
          </div>
        )}
        {this.props.imitationButton && (
          <React.Fragment>
          <a href="#" style={{display: 'none'}} onClick={(el) => this.hiddenUpload(el)} data-upload_location_id={''} data-directory_id={''} data-path={''} className='imitation_button'>Imitation</a>
          <input type="file" className="f_open-filepicker" style={{display: 'none'}} multiple="multiple"/>
          </React.Fragment>
        )}
        </div>
      </React.Fragment>
    )
  }
}
