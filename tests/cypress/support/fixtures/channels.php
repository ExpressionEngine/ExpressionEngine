<?php

require('bootstrap.php');
require_once '../../vendor/fzaninotto/faker/src/autoload.php';

$command = array_shift($argv);

$longopts = array(
	"max_entries:",
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

$title = $faker->words(mt_rand(2, 5), TRUE);

$channel = ee('Model')->make('Channel', array(
  'channel_name' =>            strtolower(str_replace(' ', '-', substr($title, 0, 70))),
  'channel_title'=>         substr($title, 0, 99),
  'channel_lang'=>          'en',
  'max_entries'=>         (isset($options['max_entries']) && is_numeric($options['max_entries'])) ? $options['max_entries'] : ''
));

//puts channel.to_json

