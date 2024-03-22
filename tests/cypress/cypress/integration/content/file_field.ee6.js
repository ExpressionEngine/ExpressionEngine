/// <reference types="Cypress" />

import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import FileModal from '../../elements/pages/publish/FileModal';

let file_modal = new FileModal;

context('File field tags', () => {

    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    it('{url} variable inside File field tag pair', () => {
        cy.visit('/index.php/entries/picture')

        cy.hasNoErrors()

        cy.get('.file img.url')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.greaterThan(0)
        })

        cy.get('.file img.resized')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(50)
        })

        cy.get('.file img.cropped')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(75)
            expect($img[0].naturalHeight).to.be.eq(75)
        })

        cy.get('.file img.webp')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(75)
            expect($img[0].naturalHeight).to.be.eq(75)
        })
        cy.get('.file img.webp').invoke('attr', 'src').should('match', /\.webp$/)
    })

    it('{url} variable inside File Grid field tag pair', () => {
        cy.auth()
        const channel_field_form = new ChannelFieldForm
        channel_field_form.createField({
            group_id: 1,
            type: 'File Grid',
            label: 'File Grid Field',
            fields: { field_content_type: 'all' }
        })

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        // add another row
        let link = cy.get('.js-file-grid').find("button:contains('Choose Existing'):visible");
        link.click()
        link.next('.dropdown').find("a:contains('About')").click()
        file_modal.get('files').should('be.visible')
        file_modal.get('files').first().click()
        file_modal.get('files').should('not.be.visible')
        cy.wait(5000); //give JS some extra time
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('/index.php/entries/picture')

        cy.hasNoErrors()

        cy.get('.file_grid img.url')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.greaterThan(0)
        })

        cy.get('.file_grid img.resized')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(40)
        })

        cy.get('.file_grid img.cropped')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(65)
            expect($img[0].naturalHeight).to.be.eq(65)
        })

        cy.get('.file_grid img.webp')
            .should('be.visible')
            .and(($img) => {
            expect($img[0].naturalWidth).to.be.eq(65)
            expect($img[0].naturalHeight).to.be.eq(65)
        })
        cy.get('.file_grid img.webp').invoke('attr', 'src').should('match', /\.webp$/)

    })

})
