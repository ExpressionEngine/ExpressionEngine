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

     it.only('creates a forum post when entering data into the forum tab', () => {
     	cy.get('.solo > .btn').click() // click the New Forum button
      	helper.create_entry()
     })



})

   //   it.only('creates a forum post when entering data into the forum tab', () => {
	  //   create_entry

	  //   $db.query('SELECT title, body FROM exp_forum_topics').each do |row|
	  //     row['title'].should == title
	  //     row['body'].should == body
	  //   }

	  //   $db.query('SELECT forum_topic_id FROM exp_channel_titles ORDER BY entry_id desc LIMIT 1').each do |row|
	  //     row['forum_topic_id'].should == 1
	  //   }

	  //   click_link(title)
	  //   page.tab_links[4].click()
	  //   page.forum_tab.should have_css('textarea[name=forum__forum_body][disabled]')
	  //   page.forum_tab.should have_css('.fields-select-drop.field-disabled')
	  // }
