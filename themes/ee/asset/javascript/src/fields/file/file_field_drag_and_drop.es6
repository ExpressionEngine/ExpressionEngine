/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
      .on('click', '.button.remove', () => {
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

    // If in a grid, return that
    if($(this.props.thisField).closest('.grid-file-upload').length) {
      return $(this.props.thisField).closest('.grid-file-upload')
    }

    let fluidContainer = $(this.props.thisField).closest('.fluid__item-field')

    // Is this file field inside of a fluid field? 
    // If it is, we need to get the fluid item container, 
    // not the container that holds the entire fluid field
    if (fluidContainer.length) {
      return fluidContainer
    }

    return $(this.props.thisField).closest('.grid-file-upload, .field-control')
  }

  setFile = (response) => {
    let fileField = this.getFieldContainer()

    EE.FileField.pickerCallback(response, {
      input_value: fileField.find('input.js-file-input'),
      input_img: fileField.find('img.js-file-image'),
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
