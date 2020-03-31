<?php

require('bootstrap.php');
require_once '../../vendor/fzaninotto/faker/src/autoload.php';

$faker = Faker\Factory::create();

$command = array_shift($argv);

$longopts = array(
	"max-entries:",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
  print <<<EOF
Add a channel to your EE site for testing.
Usage: {$command} [options]
	--help                   This help message
	--max-entries      <number> Maximum entries
EOF;
	exit();
}

$title = $faker->words(mt_rand(2, 3), TRUE);

$channel = ee('Model')->make('Channel', array(
  'channel_name' =>            strtolower(str_replace(' ', '-', substr($title, 0, 70))),
  'channel_title'=>         substr($title, 0, 99),
  'channel_lang'=>          'en',
  'max_entries'=>         (isset($options['max-entries']) && is_numeric($options['max-entries'])) ? $options['max-entries'] : '0'
))->save()->toArray();

echo json_encode($channel);

//puts channel.to_json

