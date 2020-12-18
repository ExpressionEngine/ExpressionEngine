<?php

require('bootstrap.php');
require_once '../../vendor/fzaninotto/faker/src/autoload.php';

$command = array_shift($argv);

$longopts = array(
	"channel:",
	"number:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
  print <<<EOF
Add many entries to your EE site for testing.
Usage: {$command} [options]
	--help                   This help message
	--number      <number> Number of entries to create
	--channel      <number> Channel ID
EOF;
	exit();
}
/*
# Random time
def rand_time(from, to=Time.now)
  Time.at(rand_in_range(from.to_f, to.to_f))
end

# Get random item in range
def rand_in_range(from, to)
  rand * (to - from) + from
end
*/
$channel_id = isset($options['channel']) && is_numeric($options['channel']) ? (int) $options['channel'] : 1;
$number = isset($options['number']) && is_numeric($options['number']) ? (int) $options['number'] : 20;


# First let's get the channel information
$channel = ee('Model')->get('Channel', $channel_id)
  ->with('FieldGroups', 'CustomFields')
  ->first();

$fields = $channel->getAllCustomFields();

$allowed_fields = ['textarea', 'text', 'rte', 'date'];

$faker = Faker\Factory::create();

# Alright, let's add the entries
for ($i=0; $i < $number; $i++) {
  $time = ee()->localize->now;

  # First, get a random member who can add this stuff, use the Super Admin group
  $members = ee('Model')->get('Member')
    ->filter('role_id', 1)
    ->all()
    ->getDictionary('member_id', 'member_id');
  $member =  ee('Model')->get('Member', array_rand($members))->first();
  # Generate title
  $title = $faker->words(mt_rand(5, 15), TRUE);

  # Next, create a channel entry
  $entry = ee('Model')->make('ChannelEntry', array(
    'site_id' =>            $channel->site_id,
    'channel_id'=>         $channel->channel_id,
    'author_id'=>          $member->member_id,
    'ip_address'=>         '127.0.0.1',
    'title'=>              substr($title, 0, 99),
    'url_title'=>         strtolower(str_replace(' ', '-', substr($title, 0, 70))),
    'status'=>            'open',
    'status_id'=>         1,
    'versioning_enabled'=> 'y',
    'entry_date'=>         $time,
    'edit_date'=>         ee()->localize->format_date('%Y%m%d%H%M%S', $time),
    'year'=>              ee()->localize->format_date('%Y', $time),
    'month'=>             ee()->localize->format_date('%m', $time),
    'day'=>              ee()->localize->format_date('%d', $time),
  ));

  # Next, setup the data to add to fields

  foreach ($fields as $field) {
    # Skip ahead unless it's an allowed field type
    if (!in_array($field->field_type, $allowed_fields)) continue;
    # Figure out what kind of data based on the field_type
    switch ($field->field_type) {
      case 'textarea':
        $field_data = $faker->paragraphs(
          mt_rand(1, 6),
          true
        );
        $entry->{'field_ft_'.$field->field_id} = ($field->field_fmt == 'xhtml' ? 'xhtml' : 'none');
        break;

      case 'text':
        $field_data = $faker->words(
          mt_rand(5, 15),
          true
        );
        $entry->{'field_ft_'.$field->field_id} = ($field->field_fmt == 'xhtml' ? 'xhtml' : 'none');
        break;
      case 'date':
        $field_data = ee()->localize->format_date('%D, %F %d, %Y %g:%i:%s%a', mt_rand(strtotime("2 months ago", $time)));
        $entry->{'field_dt_'.$field->field_id} = $member->timezone;
        break;
      case 'rte':
        $field_data = $faker->paragraphs(
          mt_rand(1, 6),
          true
        );
        $entry->{'field_ft_'.$field->field_id} = 'xhtml';
        break;
    }
    $entry->{'field_id_'.$field->field_id} = $field_data;
  }

  # Now save the data
  $entry->save();

  # Increment entry count and change last_entry_date
  $channel->total_records = $channel->total_records + 1;
  $channel->total_entries = $channel->total_entries + 1;
  $channel->save();
  $member->total_entries = $member->total_entries + 1;
  $member->last_entry_date = $time;
  $member->save();
}
