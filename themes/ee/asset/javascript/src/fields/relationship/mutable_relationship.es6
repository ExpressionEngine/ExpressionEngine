/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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

        const channelLink = $(e.currentTarget)
        this.openPublishFormForChannel(
          channelLink.data('channelId'),
          channelLink.data('channelTitle')
        )

        // Close sub menu
        if (channelLink.closest('.sub-menu').length) {
          channelLink.closest('.filters')
            .find('.open')
            .removeClass('open')
            .siblings('.sub-menu')
            .hide();
        }
      })
  }

  openPublishFormForChannel (channelId, channelTitle) {
    EE.cp.ModalForm.openForm({
      url: EE.relationship.publishCreateUrl.replace('###', channelId),
      full: true,
      iframe: true,
      success: this.options.success,
      load: (modal) => {
        const entryTitle = this.field.closest('[data-publish]').find('input[name=title]').val()

        let title = EE.relationship.lang.creatingNew
          .replace('#to_channel#', channelTitle)
          .replace('#from_channel#', EE.publish.channel_title)

        if (entryTitle) {
          title += '<b>: ' + entryTitle + '</b>'
        }

        EE.cp.ModalForm.setTitle(title)
      }
    })
  }
}
