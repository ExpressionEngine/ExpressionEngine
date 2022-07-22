import ControlPanel from '../ControlPanel'

class FileModal extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'file_modal': '.modal-file',
      'title': '.modal-file .title-bar__title',
      'upload_button': '.modal-file .button.button--primary:contains("Upload")',
      'filters': '.modal-file .filter-search-bar > div > .filter-search-bar__item',
      'view_filters': '.modal-file .filter-search-bar .filter-search-bar__item .filter__viewtype',
      'files': '.modal-file table tbody tr'
    })
  }
}
export default FileModal;
