import ControlPanel from '../ControlPanel'

class ForumTab extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'forum_title': 'input[type!=hidden][name="forum__forum_title"]',
      'forum_body': 'textarea[name="forum__forum_body"]',
      'forum_id': 'div[data-input-value="forum__forum_id"]',
      'forum_id_choices': 'div[data-input-value="forum__forum_id"] .field-drop-choices label',
      'forum_topic_id': 'input[type!=hidden][name="forum__forum_topic_id"]'
    })
  }

  // Install forum, create a board, category, and forum
  install_forum() {
    cy.authVisit('/admin.php?/cp/addons')
    cy.get('a[data-post-url*="cp/addons/install/forum"]').click()
    cy.get('a[href*="cp/addons/settings/forum"]').click()

    // Create board
    cy.get('a[href*="cp/addons/settings/forum/create/board"]').click()
    cy.get('input[type!=hidden][name="board_label"]').type('Board')

    //cy.get('.w-12 button[value="save_and_close"]:first-child').click()
    this.get('wrap').find('div').find('button').eq(2).click()
    // Create category
    cy.get('.tbl-search a[href*="cp/addons/settings/forum/create/category/1"]').click()
    cy.get('input[type!=hidden][name="forum_name"]').type('Category')
    //cy.get('.w-12 button[value="save_and_close"]:first-child').click()
    this.get('wrap').find('div').find('button').eq(2).click()
    // Create forum
    cy.get('.tbl-action a[href*="cp/addons/settings/forum/create/forum/1"]').click()
    cy.get('input[type!=hidden][name="forum_name"]').type('Forum')

    this.get('wrap').find('div').find('button').eq(2).click()
    //cy.get('.w-12 button[value="save_and_close"]:first-child').click()
  }

  create_entry(){
    cy.get('.ajax-validate > .fieldset-required > .field-control > input').type('title')
    cy.get('textarea').type('body')
    cy.get(':nth-child(5) > .field-control > .fields-select > .field-inputs > :nth-child(3) > input').click()
    cy.get('.tab-bar__right-buttons .form-btns > [value="save_and_close"]').click()
    cy.get('.app-notice__content > :nth-child(2)').contains("The forum")
    cy.get('.app-notice__content > :nth-child(2)').contains(" has been created")


  }

  //   page.submit_buttons[2].click()

  //   page.all_there?.should == false
  //   page.get('alert').has_content?("The entry #{title} has been created.").should == true


}
export default ForumTab;


