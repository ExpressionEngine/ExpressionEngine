<?php

require('bootstrap.php');

\ExpressionEngine\Core\Autoloader::getInstance()
    ->addPrefix('ParagonIE\ConstantTime', SYSPATH . 'ee/ExpressionEngine/Addons/pro/lib/paragonie/constant_time_encoding')
    ->register();

$command = array_shift($argv);

$longopts = array(
	"help",
    "uid:",
    "code:",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
  print <<<EOF
base32 upper encode secret
Usage: {$command} [options]
	--help                   This help message
EOF;
	exit();
}

$secret = \ParagonIE\ConstantTime\Base32::encodeUpper($options['uid'] . md5($options['code']));
echo $secret;
exit();
