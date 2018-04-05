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
    this.field.closest('[data-relationship-react]')
      .parent()
      .find('[rel=add_new][data-channel-id]')
      .on('click', (e) => {
        e.preventDefault()
        this.openPublishFormForChannel($(e.currentTarget).data('channelId'))

        // Close sub menu
        if ($(e.currentTarget).closest('.sub-menu').length) {
          $(e.currentTarget).closest('.filters')
            .find('.open')
            .removeClass('open')
            .siblings('.sub-menu')
            .hide();
        }
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
