import ControlPanel from '../ControlPanel'

class FileModal extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'file_modal': '.modal-file',
      'title': '.modal-file .title-bar__title',
      'upload_button': '.modal-file .button.button--primary:contains("Upload")',
      'filters': '.modal-file .filter-bar .filter-bar__item',
      'view_filters': '.modal-file .filter-bar .filter-bar__item:contains("View as")',
      'files': '.modal-file table tbody td'
    })
  }
}
export default FileModal;