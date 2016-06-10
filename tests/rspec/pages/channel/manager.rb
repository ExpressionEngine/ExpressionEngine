class ChannelManager < ControlPanelPage

  element :table, 'table'
  element :sort_col, 'table th.highlight'
  element :import, '.tbl-search .action[href*=sets]'
  elements :channels, 'table tr'
  elements :channel_titles, 'table tr td:nth-child(2)'
  elements :channel_names, 'table tr td:nth-child(3)'

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
    click_link 'Channel Manager'
  end
end
