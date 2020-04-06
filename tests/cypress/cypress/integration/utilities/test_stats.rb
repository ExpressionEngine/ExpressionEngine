require './bootstrap.rb'

context('Statistics', () => {

  beforeEach(function() {
    cy.auth();
    page = Stats.new
    page.load()

    page.should be_displayed
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Manage Statistics'
    page.should have_content_table
  }

  it "shows the Manage Statistics page" do
    page.should have(4).rows // 3 rows + header
    page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    page.counts.map {|count| count.text}.should == ["10", "7", "1"]
  }

  it "can sort by source" do
    page.find('a.sort')[0].click()
    page.sources.map {|source| source.text}.should == ["Sites", "Members", "Channel Entries"]
    page.content_table.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Source'

    page.find('a.sort')[0].click()
    page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    page.content_table.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Source'
  }

  it "can sort by count" do
    page.find('a.sort')[1].click()
    page.counts.map {|count| count.text}.should == ["1", "7", "10"]
    page.content_table.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Record Count'

    page.find('a.sort')[1].click()
    page.counts.map {|count| count.text}.should == ["10", "7", "1"]
    page.content_table.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Record Count'
  }

  it "reports accurate record count after adding a member" do
    add_member(username: 'johndoe')
    page.load()

    page.should have(4).rows // 3 rows + header
    page.sources.map {|source| source.text}.should == ["Channel Entries", "Members", "Sites"]
    page.counts.map {|count| count.text}.should == ["10", "8", "1"]
  }

  it "can sync one source" do
    page.content_table.find('tr:nth-child(2) li.sync a').click()

    page.get('alert').should('be.visible')
    page.get('alert_success').should('be.visible')
  }

  it "can sync multiple sources" do
    page.find('input[type="checkbox"][title="select all"]').set(true)
    page.get('bulk_action').should('be.visible')
    page.get('bulk_action').select "Sync"
    page.get('action_submit_button').click()

    page.get('alert').should('be.visible')
    page.get('alert_success').should('be.visible')
  }

}
