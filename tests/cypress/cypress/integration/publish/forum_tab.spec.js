import Publish from '../../elements/pages/publish/Publish';
import ForumTab from '../../elements/pages/publish/ForumTab';

const page = new Publish;
const helper = new ForumTab


context('Forum Tab', () => {

	 before(function() {
	 	helper.install_forum()
	 })



	 // after(function(){
  //     cy.authVisit('/admin.php?/cp/addons')
  //   	cy.get('input[title="select all"]').click()
  //   	cy.get('select[name="bulk_action"]').select('Uninstall')
  //   	cy.get('button').contains('Submit').click()
  //     cy.get('input[type="submit"]').first().click()
	 // })
    

     


})

