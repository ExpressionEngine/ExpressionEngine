import ControlPanel from '../ControlPanel'

class FileModal extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'file_modal': '.modal-file',
      'title': '.modal-file h1',
      'upload_button': '.modal-file table .btn.submit',
      'filters': '.modal-file .filter-bar .filter-bar__item',
      'view_filters': '.modal-file .filter-bar .filter-bar__item:contains("View as")',
      'files': '.modal-file table tbody td'
    })
  }
}
export default FileModal;