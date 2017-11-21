class ChannelManager < ControlPanelPage

  elements :channels, '.tbl-list > li .main > a'
  elements :channels_checkboxes, '.tbl-list > li input[type="checkbox"]'
  element :select_all, '.ctrl-all input'
  element :import, 'a[rel=import-channel]'

  # Get a channel ID from a channel name or title
  #
  # @param [String] name The channel name/title to look for
  # @raise [RuntimeError] if the channel name does not exist
  # @return [Integer] The channel's ID
  def get_channel_id_from_name(name)
    $db.query('SELECT channel_id FROM exp_channels WHERE channel_name = "'+name+'"').each(:as => :array) do |row|
      return row[0]
    end

    raise 'No known channel'
  end

  def load
    self.open_dev_menu
    click_link 'Channels'
  end
end
