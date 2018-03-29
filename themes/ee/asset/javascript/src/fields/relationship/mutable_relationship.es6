/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class MutableRelationshipField {
  constructor (field, options) {
    this.field = field
    this.options = options

    this.bindAdd()
  }

  bindAdd () {
    this.field.parent().find('[rel=add_new][data-channel-id]').on('click', (e) => {
      e.preventDefault()
      this.openPublishFormForChannel($(e.currentTarget).data('channelId'))
    })
  }

  openPublishFormForChannel (channelId) {
    EE.cp.ModalForm.openForm({
      url: EE.relationship.publishCreateUrl.replace('###', channelId),
      full: true,
      iframe: true,
      success: this.options.success
    })
  }
}
