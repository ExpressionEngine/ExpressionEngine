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
                    <span>&nbsp;<a href="#" onClick={(e) => props.onFileErrorDismiss(e, file)}>Dismiss</a></span>}
                  {file.duplicate &&
                    <a href="#" onClick={(e) => props.onResolveConflict(e, file)}>Resolve Conflict</a>}
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
