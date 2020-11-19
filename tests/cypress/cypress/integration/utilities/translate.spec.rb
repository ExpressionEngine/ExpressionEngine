/// <reference types="Cypress" />

import Translate from '../../elements/pages/utilities/Translate';
import TranslateEdit from '../../elements/pages/utilities/TranslateEdit';

const list_page = new Translate
const edit_page = new TranslateEdit

const english_path = '../../system/ee/legacy/language/english/'
const language_path = '../../system/user/language/'
const translations_path = '../../system/ee/legacy/translations/'

context('Translate Tool', () => {

  before(function() {
    cy.task('filesystem:create', language_path + 'rspeclingo')
    cy.task('filesystem:copy', { from: english_path + '*', to: language_path + 'rspeclingo/' })
  })

  beforeEach(function() {
    cy.auth();
  })

  function beforeEach_edit_false() {
    cy.auth();

    list_page.load()

    //list_page.should be_displayed
    //edit_page.should_not be_displayed

    list_page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('English Language Files') })
    list_page.get('phrase_search').should('exist')
  }

  function beforeEach_edit_true() {
    cy.auth();
    edit_page.load()

    //edit_page.should be_displayed

    edit_page.get('heading').contains('addons_lang.php Translation')
    edit_page.get('breadcrumb').should('exist')
    edit_page.get('items').should('exist')
    edit_page.get('submit_button').should('exist')
    edit_page.breadcrumb.contains('English Language Files')
  }

  after(function() {
    cy.task('filesystem:delete', language_path + 'rspeclingo/')
    cy.task('filesystem:delete', language_path + 'rspeclingo')
    cy.task('filesystem:delete', translations_path + '*.php')
  })

  it('displays 2 languages in the sidebar', () => {
    beforeEach_edit_false()
    list_page.get('languages').should('have.length', 2).invoke('text').then((text) => {
      expect(text).to.include('English (Default)')
      expect(text).to.include('Rspeclingo')
    })
  })

  /*it('displays the default language first in the sidebar', :edit => false do
    eeConfig({item: 'deft_lang', value: 'rspeclingo')
    list_page.load()
    list_page.languages.map {|lang| lang.text}.should == ["Rspeclingo (Default)", 'English']
    eeConfig({item: 'deft_lang', value: 'english')
  }

  it('shows the English Language files', :edit => false do
    list_page.should have_pagination
    list_page.should have(6).pages
    list_page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

    list_page.should have(26).rows // 25 rows per page + header row
  }

  it('can search by phrases', :edit => false do
    my_phrase = 'admin'
    list_page.get('wrap').contains(my_phrase
    list_page.get('phrase_search').clear().type(my_phrase).type('{enter}')
    cy.hasNoErrors()

    list_page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 2 results for "' + my_phrase + '"'
    list_page.phrase_search.invoke('val').then((val) => { expect(val).to.be.equal(my_phrase
    list_page.get('wrap').contains(my_phrase
    list_page.should have(3).rows // 2 rows + header row
    list_page.get('pagination').should('not.exist')
  }

  it('reports "no results" when a search fails', :edit => false do
    my_phrase = 'foobarbaz'
    list_page.should_not have_text my_phrase
    list_page.get('phrase_search').clear().type(my_phrase).type('{enter}')
    cy.hasNoErrors()

    list_page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 0 results for "' + my_phrase + '"'
    list_page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(my_phrase
    list_page.get('pagination').should('not.exist')
    list_page.get('no_results').should('exist')
  }

  it('paginates', :edit => false do
    click_link "Next"
    cy.hasNoErrors()

    list_page.should have_pagination
    list_page.should have(7).pages
    list_page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]

    files = Dir.glob(english_path + '*_lang.php')
    files = files[25..49]
    list_page.should have(files.count + 1).rows // +1 for header row
  }

  it('sorts by file name', :edit => false do
    list_page.find('a.sort').click()
    cy.hasNoErrors()

    list_page.should have_css('a.desc')
    list_page.should_not have_css('a.asc')
  }

  it('keeps sort while paginating', :edit => false do
    list_page.find('a.sort').click()
    cy.hasNoErrors()

    click_link "Next"
    cy.hasNoErrors()

    list_page.should have_css('a.desc')
    list_page.should_not have_css('a.asc')
  }

  // The capybara/webkit driver is munging headers.
  // it('can export language files', :edit => false do
  //   list_page.find('input[type="checkbox"][title="select all"]').set(true)
  //   list_page.get('bulk_action').select "Export (Download)"
  //   list_page.get('action_submit_button').click()
  //   list_page.response_headers['Content-Disposition'].should include 'attachment; filename='
  // }

  it('shows an error if any of the selected files is not readable', :edit => false do
    FileUtils.chmod 0000, language_path + 'rspeclingo/admin_lang.php'

    click_link "Rspeclingo"
    cy.hasNoErrors()

    list_page.find('input[type="checkbox"][title="select all"]').set(true)
    list_page.get('bulk_action').should('be.visible')
    list_page.get('bulk_action').select "Export (Download)"
    list_page.get('action_submit_button').click()
    cy.hasNoErrors()

    list_page.get('alert').should('be.visible')

    FileUtils.chmod 0644, language_path + 'rspeclingo/admin_lang.php'
  }

  // Not sure how to force this error
  // it('shows an error if a ZipArchive cannot be created', :edit => false do
  //   list_page.find('input[type="checkbox"][title="select all"]').set(true)
  //   list_page.get('bulk_action').select "Export (Download)"
  //   list_page.get('action_submit_button').click()
  // }

  it('uses the default language when language is not specified in the URL', :edit => false do
    new_url = list_page.current_url.gsub('/english', '')

    visit(new_url)

    list_page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('English Language Files'
  }

  it('can use multiple languages', :edit => false do
    click_link "Rspeclingo"
    cy.hasNoErrors()

    list_page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Rspeclingo Language Files'
    list_page.should have_pagination
    list_page.should have(6).pages
    list_page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

    list_page.should have(26).rows // 25 rows per page + header row
  }

  it('returns a 404 for an invalid language', :edit => false do
    new_url = list_page.current_url.gsub('english', 'gibberish')

    visit(new_url)

    list_page.get('wrap').contains("404: Item does not exist"
  }

  it('shows a breadcrumb link on the edit page', :edit => true do
    list_page = Translate.new

    edit_page.breadcrumb.find('a').click()
    cy.hasNoErrors()

    list_page.should be_displayed
    edit_page.should_not be_displayed
  }

  it('displays an error when it cannot create a new translation file', :edit => true do
    t_stat = File::Stat.new(translations_path)
    FileUtils.chmod 0000, translations_path

    edit_page.items[1].find('input').set('Rspeced!')
    edit_page.submit_button.click()
    cy.hasNoErrors()

    edit_page.get('alert').should('be.visible')
    edit_page.get('alert_error').should('be.visible')

    FileUtils.chmod t_stat.mode, translations_path
  }

  it('displays a flash message after saving a translation', :edit => true do
    FileUtils.mkdir(language_path + 'english')
    FileUtils.cp_r(Dir.glob(english_path + '*'), language_path + 'english/')
    FileUtils.chmod 0777, language_path + 'english/addons_lang.php'

    edit_page.items[1].find('input').set('Rspeced!')
    edit_page.submit_button.click()
    cy.hasNoErrors()

    edit_page.get('alert').should('be.visible')
    edit_page.get('alert_success').should('be.visible')
    File.exists?(language_path + 'english/addons_lang.php')
  }

  it('displays an error when it cannot write to the translations directory (update a translation)', :edit => true do
    FileUtils.chmod 0000, language_path + 'english/addons_lang.php'

    edit_page.items[1].find('input').set('Rspeced!')
    edit_page.submit_button.click()
    cy.hasNoErrors()

    edit_page.get('alert').should('be.visible')
    edit_page.get('alert_error').should('be.visible')

    FileUtils.chmod 0777, language_path + 'english/addons_lang.php'
  }

  it('displays an error when trying to edit a file that is not readable', :edit => true do
    // Off the rspeclingo language
    new_url = list_page.current_url.gsub('english', 'rspeclingo')
    visit(new_url)

    FileUtils.chmod 0000, language_path + 'rspeclingo/addons_lang.php'

    edit_page.items[1].find('input').set('Rspeced!')
    edit_page.submit_button.click()
    cy.hasNoErrors()

    edit_page.get('alert').should('be.visible')
    edit_page.get('alert_error').should('be.visible')

    FileUtils.chmod 0644, language_path + 'rspeclingo/addons_lang.php'
  }*/
})
