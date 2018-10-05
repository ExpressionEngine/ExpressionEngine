/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class FileField extends React.Component {
  constructor(props) {
    super(props)

    this.state = {
      file: props.file
    }
  }

  static renderFields(context) {
    $('div[data-file-field-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('fileFieldReact')))
      props.thisField = $(this)
      ReactDOM.render(React.createElement(FileField, props, null), this)
    })
  }

  componentDidMount () {
    this.getFieldContainer()
      .on('click', 'li.remove a', () => {
        this.setState({
          file: null
        })
      })
      .on('hasFile', 'input:hidden', data => {
        this.setState({
          file: data
        })
      })
  }

  getFieldContainer() {
    return $(this.props.thisField).closest('.grid-file-upload, .field-control')
  }

  setFile = (response) => {
    let fileField = this.getFieldContainer()

    EE.FileField.pickerCallback(response, {
      input_value: fileField.find('input:hidden').first(),
      input_img: fileField.find('img').first(),
      modal: $('.modal-file')
    })

    this.setState({
      file: response
    })
  }

  render() {
    if (this.state.file) {
      return null
    }

    return <DragAndDropUpload
      {...this.props}
      onFileUploadSuccess={this.setFile}
      marginTop={false}
      multiFile={false}
    />
  }
}
