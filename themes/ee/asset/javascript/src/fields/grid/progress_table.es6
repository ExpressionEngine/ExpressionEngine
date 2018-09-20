/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function GridImagesProgressTable(props) {
  return (
    <div className="field-file-upload__table">
      <div className="tbl-wrap">
        <table>
          <tbody>
            <tr>
              <th>File Name</th>
              <th>Progress</th>
            </tr>
            {props.files.map(file =>
              <tr key={file.name}>
                <td>{file.name}</td>
                <td>
                  <div className="progress-bar">
                    <div className="progress" style={{width: file.progress+'%'}}></div>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
