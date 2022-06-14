/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

function FileUploadProgressTable(props) {
  return (
    <div className="file-field__items list-group">
            {props.files.map(file =>
              <div key={file.name} className="list-item">
                <div className="list-item__content-left">
                    {(file.error || file.duplicate) &&
                    <i class="fas fa-exclamation-triangle file-field__file-icon file-field__file-icon-warning"></i>
                    }
                    { !file.error && !file.duplicate &&
                    <i class="fas fa-file-archive file-field__file-icon"></i>
                    }
                </div>
                <div className="list-item__content">
                    <div>{file.name} { !file.error && !file.duplicate && <span class="float-right meta-info">{Math.round(file.progress)}% / 100%</span>}</div>
                    <div className="list-item__secondary">
                        {file.error && <span className="error-text">{file.error}</span> }
                        {file.duplicate && <span className="error-text">{EE.lang.file_dnd_conflict}</span> }

                        { !file.error && !file.duplicate &&
                        <div className="progress-bar">
                            <div className="progress" style={{width: file.progress+'%'}}></div>
                        </div>
                        }
                    </div>
                </div>
                <div className="list-item__content-right">

                  {file.error &&
                    <a className="button button--default" href="#" onClick={(e) => props.onFileErrorDismiss(e, file)}>{EE.lang.file_dnd_dismiss}</a>
                  }
                  {file.duplicate && <ResolveFilenameConflict
                    file={file}
                    onResolveConflict={props.onResolveConflict}
                    onFileUploadCancel={(e) => props.onFileErrorDismiss(e, file)}
                  />}
                </div>
              </div>
            )}
    </div>
  )
}

class ResolveFilenameConflict extends React.Component {
  resolveConflict = (e, file) => {
    e.preventDefault()

    let modal = $('.modal-file')
    $('div.box', modal).html('<iframe></iframe>')
    let iframe = $('iframe', modal)
    iframe.css({
      border: 'none',
      width: '100%'
    })

    let params = {
      file_id: file.fileId,
      original_name: file.originalFileName
    }
    let url = EE.dragAndDrop.resolveConflictEndpoint + '&' + $.param(params)

    iframe.attr('src', url)
    modal.find('div.box').html(iframe)

    iframe.on('load', () => {
      let response = iframe.contents().find('body').text()
      try {
        response = JSON.parse(response)
        modal.trigger('modal:close')
        if (response.cancel) {
          return this.props.onFileUploadCancel(e, file)
        }
        return this.props.onResolveConflict(file, response)
      } catch(e) {
        var height = iframe.contents().find('body').height()
        $('.box', modal).height(height)
        iframe.height(height)
      }

      $(iframe[0].contentWindow).on('unload', () => {
        iframe.hide()
        $('.box', modal).height('auto')
        $(modal).height('auto')
      })
    })
  }

  render() {
    return (
      <a href="#" className="button button--default m-link" rel="modal-file" onClick={(e) => this.resolveConflict(e, this.props.file)}>
        <i class="fas fa-info-circle icon-left"></i>
        {EE.lang.file_dnd_resolve_conflict}
      </a>
    )
  }
}
