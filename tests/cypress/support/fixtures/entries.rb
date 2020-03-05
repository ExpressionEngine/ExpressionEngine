#!/usr/bin/env ruby

require 'optparse'
require 'forgery'
require 'mysql2'
require 'active_record'

# Random time
def rand_time(from, to=Time.now)
  Time.at(rand_in_range(from.to_f, to.to_f))
end

# Get random item in range
def rand_in_range(from, to)
  rand * (to - from) + from
end

# Default Options
options = {
  :adapter  => 'mysql2',
  :database => 'ee-test',
  :username => 'root',
  :password => 'root',
  :number   => 20
}

# Parse out command line options
OptionParser.new do |opts|
  opts.banner = "Add many entries to your EE site for testing.
Usage: entries.rb [options] <Channel ID>"

  opts.on("--db-name NAME", "Database Name") do |name|
    options[:database] = name
  end

  opts.on("--db-username USERNAME", "Database Username") do |username|
    options[:username] = username
  end

  opts.on("--db-password PASSWORD", "Database Password") do |password|
    options[:password] = password
  end

  opts.on("-n N", "--number N", "Number of entries") do |number|
    options[:number] = number
  end
end.parse!

# Get the Channel ID, must be first argument
raise OptionParser::MissingArgument if ARGV[0].nil?
options[:channel_id] = ARGV[0].to_i

# Establish the connection with the database and setup Active Record
ActiveRecord::Base.establish_connection(options)
# ActiveRecord::Base.logger = Logger.new(STDOUT) # Enable query output
class Channels < ActiveRecord::Base; self.table_name = 'exp_channels'; end
class ChannelData < ActiveRecord::Base; self.table_name = 'exp_channel_data'; end
class ChannelFields < ActiveRecord::Base; self.table_name = 'exp_channel_fields'; end

class ChannelFieldGroups < ActiveRecord::Base; self.table_name = 'exp_channels_channel_field_groups'; end
class ChannelFieldGroupsFields < ActiveRecord::Base; self.table_name = 'exp_channel_field_groups_fields'; end
class ChannelChannelFields < ActiveRecord::Base; self.table_name = 'exp_channels_channel_fields'; end

class ChannelTitles < ActiveRecord::Base; self.table_name = 'exp_channel_titles'; self.primary_key = 'entry_id'; end
class Member < ActiveRecord::Base; self.table_name = 'exp_members'; end

# First let's get the channel information
channel = Channels.find(options[:channel_id])

field_groups = ChannelFieldGroups.where(channel_id: options[:channel_id]).pluck('group_id')
field_ids = ChannelFieldGroupsFields.where(group_id: field_groups).pluck('field_id')
field_ids += ChannelChannelFields.where(channel_id: options[:channel_id]).pluck('field_id')

fields = ChannelFields.all
  .where(field_id: field_ids)
  .select('field_id, field_type, field_fmt')

allowed_fields = ['textarea', 'text', 'rte', 'date']

# Alright, let's add the entries
options[:number].to_i.times do
  time = Time.now

  # First, get a random member who can add this stuff, use the Super Admin group
  member = Member.where(group_id: 1)
    .order('RAND()')
    .first()
  # Generate title
  title = Forgery(:lorem_ipsum).words(rand(5..15), :random => true).titlecase

  # Next, create a channel entry
  channel_title = ChannelTitles.create(
    site_id:            channel.site_id,
    channel_id:         channel.channel_id,
    author_id:          member.member_id,
    ip_address:         '127.0.0.1',
    title:              title[0..99],
    url_title:          title[0..70].downcase.gsub(' ', '-'),
    status:             'open',
    status_id:          1,
    versioning_enabled: 'y',
    entry_date:         time.to_i,
    edit_date:          time.strftime('%Y%m%d%H%M%S'),
    year:               time.year,
    month:              time.month,
    day:                time.day,
  )

  # Next, setup the data to add to fields
  data = ChannelData.new
  data.entry_id   = channel_title.entry_id
  data.site_id    = channel.site_id
  data.channel_id = channel.channel_id

  fields.each do |field|
    # Skip ahead unless it's an allowed field type
    next unless allowed_fields.include?(field.field_type)

    # Figure out what kind of data based on the field_type
    case field.field_type
    when 'textarea'
      field_data = Forgery(:lorem_ipsum).paragraphs(
        rand(1..6),
        :html => (field.field_fmt == 'xhtml' ? true : false),
        :sentences => rand(3..6),
        :separator => (field.field_fmt == 'xhtml' ? '' : "\n\n")
      )
      data['field_ft_' + field.field_id.to_s] = (field.field_fmt == 'xhtml' ? 'xhtml' : 'none')
    when 'text'
      field_data = Forgery(:lorem_ipsum).words(
        rand(5..15),
        :random => true
      ).capitalize
      data['field_ft_' + field.field_id.to_s] = (field.field_fmt == 'xhtml' ? 'xhtml' : 'none')
    when 'date'
      field_data = rand_time(2.months.ago).to_i
      data['field_dt_' + field.field_id.to_s] = member.timezone
    when 'rte'
      field_data = Forgery(:lorem_ipsum).paragraphs(
        rand(1..6),
        :html => true,
        :sentences => rand(3..6),
        :separator => ''
      )
      data['field_ft_' + field.field_id.to_s] = 'xhtml'
    end

    data['field_id_' + field.field_id.to_s] = field_data
  end

  # Now save the data
  data.save!

  # Increment entry count and change last_entry_date
  channel.total_records = channel.total_records + 1
  channel.total_entries = channel.total_entries + 1
  channel.save
  member.total_entries = member.total_entries + 1
  member.last_entry_date = time.to_i
  member.save
end
