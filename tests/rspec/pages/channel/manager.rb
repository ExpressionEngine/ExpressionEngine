class ChannelManager < ControlPanelPage

  elements :channels, '.tbl-list > li .main > a'
  elements :channels_checkboxes, '.tbl-list > li input[type="checkbox"]'
  element :select_all, '.ctrl-all input'

  # Get a channel ID from a channel name or title
  #
  # @param [String] name The channel name/title to look for
  # @raise [RuntimeError] if the channel name does not exist
  # @return [Integer] The channel's ID
  def get_channel_id_from_name(name)
    channels.each do |element|
      return element.find('td:nth-child(1)').text.to_i if element.text.downcase.include? name.downcase
    end

    raise 'No known channel'
  end

  def load
    self.open_dev_menu
    click_link 'Channels'
  end
end
