/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import UploadEdit from '../../elements/pages/files/UploadEdit';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import Channel from '../../elements/pages/channel/Channel';
import Publish from '../../elements/pages/publish/Publish';

const siteForm = new SiteForm;
const uploadEdit = new UploadEdit;
const fieldForm = new ChannelFieldForm;
const channelForm = new Channel;
const publish = new Publish;
const uploadPath = '../../images/uploads/issue-5438';
const media = [
  { type: 'JPEG', path: 'support/file-sync/images/carlshead2.jpeg', extension: 'jpeg', mime: 'image/jpeg' },
  { type: 'MP4', path: 'support/file/ee-sample-video.mp4', extension: 'mp4', mime: 'video/mp4' }
];

context('File fields in MSM shared upload directories (#5438)', () => {
  const fixture = { sites: {} }
  const field = (site, restriction) => cy.get(`[data-field_id="${fixture.sites[site].fields[restriction]}"]`)
  const switchSite = site => cy.visit(`admin.php?/cp/msm/switch_to/${site}`)
  const visitEntry = site => cy.visit(`admin.php?/cp/publish/create/${fixture.sites[site].channel}`)

  function upload(site, restriction, file, filename) {
    cy.intercept('POST', '**/filepicker/ajax-upload').as('sharedUpload')
    cy.readFile(file.path, null).then(contents => {
      field(site, restriction).find('.file-field__dropzone').selectFile({
        contents, fileName: filename, mimeType: file.mime
      }, { action: 'drag-drop' })
    })
    if (restriction === 'all') {
      field(site, restriction).find('.file-field__dropzone button').click()
      field(site, restriction).find('.dropdown:visible a').contains(/^Shared Files$/).click()
    }
    cy.wait('@sharedUpload').its('response.body.status').should('eq', 'success')
    field(site, restriction).find('.js-file-input').should('not.have.value', '')
    field(site, restriction).should('contain', filename)
  }

  function chooseExisting(site, restriction, filename) {
    field(site, restriction).contains('button, a', 'Choose Existing').click()
    if (restriction === 'all') {
      field(site, restriction).find('.dropdown:visible a').contains(/^Shared Files$/).click()
    }
    cy.get('.modal-file:visible').contains('.app-listing__row a', filename).click()
    cy.get('.modal-file').should('not.be.visible')
    field(site, restriction).find('.js-file-input').should('not.have.value', '')
    field(site, restriction).should('contain', filename)
  }

  before(() => {
    cy.task('db:seed')
    cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
    cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'n' })
    cy.eeConfig({ item: 'enable_dock', value: 'n' })
    cy.task('filesystem:delete', uploadPath)
    cy.task('filesystem:create', uploadPath)
    cy.exec(`chmod 777 ${uploadPath}`)
    cy.auth()
    cy.visit('admin.php?/cp/msm/create')
    cy.dismissLicenseAlert()
    siteForm.add_site({ name: 'Shared File Site', short_name: 'shared_file_site' })
    cy.hasNoErrors()

    switchSite(1)
    uploadEdit.load()
    uploadEdit.get('adapter').find('.select__button').click()
    uploadEdit.get('adapter').find('.select__dropdown-item').contains('Local').click()
    uploadEdit.get('name').clear().type('Shared Files')
    uploadEdit.get('url').clear().type(`${Cypress.config().baseUrl}/images/uploads/issue-5438/`)
    uploadEdit.get('server_path').clear().type('{base_path}/images/uploads/issue-5438/', { parseSpecialCharSequences: false })
    uploadEdit.get('allowed_types').find('input[type=checkbox]').uncheck({ force: true })
    uploadEdit.get('allowed_types').find('[value="--"]').check({ force: true })
    cy.get('#fieldset-share_directory .toggle-btn').click()
    uploadEdit.submit()
    uploadEdit.get('alert_success').should('contain', 'Upload directory saved')
    cy.task('db:query', "SELECT id, site_id FROM exp_upload_prefs WHERE name = 'Shared Files'").then(([rows]) => {
      expect(rows).to.have.length(1)
      expect(Number(rows[0].site_id)).to.eq(0)
      fixture.directory = rows[0].id
    })

    ;[1, 2].forEach(site => {
      fixture.sites[site] = { fields: {} }
      switchSite(site)
      ;['all', 'shared'].forEach(restriction => {
        cy.visit('admin.php?/cp/fields/create')
        cy.then(() => fieldForm.createField({
          type: 'File',
          label: `Shared file ${restriction} ${site}`,
          fields: { allowed_directories: restriction === 'all' ? 'all' : fixture.directory, field_content_type: 'all' }
        }))
      })
      cy.task('db:query', `SELECT field_id, field_name FROM exp_channel_fields WHERE field_name IN ('shared_file_all_${site}', 'shared_file_shared_${site}')`).then(([rows]) => {
        expect(rows).to.have.length(2)
        rows.forEach(row => {
          fixture.sites[site].fields[row.field_name.split('_')[2]] = row.field_id
        })
      })
      cy.visit('admin.php?/cp/channels/create')
      channelForm.get('channel_title').type(`Shared Files ${site}`)
      channelForm.get('fields_tab').click()
      cy.then(() => {
        Object.values(fixture.sites[site].fields).forEach(id => {
          channelForm.get('custom_fields').filter(`[value="${id}"]`).check({ force: true })
        })
      })
      channelForm.get('save_button').first().click()
      cy.hasNoErrors()
      cy.task('db:query', `SELECT channel_id, site_id FROM exp_channels WHERE channel_name = 'shared_files_${site}'`).then(([rows]) => {
        expect(rows).to.have.length(1)
        expect(Number(rows[0].site_id)).to.eq(site)
        fixture.sites[site].channel = rows[0].channel_id
      })
    })

    // Create existing selections through the normal uploader with compatibility mode off.
    switchSite(1)
    media.forEach(file => {
      cy.then(() => {
        visitEntry(1)
        upload(1, 'shared', file, `existing.${file.extension}`)
      })
    })
    cy.task('db:query', "SELECT file_name, site_id FROM exp_files WHERE file_name IN ('existing.jpeg', 'existing.mp4')").then(([rows]) => {
      expect(rows).to.have.length(2)
      rows.forEach(row => expect(Number(row.site_id)).to.eq(0))
    })
  })

  after(() => {
    cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'n' })
    cy.eeConfig({ item: 'enable_dock', value: 'y' })
  })

  ;['n', 'y'].forEach(mode => {
    context(`compatibility mode ${mode === 'y' ? 'enabled' : 'disabled'}`, () => {
      beforeEach(() => {
        cy.eeConfig({ item: 'file_manager_compatibility_mode', value: mode })
        cy.auth()
      })

      ;[1, 2].forEach(site => {
        media.forEach(file => {
          ;['upload', 'existing'].forEach(action => {
            it(`${action}: saves and reloads a shared ${file.type} on site ${site} with either directory setting`, () => {
              switchSite(site)
              visitEntry(site)
              cy.window().its('EE.fileManagerCompatibilityMode').should('eq', mode === 'y')
              const title = `Shared ${mode} ${site} ${file.type} ${action}`
              publish.get('title').type(title)
              ;['all', 'shared'].forEach(restriction => {
                const filename = action === 'existing' ? `existing.${file.extension}` : `${mode}-${site}-${restriction}.${file.extension}`
                if (action === 'upload') {
                  upload(site, restriction, file, filename)
                } else {
                  chooseExisting(site, restriction, filename)
                }
                field(site, restriction).find('.js-file-input').invoke('val').then(value => {
                  if (mode === 'y') {
                    expect(value).to.eq(`{filedir_${fixture.directory}}${filename}`)
                  } else {
                    expect(value).to.match(/^\{file:\d+:url\}$/)
                  }
                })
              })
              publish.get('save').click()
              cy.get('body').should('not.contain', 'Invalid Selection')
              cy.hasNoErrors()
              cy.task('db:query', `SELECT entry_id, site_id FROM exp_channel_titles WHERE title = '${title}'`).then(([rows]) => {
                expect(rows).to.have.length(1)
                expect(Number(rows[0].site_id)).to.eq(site)
                cy.visit(`admin.php?/cp/publish/edit/entry/${rows[0].entry_id}`)
              })
              ;['all', 'shared'].forEach(restriction => {
                const filename = action === 'existing' ? `existing.${file.extension}` : `${mode}-${site}-${restriction}.${file.extension}`
                field(site, restriction).should('contain', filename)
                field(site, restriction).find('.js-file-input').should('not.have.value', '')
              })
            })
          })
        })
      })
    })
  })
})
