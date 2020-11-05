/// <reference types="Cypress" />

import UploadSync from '../../elements/pages/files/UploadSync';
import UploadEdit from '../../elements/pages/files/UploadEdit';
const page = new UploadSync;
const new_upload = new UploadEdit;

// Paths we'll need on hand
const base_path = "support/file-sync"
const upload_path = base_path + '/uploads/'
const thumbs_path = upload_path + '_thumbs/'
const images_path = base_path + '/images/' // Files should sync without error
const non_images_path = base_path + '/non-images/' // Files should sync without error
const bad_files = base_path + '/bad/' // Should cause errors

let images_count = 0
let non_images_count = 0
let bad_files_count = 0
let thumb_count = 0

context('Upload Sync', () => {

  function init() {
    cy.auth();
    // Create a new upload directory for testing
    new_upload.load()
    new_upload.get('name').type('Dir')
    new_upload.get('url').type('http://ee/')
    cy.task('filesystem:path', upload_path).then((text) => {
      new_upload.get('server_path').type(text)
    })
    new_upload.submit()
    new_upload.get('wrap').contains('Upload directory saved')
  }

  before(function() {

    cy.task('db:seed');

    cy.task('filesystem:count', images_path).then((count) => {
      images_count = count;
    })
    non_images_count = cy.task('filesystem:count', non_images_path).then((count) => {
      non_images_count = count;
    })
    bad_files_count = cy.task('filesystem:count', bad_files).then((count) => {
      bad_files_count = count;
    })

    cy.task('filesystem:copy', { from: images_path+'*', to: upload_path })

    init()
  })

  beforeEach(function() {

    cy.auth();

    page.load_sync_for_dir(2)
    cy.hasNoErrors()
  })


  after(function() {
    cy.task('filesystem:delete', upload_path + '*')
  })


  it.skip('shows the Upload Directory Sync page', () => {
    page.get('progress_bar').should('exist')
    page.get('sizes').should('not.exist') // No image manipulations yet
  })

  it('should sync the directory', () => {

    page.get('wrap').contains(images_count.toString() + ' image files')

    cy.hasNoErrors()

    /*cy.document().then( document => {
      var base = document.createElement('base');
      base.href = Cypress.config('baseUrl');
      document.getElementsByTagName('head').eq(0).appendChild(base);
      console.log(base);
      cy.log(base.href);
    });*/

    page.get('sync_button').click()


    cy.wait(10000)
    cy.visit(dir_link)
    // Make sure progress bar progressed in the proper increments

    // This was a nice idea, but it's too intermittent, the AJAX is
    // sometimes too fast for RSpec. Uncomment if you want to test locally.
    //progress_bar_values = page.log_progress_bar_moves
    //progress_bar_values.should('eq', page.progress_bar_moves_for_file_count($images_count)



    page.get('alert').should('exist')

    page.get('alert').contains('Upload directory synchronized')

    cy.hasNoErrors()

    // Make sure thumbnails were created for each file
    // - 1 for index.html
    cy.task('filesystem:count', thumbs_path).then((thumb_count) => {
      expect(thumb_count).eq(images_count + 1)
    })

    // Get a list of files from the database AND the filesystem
    let db_files = [];
    let dir_files = [];
    cy.task('db:query', 'SELECT file_name FROM exp_files').then((result) => {
      result[0].forEach(function(row){
        db_files.push(row.file_name);
      });
      cy.task('filesystem:list', {target: images_path}).then((files) => {
        // Make sure files made it into the database
        files.forEach(function(filename){
          dir_files.push(filename.split("/").pop());
        });
        expect(db_files).to.include.members(dir_files)
      })
    })


    page.get('alert').contains('Upload directory synchronized')


  })



  it('should not sync non-images if directory does not allow it', () => {
    // Copy some non-image files over
    cy.task('filesystem:copy', { from: non_images_path+'*', to: upload_path })

    // Page should still only report the number of images and sync successfully
    page.get('wrap').contains(images_count.toString() + ' image files')

    page.get('sync_button').click()
    cy.wait(10000)
    cy.visit('/admin.php?/cp/files/directory/2')
    page.get('alert').should('exist')
    page.get('alert').contains('Upload directory synchronized')
  })

  it('should sync non-images if directory allows it', () => {
    cy.task('filesystem:copy', { from: non_images_path+'*', to: upload_path })


    let file_count = images_count + non_images_count

    new_upload.load_edit_for_dir(2)
    new_upload.get('allowed_types').check('all')
    new_upload.submit()
    new_upload.get('wrap').contains('Upload directory saved')
    cy.hasNoErrors()

    page.load_sync_for_dir(2)

    // Page should show file count for all files now
    page.get('wrap').contains(file_count.toString() + ' files')

    page.get('sync_button').click()
    cy.wait(10000)
    cy.visit(dir_link)
    page.get('alert').should('exist')
    page.get('alert').contains('Upload directory synchronized')

    cy.task('filesystem:delete', upload_path + '/**/*.mp3')
  })

  it('should not sync invalid mimes', () => {
    cy.task('filesystem:delete', upload_path + '*').then(() => {
      cy.task('filesystem:copy', { from: images_path+'*', to: upload_path }).then(() => {
        cy.task('filesystem:copy', { from: bad_files+'*', to: upload_path }).then(() => {

          let file_count = images_count + bad_files_count;

          new_upload.load_edit_for_dir(2)
          new_upload.get('allowed_types').check('all')
          new_upload.submit()
          new_upload.get('wrap').contains('Upload directory saved')
          cy.hasNoErrors()

          page.load_sync_for_dir(2)

          // Page should show file count for all files now
          page.get('wrap').contains(file_count.toString() + ' files')

          page.get('sync_button').click()
          cy.wait(10000)

          //page.get('alert').should('be.visible')
          page.get('alert').contains('Some files could not be synchronized')
          page.get('alert').contains('script copy 2.sh')
          page.get('alert').contains('script copy 3.sh')
          page.get('alert').contains('script copy 4.sh')
          page.get('alert').contains('script copy.sh')
          page.get('alert').contains('script.sh')
          page.get('alert').contains('The file type you are attempting to upload is not allowed.')
        })
      })
    })
  })


  context('Upload Sync with manipulations', () => {


    before(function(){
      cy.auth();
      new_upload.create_manipulation()

      cy.task('filesystem:count', images_path).then((count) => {
        images_count = count;
      })
    })

    
    it.skip('should apply image manipulations to new files', () => {
      // First we need to create the manipulations
      new_upload.create_manipulation()

      page.load_sync_for_dir(2)
      page.get('sizes').should('exist')
      page.get('wrap').contains('some_name Constrain, 20px by 30px')
      page.get('wrap').contains('some_other_name Crop, 50px by 40px')
      // Should be unchecked by default
      page.get('sizes').should('have.length', 2)
      page.get('sizes').eq(0).should('not.be.checked')
      page.get('sizes').eq(1).should('not.be.checked')

      page.get('sync_button').click()

      cy.hasNoErrors()

      // Make sure progress bar progressed in the proper increments

      // This was a nice idea, but it's too intermittent, the AJAX is
      // sometimes too fast for RSpec. Uncomment if you want to test locally.
      //progress_bar_values = page.log_progress_bar_moves
      //progress_bar_values.should('eq', page.progress_bar_moves_for_file_count($images_count)

      page.get('alert').should('exist')

      cy.hasNoErrors()

      let constrain_files = [];
      cy.task('filesystem:list', {target: upload_path + '_some_name/'}).then((files) => {
        constrain_files = files
        thumb_count = constrain_files.length - 1 // - 1 for index.html
        expect(thumb_count).eq(images_count)
      })
      let crop_files = []
      cy.task('filesystem:list', {target: upload_path + '_some_other_name/'}).then((files) => {
        crop_files = files
        thumb_count = crop_files.length - 1 // - 1 for index.html
        expect(thumb_count).eq(images_count)
      })

      // Make sure thumbnails and image manupulations were created for each file
      // - 1 for index.html
      cy.task('filesystem:count', thumbs_path).then((thumb_count) => {
        expect(thumb_count).eq(images_count + 1)
      })


      // Next, we'll make sure the manipulations were properly constrained/cropped
      const valid_extensions = ['.gif', '.jpg', '.JPG', '.jpeg', '.png']

      const imageSize = require('image-size');

      constrain_files.forEach(function(file) {
        if (valid_extensions.indexOf(file.split('.').pop()))
        {
            size = imageSize(file)
            size.width.should('<=', 20)
            size.height.should('<=', 30)
        }
      })

      crop_files.forEach(function(file) {
        if (valid_extensions.indexOf(file.split('.').pop()))
        {
            size = imageSize(file)
            size.width.should('eq', 50)
            size.height.should('eq', 40)
        }
      })
    })

    it('should not overwrite existing manipulations if not told to', () => {

      page.load_sync_for_dir(2)
      page.get('sync_button').click()
      cy.wait(20000)
      cy.visit('/admin.php?/cp/files/directory/2')
      page.get('alert').should('exist')
      cy.hasNoErrors()

      let file = ''
      cy.task('filesystem:list', {target: upload_path + '_some_name', mask: '/*.png'}).then((files) => {
        file = files[0]
        let stats = {}
        cy.task('filesystem:info', file).then((stat) => {
          stats = stat
          // Sleep for a second to make sure enough time has passed for the file
          // modified time to be different if it gets modified
          page.load_sync_for_dir(2)

          // Submit again with no manipulations checked
          page.get('sync_button').click()
          cy.wait(20000)
          cy.visit('/admin.php?/cp/files/directory/2')
          page.get('alert').should('exist')

          // Modification time should be the exact same
          let new_stats = {}
          cy.task('filesystem:info', file).then((stat) => {
            new_stats = stat
            expect(stats.mtime).eq(new_stats.mtime)
          })
        })

      })


    })

    it('should overwrite existing manipulations if told to', () => {
      //new_upload.create_manipulation()

      page.load_sync_for_dir(2)
      page.get('sync_button').click()
      cy.wait(20000)
      cy.visit('/admin.php?/cp/files/directory/2')
      page.get('alert').should('exist')
      cy.hasNoErrors()

      let file = ''
      cy.task('filesystem:list', {target: upload_path + '_some_name', mask: '/*.png'}).then((files) => {
        file = files[0]
        let stats = {}
        cy.task('filesystem:info', file).then((stat) => {
          stats = stat

          // Sleep for a second to make sure enough time has passed for the file
          // modified time to be different
          page.load_sync_for_dir(2)

          // Submit again with manipulations checked
          page.get('sizes').eq(0).click()
          page.get('sizes').eq(1).click()
          page.get('sync_button').click()
          cy.wait(10000)
          cy.visit('/admin.php?/cp/files/directory/2')
          page.get('alert').should('exist')

          // Modification time should be different as the file was overwritten
          let new_stats = {}
          cy.task('filesystem:info', file).then((stat) => {
            new_stats = stat
            expect(stats.mtime).not.eq(new_stats.mtime)
          })
        })
      })

    })


  })

})
