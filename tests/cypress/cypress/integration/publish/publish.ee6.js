/// <reference types="Cypress" />

import Publish from '../../elements/pages/publish/Publish';
import ForumTab from '../../elements/pages/publish/ForumTab';
import FileModal from '../../elements/pages/publish/FileModal';
import FluidField from '../../elements/pages/publish/FluidField';
import EntryManager from '../../elements/pages/publish/EntryManager';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';

const page = new Publish;
const edit = new EntryManager;
const fluid_field = new FluidField;
let file_modal = new FileModal;

context('Publish Entry', () => {

    before(function(){
      cy.task('db:seed')
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.createEntries({})
      cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
        cy.visit('admin.php?/cp/design')
      })
      cy.eeConfig({ item: 'show_profiler', value: 'y' })
      cy.task('filesystem:delete', '../../images/uploads/*')
    })

    beforeEach(function(){
        cy.auth();
        cy.hasNoErrors()
    })

    after(function(){
      cy.eeConfig({ item: 'show_profiler', value: 'n' })
    })

    it('shows a 404 if there is no channel id', () => {
        cy.visit(Cypress._.replace(page.url, '{channel_id}', ''), {failOnStatusCode: false})
        cy.contains("404")
        cy.logCPPerformance()
    })

    it('shows comment fields when comments are enabled by system and channel allows comments', () => {
        cy.eeConfig({item: 'enable_comments', value: 'y'})
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('exist')
        cy.logCPPerformance()
    })

    it('does not show comment fields when comments are disabled by system', () => {
        cy.eeConfig({item: 'enable_comments', value: 'n'})

        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('not.exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('not.exist')
    })

    it('does not shows comment fields when comments are disabled by system and channel allows comments', () => {
        cy.eeConfig({item: 'enable_comments', value: 'n'})
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 2))
        page.get('tab_links').eq(1).click()
        page.get('wrap').find('input[type!=hidden][name="comment_expiration_date"]').should('not.exist')
        page.get('tab_links').eq(3).click()
        page.get('wrap').find('[data-toggle-for="allow_comments"]').should('not.exist')
    })

    it('selects default categories for new entries', () => {
        cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
        page.get('tab_links').eq(2).click()
        page.get('wrap').find('input[type="checkbox"][value=2]').should('be.checked')
    })

    context('Create entry with file fields', () => {

        beforeEach(function(){
            cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
            page.get('title').should('exist')
            page.get('url_title').should('exist')
            cy.wait(1000)
        })

        before(function() {
            cy.auth();
            const channel_field_form = new ChannelFieldForm
            channel_field_form.createField({
                group_id: 1,
                type: 'File',
                label: 'Second File',
                fields: { allowed_directories: 2 }
            })

            cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
            page.get('title').should('exist')
            page.get('url_title').should('exist')
        })

        after(function() {
            cy.task('filesystem:delete', '../../images/uploads/README.md')
        })

        it('the file field properly assigns image data when using the filepicker modal in a channel with two file fields', () => {
            var selectedFiles = {};
            var selectedFileIds = {};

            page.get('file_fields').each(function(field, i) {

                cy.intercept('/admin.php?/cp/addons/settings/filepicker/modal*').as('filepicker' + i)

                let link = field.find(".file-field__buttons .button-segment").find(":contains('Choose Existing')")
                cy.get(link).should('be.visible')
                cy.get(link).click()

                if (link.hasClass('has-sub')) {
                    let dir_link = link.next('.dropdown').find("a:contains('About')")
                    cy.get(dir_link).click()
                }

                cy.wait('@filepicker' + i)

                file_modal.get('files').should('be.visible')
                cy.get('.modal-file table tbody tr:nth-child(' + (i + 1) + ')').should('be.visible')
                cy.get('.modal-file table tbody tr:nth-child(' + (i + 1) + ')').invoke('attr', 'data-id').then((fileId) => {
                    selectedFileIds[i] = fileId;
                })
                cy.get('.modal-file table tbody tr:nth-child(' + (i + 1) + ') td:nth-child(3)').invoke('text').then((fileName) => {
                    selectedFiles[i] = fileName.replace('File Name', '');
                })
                cy.get('.modal-file table tbody tr:nth-child(' + (i + 1) + ') td:nth-child(3)').should('be.visible')
                cy.get('.modal-file table tbody tr:nth-child(' + (i + 1) + ')').click()

                file_modal.get('files').should('not.be.visible')
            })
            page.get('chosen_files').should('have.length', 2)
            page.get('file_fields').each(function(field, i) {
                var fileNameLabel = field.parents('.field-control').find('.fields-upload-chosen-name');
                cy.get(fileNameLabel).should('be.visible')
                cy.get(fileNameLabel).should('contain', selectedFiles[i])

                var fileInputHidded = field.parents('.field-control').find('input[class="js-file-input"]');
                cy.get(fileInputHidded).invoke('val').then((value) => {
                    expect(value).eq( '{file:' + selectedFileIds[i] + ':url}')
                })
            })

            //data in place after saving
            page.get('title').clear().type('File Field Test')
            page.get('chosen_files').should('have.length', 2)
            cy.get('button[value="save"]').click()

            page.get('chosen_files').should('have.length', 2)
            page.get('file_fields').each(function(field, i) {
                var fileNameLabel = field.parents('.field-control').find('.fields-upload-chosen-name');
                cy.get(fileNameLabel).should('be.visible')
                cy.get(fileNameLabel).should('contain', selectedFiles[i])

                var fileInputHidded = field.parents('.field-control').find('input[class="js-file-input"]');
                cy.get(fileInputHidded).invoke('val').then((value) => {
                    expect(value).eq( '{file:' + selectedFileIds[i] + ':url}')
                })
            })
        })

        it('if the file field is limited to directory, it is only available', () => {
            let link = page.get('file_fields').eq(0).find("a:contains('Choose Existing')");
            link.click()

            //page.get('modal').should('be.visible')
            file_modal.get('files').should('be.visible')
            //page.file_modal.wait_for_filters

            file_modal.get('filters').find('[data-filter-label="upload location"]').should('not.exist')
            file_modal.get('title').invoke('text').then((text) => {
                expect(text.trim()).not.equal('All Files')
            })
            cy.intercept("**/filepicker/modal**").as('ajax')
            file_modal.get('view_filters').find('a:not(.active)').first().click()
            cy.wait("@ajax");

            //directory selection should not be here
            file_modal.get('filters').find('[data-filter-label="upload location"]').should('not.exist')
            file_modal.get('title').invoke('text').then((text) => {
                expect(text.trim()).not.equal('All Files')
            })
            cy.logCPPerformance()
        })

        it('the file field restricts you to the chosen directory', () => {
            let link = page.get('file_fields').eq(1).find("button:contains('Choose Existing')");
            link.click()

            link.next('.dropdown').find("a:contains('About')").click()

            file_modal.get('files').should('be.visible')

            file_modal.get('filters').should('have.length', 9)
            file_modal.get('filters').find('[data-filter-label="upload location"]').should('contain', 'About')
            file_modal.get('title').invoke('text').then((text) => {
                expect(text.trim()).not.equal('All Files')
            })
            cy.intercept("**/filepicker/modal**").as('ajax')
            file_modal.get('view_filters').find('a:not(.active)').first().click() //switch view
            cy.wait("@ajax");

            //file_modal.wait_for_filters
            file_modal.get('filters').should('have.length', 10)
            file_modal.get('filters').find('[data-filter-label="upload location"]').should('contain', 'About')
            file_modal.get('title').invoke('text').then((text) => {
                expect(text.trim()).not.equal('All Files')
            })
            cy.logCPPerformance()
        })

        it('uploads the file into field with drag & drop', () => {
            cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
            page.get('file_fields').eq(0).find('.file-field__dropzone').attachFile('../../support/file/README.md', { subjectType: 'drag-n-drop' })
            cy.wait('@upload')
            cy.hasNoErrors()

            //the file should not be uploaded, error shown with dismiss button
            page.get('file_fields').eq(0).should('contain', 'File not allowed')
            page.get('file_fields').eq(0).find('a').contains('Dismiss').click()
            page.get('chosen_files').should('have.length', 0)

            //allow all files into this upload location
            cy.visit('admin.php?/cp/fields');
            cy.get('.list-item__content').contains('Second File').click();
            cy.get('input[name=allowed_directories][value=1]').check()
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            //try again
            cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
            cy.wait(1000)
            page.get('file_fields').eq(0).find('.file-field__dropzone').attachFile('../../support/file/README.md', { subjectType: 'drag-n-drop' })
            cy.wait('@upload')

            page.get('chosen_files').should('have.length', 1)
            page.get('file_fields').eq(0).parents('.field-control').find('.fields-upload-chosen-name').should('be.visible').should('contain', 'README.md')

            var fileInputHidded = '';
            page.get('file_fields').eq(0).parents('.field-control').find('input[class="js-file-input"]').invoke('val').then((value) => {
              fileInputHidded = value
            })

            //data in place after saving
            page.get('title').clear().type('File Field Test - Upload Image')
            cy.get('button[value="save"]').click()

            page.get('chosen_files').should('have.length', 1)
            page.get('file_fields').eq(0).parents('.field-control').find('.fields-upload-chosen-name').should('be.visible').should('contain', 'README.md')

            page.get('file_fields').eq(0).parents('.field-control').find('input[class="js-file-input"]').invoke('val').then((value) => {
              expect(value).eq(fileInputHidded)
            })
        })
    })

    context('Create entry with file grid', () => {

      beforeEach(function(){
          cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
          page.get('title').should('exist')
          page.get('url_title').should('exist')
          cy.wait(1000)
      })

      before(function() {
          cy.auth();
          const channel_field_form = new ChannelFieldForm
          channel_field_form.createField({
              group_id: 1,
              type: 'File Grid',
              label: 'File Grid Field',
              fields: { field_content_type: 'all' }
          })

          cy.visit(Cypress._.replace(page.url, '{channel_id}', 1))
          page.get('title').should('exist')
          page.get('url_title').should('exist')
      })

      it('uploads several files into same field', () => {
          cy.intercept('/admin.php?/cp/addons/settings/filepicker/ajax-upload').as('upload')
          cy.intercept('POST', '**/publish/create/**'). as('validation')
          cy.get('.js-file-grid').eq(0).find('.file-field__dropzone:visible').attachFile(['../../support/file/README.md', '../../../../LICENSE.txt', '../../support/file/script.sh'], { subjectType: 'drag-n-drop' })
          //nothing happens until you select upload destination
          cy.wait(3000);
          cy.get('.js-file-grid').eq(0).contains('You must choose a directory to upload files').should('be.visible')
          //select the destination
          cy.get('.js-file-grid').eq(0).find('.file-field__dropzone-button:visible .js-dropdown-toggle').click();
          cy.get('.js-file-grid').eq(0).find('.file-field__dropzone-button:visible .dropdown__link:contains("Main Upload Directory")').click()
          cy.wait('@upload')
          cy.hasNoErrors()

          //one of the files is not allowed, two should be successfully uploaded
          cy.get('.js-file-grid').eq(0).should('contain', 'File not allowed')
          cy.get('.js-file-grid').eq(0).find('a').contains('Dismiss').click()
          cy.get('.grid-field__table tbody tr:visible').should('have.length', 2)
          cy.get('.grid-field__table tbody tr:visible').contains('README.md')
          cy.get('.grid-field__table tbody tr:visible').contains('LICENSE.txt')
          cy.get('.grid-field__table tbody tr:visible').should('not.contain', 'script.sh')

          //data in place after validation error
          cy.get('button[value="save"]').click()

          cy.get('.grid-field__table tbody tr:visible').should('have.length', 2)
          cy.get('.grid-field__table tbody tr:visible').contains('README.md')
          cy.get('.grid-field__table tbody tr:visible').contains('LICENSE.txt')
          cy.get('.grid-field__table tbody tr:visible').should('not.contain', 'script.sh')

          //data in place after saving
          page.get('title').clear().type('File Grid Test').blur()
          page.get('url_title').focus().blur()
          cy.wait('@validation')
          cy.wait(3000)
          cy.get('button[value="save"]').click()
          
          cy.get('.grid-field__table tbody tr:visible').should('have.length', 2)
          cy.get('.grid-field__table tbody tr:visible').contains('README.md')
          cy.get('.grid-field__table tbody tr:visible').contains('LICENSE.txt')
          cy.get('.grid-field__table tbody tr:visible').should('not.contain', 'script.sh')
      })
  })

    context('Create entry with various Grids', () => {
      it('Grid with Buttons', () => {
        cy.authVisit('admin.php?/cp/fields/create/1')
        cy.get('[data-input-value=field_type] .select__button.js-dropdown-toggle').should('exist')
        cy.get('[data-input-value=field_type] .select__button').click()
        cy.get('[data-input-value=field_type] .select__dropdown-item:contains("Grid")').last().click()
        cy.get('input[type="text"][name = "field_label"]').type("Grid with Buttons")
        cy.get('[name="grid[cols][new_0][col_label]"]:visible').type("col 1")

        cy.get('.fields-grid-tool-add:visible').last().click()
        cy.get('[data-input-value="grid[cols][new_1][col_type]"] .select__button').click()
        cy.get('[data-input-value="grid[cols][new_1][col_type]"] .select__dropdown-item').contains("Selectable Buttons").last().click()
        cy.get('[name="grid[cols][new_1][col_label]"]:visible').type("buttons multiple")
        cy.get('[data-toggle-for="allow_multiple"]:visible').click()
        cy.get('[name="grid[cols][new_1][col_settings][field_pre_populate]"][value="n"]:visible').check()
        cy.get('[name="grid[cols][new_1][col_settings][field_list_items]"]:visible').type('uno{enter}dos{enter}tres')

        cy.get('.fields-grid-tool-add:visible').last().click()
        cy.get('[data-input-value="grid[cols][new_2][col_type]"] .select__button').click()
        cy.get('[data-input-value="grid[cols][new_2][col_type]"] .select__dropdown-item').contains("Selectable Buttons").last().click()
        cy.get('[name="grid[cols][new_2][col_label]"]:visible').type("buttons single")
        cy.get('[name="grid[cols][new_2][col_settings][field_pre_populate]"][value="n"]:visible').check()
        cy.get('[name="grid[cols][new_2][col_settings][field_list_items]"]:visible').type('quatro{enter}cinco{enter}seis')

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('p').contains('has been created')

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('.grid-field tbody [rel=add_row]').should('be.visible');
        cy.get('.grid-field [rel=add_row]:visible').click();
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(0).find('input').type('row 1');
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("dos")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("tres")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("dos")').should('have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(1).find('.button:contains("tres")').should('have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("quatro")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("cinco")').click()
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("quatro")').should('not.have.class', 'active')
        cy.get('.grid-field td:visible[data-new-row-id="new_row_1"]').eq(2).find('.button:contains("cinco")').should('have.class', 'active')

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('p').contains('has been updated')
        cy.get('.grid-field tbody tr:visible td:visible').eq(0).find('input').invoke('attr', 'value').then((val) => {
          expect(val).to.eq('row 1');
        })
        cy.get('.grid-field tbody tr:visible td:visible').eq(1).find('.button:contains("dos")').should('have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(1).find('.button:contains("tres")').should('have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(2).find('.button:contains("quatro")').should('not.have.class', 'active')
        cy.get('.grid-field tbody tr:visible td:visible').eq(2).find('.button:contains("cinco")').should('have.class', 'active')

        cy.visit('index.php/entries/grid')
        cy.logFrontendPerformance()
        cy.get('.grid_with_buttons .row-1 .col_1').invoke('text').then((text) => {
          expect(text).to.eq('row 1')
        })
        cy.get('.grid_with_buttons .row-1 .buttons_multiple').invoke('text').then((text) => {
          expect(text).to.eq('dos, tres')
        })
        cy.get('.grid_with_buttons .row-1 .buttons_single').invoke('text').then((text) => {
          expect(text).to.eq('cinco')
        })
        cy.logCPPerformance()
      })
    })
})
