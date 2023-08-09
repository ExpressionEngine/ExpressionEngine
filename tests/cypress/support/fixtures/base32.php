<?php

require('bootstrap.php');

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

$secret = \ExpressionEngine\Dependency\ParagonIE\ConstantTime\Base32::encodeUpper($options['uid'] . md5($options['code']));
echo $secret;
exit();
