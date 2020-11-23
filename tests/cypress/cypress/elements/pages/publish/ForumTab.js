import ControlPanel from '../ControlPanel'

class ForumTab extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'forum_title': 'input[name="forum__forum_title"]',
      'forum_body': 'textarea[name="forum__forum_body"]',
      'forum_id': 'div[data-input-value="forum__forum_id"]',
      'forum_id_choices': 'div[data-input-value="forum__forum_id"] .field-drop-choices label',
      'forum_topic_id': 'input[name="forum__forum_topic_id"]'
    })
  }

  // Install forum, create a board,
  install_forum() {
    cy.authVisit('/admin.php?/cp/addons')

    cy.get('input[title="select all"]').click()
      cy.get('select[name="bulk_action"]').select('Install')
      cy.get('button').contains('Submit').click()
       cy.get('input[type="submit"]').first().click()


    //cy.get('tbody > :nth-child(2) > :nth-child(1) > a').click() //GOTO Forums
    cy.visit('/admin.php?/cp/addons/settings/forum/create/board')


    cy.get('input[name="board_label"]').type('Board')
  
    cy.get('button[value="save_and_close"]').click()
   
    // // Create category
    // cy.get('td > .btn').click()//click create button
    // cy.get('.ajax-validate > .fieldset-required > .field-control > input').type('category')
    // cy.get('.form-btns-top > [value="save_and_close"]').click()

    // // //GOTO FORUM
    // cy.get('.no-results > .solo > a').eq(0).click()
    // // Create forum
    // cy.get('input[name="forum_name"]').type('Forum')
    
    // cy.get('.form-btns-top > [value="save_and_close"]').click()
    
  }

  create_entry(){
   
    cy.get('.t-0 > :nth-child(1) > .field-control > input').type('title')
   
    cy.get('a').contains('Forum').click()
    cy.get('.t-4 > :nth-child(1) > .field-control > input').type('title')
    cy.get('.field-control > textarea').type('body')
    cy.get('i').contains('Choose').click()
    cy.get('label').contains('board: Forum').click()
    
    
    cy.get('.form-btns-top > [value="save_and_close"]').click()
    cy.get('.app-notice__content > :nth-child(2)').contains("The entry")
    cy.get('.app-notice__content > :nth-child(2)').contains(" has been created")
  }

  get_post(){
    return cy.task('db:query', 'SELECT title, body FROM exp_forum_topics').then(function([rows,fields]){
      return rows;
    })
  }

 

   


}
export default ForumTab;




