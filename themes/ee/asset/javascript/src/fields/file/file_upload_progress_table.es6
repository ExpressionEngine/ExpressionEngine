/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function FileUploadProgressTable(props) {
  return (
    <div className="field-file-upload__table">
      <div className="tbl-wrap">
        <table className="tbl-fixed tables--uploads">
          <tbody>
            <tr>
              <th>{EE.lang.file_dnd_file_name}</th>
              <th>{EE.lang.file_dnd_progress}</th>
            </tr>
            {props.files.map(file =>
              <tr key={file.name}>
                <td>{(file.error || file.duplicate) && <span className="icon--issue"></span>}{file.name}</td>
                <td>
                  {file.error}
                  {file.error &&
                    <span>&nbsp;<a href="#" onClick={(e) => props.onFileErrorDismiss(e, file)}>{EE.lang.file_dnd_dismiss}</a></span>
                  }
                  {file.duplicate && <ResolveFilenameConflict
                    file={file}
                    onResolveConflict={props.onResolveConflict}
                    onFileUploadCancel={(e) => props.onFileErrorDismiss(e, file)}
                  />}
                  { ! file.error && ! file.duplicate && <div className="progress-bar">
                    <div className="progress" style={{width: file.progress+'%'}}></div>
                  </div>}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
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

    iframe.load(() => {
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
      <a href="#" className="m-link" rel="modal-file" onClick={(e) => this.resolveConflict(e, this.props.file)}>
        {EE.lang.file_dnd_resolve_conflict}
      </a>
    )
  }
}
