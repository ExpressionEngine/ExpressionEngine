/// <reference types="Cypress" />

import ChannelManager from '../../elements/pages/channel/Manager';
const page = new ChannelManager;
const { _, $ } = Cypress

context('Channel Manager', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('shows the Channel Manager page', function() {
        cy.contains('Channel Manager')
    })


    it('should list the channels', function() {
        page.getChannelTitles().then(function(results) {
            let channels = [...page.$('channels').map(function(index, el) {
                return $(el).text().trim();
            })];
            expect(channels).to.deep.equal(results)
            page.get('channels').its('length').should('eq', results.length)
        })
    })

    it('should delete a channel', function() {
        page.getChannelTitles().then(function(channels) {
            page.get('channels_checkboxes').eq(1).click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Remove')
            page.get('action_submit_button').click()

            page.get('modal').contains('Channel: ' + channels[1])
            page.get('modal_submit_button').click()
            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('channels').its('length').should('eq', channels.length - 1)
        })
    })

    it('should bulk delete channels', function() {
        page.getChannelTitles().then(function(channels) {

            page.get('select_all').click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Remove')
            page.get('action_submit_button').click()

            if (channels.length <= 5) {
                channels.forEach(function(channel) {
                    page.get('modal').contains('Channel: ' + channel)
                })
            }

            page.get('modal_submit_button').click()
            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains('Channels removed')
            page.get('alert').contains(channels.length + ' channels were removed.')

            cy.contains('No Channels found')
        })
    })
})