/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';
import UploadEdit from '../../elements/pages/files/UploadEdit';
import FileManager from '../../elements/pages/files/FileManager';
import UploadSync from '../../elements/pages/files/UploadSync';
import EditFile from '../../elements/pages/files/EditFile';
import UploadFile from '../../elements/pages/files/UploadFile';

const { _, $ } = Cypress

const svg_file = '../../themes/ee/asset/img/default-addon-icon.svg'
const upload_dir = '../../images/uploads'

const editFile = new EditFile;

const uploadPage = new UploadFile;
const managerPage = new FileManager;
const uploadEdit = new UploadEdit;
const siteManager = new SiteManager;


context('Different File Types', () => {
    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'enable_dock', value: 'n' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })

    })

    after(function() {
      cy.eeConfig({ item: 'enable_dock', value: 'y' })
      cy.task('filesystem:delete', upload_dir+'/\*')
    })

    it('Upload and display SVG file', () => {
        cy.intercept("**/filepicker/**").as('ajax')

        cy.visit('admin.php?/cp/files/directory/1')
        uploadPage.dragAndDropUpload(svg_file)
        cy.get('.file-upload-widget').should('not.be.visible')
        cy.wait('@table')
        cy.hasNoErrors()

        editFile.get('selected_file').should('exist')
        editFile.get('selected_file').contains("default-addon-icon.svg")
        editFile.get('selected_file').should('contain', 'Image')

      cy.authVisit('/admin.php?/cp/publish/edit')
      cy.get('a').contains('Getting to Know ExpressionEngine').click()
      cy.wait(2000) //wait for picker to initiliaze
      cy.get('.file-field-filepicker[title=Edit]').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.wait(1000)//give JS some extra time
      let ocean_id = null
      cy.get('.modal-file .app-listing__row a').contains('default-addon-icon.svg').parents('tr').invoke('attr', 'data-id').then((id) => {
        ocean_id = id;
      })
      cy.wait(1000)//give JS some extra tim
      cy.get('.modal-file .app-listing__row a').contains('default-addon-icon.svg').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.wait(1000)//give JS some extra time
      cy.get('.js-file-input').invoke('val').then((val) => {
        expect(val).to.eq('{file:' + ocean_id + ':url}')
      })
      cy.get('.fields-upload-chosen-name').should('contain', 'default-addon-icon.svg')

      cy.get('body').type('{ctrl}', {release: false}).type('s')
      cy.get('.fields-upload-chosen-name').should('contain', 'default-addon-icon.svg')

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/entries/file-svg')
      cy.hasNoErrors()
      cy.get('.svg_image svg').invoke('attr', 'width').then(
        (width) => {
          expect(width).to.eq('100')
        }
      )
      cy.get('.svg_image svg').invoke('attr', 'height').then(
        (height) => {
          expect(height).to.eq('100')
        }
      )
      cy.get('.svg_image svg').invoke('attr', 'viewBox').then( 
        (viewBox) => {
          expect(viewBox).to.eq('0 0 50 50')
        }
      )
      cy.get('.svg_image svg').invoke('attr', 'id').then(
        (id) => {
          expect(id).to.eq('svgId')
        }
      )
      cy.get('.svg_image svg').invoke('attr', 'class').then(
        (className) => {
          expect(className).to.eq('svgClass')
        }
      )

      cy.task('filesystem:delete', upload_dir+'/\*').then(() => {
        cy.visit('/index.php/entries/file-svg')
        cy.hasNoErrors()
      })
    })


})
