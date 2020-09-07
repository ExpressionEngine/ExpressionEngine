import Publish from './Publish'

class Edit extends Publish {
  constructor() {
    super()
    this.url = '/admin.php?/cp/publish/edit/entry/{entry_id}'
  }

}
export default Edit;
