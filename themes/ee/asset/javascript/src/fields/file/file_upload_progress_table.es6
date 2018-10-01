/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function FileUploadProgressTable(props) {
  return (
    <div className="field-file-upload__table">
      <div className="tbl-wrap">
        <table className="tbl-fixed tables--uploads">
          <tbody>
            <tr>
              <th>File Name</th>
              <th>Progress</th>
            </tr>
            {props.files.map(file =>
              <tr key={file.name}>
                <td>{(file.error || file.duplicate) && <span className="icon--issue"></span>}{file.name}</td>
                <td>
                  {file.error}
                  {file.error &&
                    <span>&nbsp;<a href="#" onClick={(e) => props.onFileErrorDismiss(e, file)}>Dismiss</a></span>
                  }
                  {file.duplicate && <ResolveFilenameConflict
                    file={file}
                    onResolveConflict={props.onResolveConflict}
                    onFileUploadCancel={(e) => props.onFileErrorDismiss(e, file)}
                  />}
                  {file.progress && <div className="progress-bar">
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

    let url = 'http://eecms.localhost/admin.php?/cp/addons/settings/filepicker/ajax-overwrite-or-rename&file_id='+file.fileId+'&original_file_name='+file.originalFileName
    let modal = $('.modal-file')
    $('div.box', modal).html('<iframe></iframe>')
    let iframe = $('iframe', modal)
    iframe.css({
      border: 'none',
      width: '100%'
    })
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
        modal.trigger('modal:close')
        return this.props.onResolveConflict(file, response)
      } catch(e) {
        var height = iframe.contents().find('body').height()
        $('.box', modal).height(height)
        iframe.height(height)
      }

      $(iframe[0].contentWindow).on('unload', () => {
        iframe.hide();
        $('.box', modal).height('auto')
        $(modal).height('auto')
      })
    })
  }

  render() {
    return (
      <a href="#" className="m-link" rel="modal-file" onClick={(e) => this.resolveConflict(e, this.props.file)}>Resolve Conflict</a>
    )
  }
}
