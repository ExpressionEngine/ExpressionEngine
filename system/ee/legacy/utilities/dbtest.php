<?php
error_reporting(0);

$hostname	= "YOUR_HOSTNAME";
$username	= "YOUR_USERNAME";
$password	= "YOUR_PASSWORD";
$db_name	= "NAME_OF_YOUR_DATABASE";

$conn_type = 0;  // 1 = persistent    0 = non-persistent



$result = ($conn_type == 1) ? mysql_pconnect($hostname, $username, $password) : mysql_connect($hostname, $username, $password);

echo '<br />';

if ( ! $result)
{
	echo 'Unable to connect to your database server';
}
else
{
	echo 'A connection was established to your database server';
}


echo '<br /><br />';

if ( ! mysql_select_db($db_name))
{
	echo 'Unable to select your database';
}
else
{
	echo 'Your database was selected.';
}

// EOF
