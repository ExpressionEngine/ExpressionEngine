import Publish from '../../elements/pages/publish/Publish';
import ForumTab from '../../elements/pages/publish/ForumTab';

const page = new Publish;
const helper = new ForumTab


context('Forum Tab', () => {

	 before(function() {
	 	helper.install_forum()
	 })

	 it('has a forum tab', () => {
      	cy.get('.selected > :nth-child(2) > a').click()
      	cy.get('.ajax-validate > .fieldset-required > .field-control > input')
      	cy.get('textarea')
      	cy.get(':nth-child(7) > .field-control > input')
     })

     it('creates a forum post when entering data into the forum tab', () => {
     	cy.get('.solo > .btn').click() // click the New Forum button
      	helper.create_entry()
     })



})

