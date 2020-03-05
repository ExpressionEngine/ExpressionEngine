#!/usr/bin/env ruby

require 'optparse'
require 'forgery'
require 'mysql2'
require 'active_record'

# Default Options
options = {
  :adapter  => 'mysql2',
  :database => 'ee-test',
  :username => 'root',
  :password => 'root',
  :max_entries => ''
}

# Parse out command line options
OptionParser.new do |opts|
  opts.banner = "Add a channel to your EE site for testing.
Usage: channels.rb [options] <Channel ID>"

  opts.on("--db-name NAME", "Database Name") do |name|
    options[:database] = name
  end

  opts.on("--db-username USERNAME", "Database Username") do |username|
    options[:username] = username
  end

  opts.on("--db-password PASSWORD", "Database Password") do |password|
    options[:password] = password
  end

  opts.on("--max-entries MAXENTRIES", "Maximum entries") do |max_entries|
    options[:max_entries] = max_entries
  end
end.parse!

# Establish the connection with the database and setup Active Record
ActiveRecord::Base.establish_connection(options)
# ActiveRecord::Base.logger = Logger.new(STDOUT) # Enable query output
class Channels < ActiveRecord::Base; self.table_name = 'exp_channels'; end

title = Forgery(:lorem_ipsum).words(rand(2..5), :random => true).titlecase

channel = Channels.create(
  channel_name: title[0..70].downcase.gsub(' ', '_'),
  channel_title: title[0..99],
  channel_url: '',
  channel_lang: 'en',
  max_entries: options[:max_entries].to_i,
)

puts channel.to_json

