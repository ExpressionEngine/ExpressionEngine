import ControlPanel from '../ControlPanel'

class FileModal extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'file_modal': '.modal-file',
      'title': '.modal-file h1',
      'upload_button': '.modal-file table .btn.submit',
      'filters': '.modal-file .filters > ul > li > a.has-sub',
      'view_filters': '.modal-file .filters > ul > li:last-child ul a',
      'files': '.modal-file table tbody td'
    })
  }
}
export default FileModal;