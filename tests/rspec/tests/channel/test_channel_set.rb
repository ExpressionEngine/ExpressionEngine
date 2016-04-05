require './bootstrap.rb'

feature 'Channel Sets' do
  before :each do
    cp_session
    @page = ChannelManager.new
    @page.load
    no_php_js_errors
  end


  # Download a channel set using Ajax
  #
  # @param Integer id The ID of the channel to download
  # @return Boolean TRUE if the download was successful
  def download_channel_set(id)
    @page.execute_script("window.downloadCSVXHR = function(){ var url = window.location.protocol + '//' + window.location.host + '//system/index.php?/cp/channels/sets/export/#{id}'; return getFile(url); }")
    @page.execute_script('window.getFile = function(url) { var xhr = new XMLHttpRequest();  xhr.open("GET", url, false);  xhr.send(null); return xhr.responseText; }')
    data = @page.evaluate_script('downloadCSVXHR()')
    data.should start_with('PK')
  end

  it 'downloads the zip file when exporting channel sets' do
    download_channel_set(1)

    # Check to see if the file exists
    name = @page.channel_names[0].text
    path = File.expand_path("../../system/user/cache/cset/#{name}.zip")
    File.exist?(path).should == true
  end

  it 'exports multiple channel sets'

  context 'when importing channel sets' do
    it 'imports a channel set'
    it 'imports a channel set with additional Default statuses'
    context 'with file fields' do
      it 'imports without a specified directory'
      it 'imports with a specified directory'
    end
    context 'with grid fields' do
      it 'imports without a relationship column'
      it 'imports with a relationship column'
    end
    context 'with relationship fields' do
      it 'imports'
    end
    it 'imports a two channel set'
  end
end
