<?php
include('system/expressionengine/modules/spam/libraries/vectorize.php');
//include('system/expressionengine/modules/spam/libraries/classifier.php');

$test = [];
for($i = 1; $i <= 25; $i++)
{
	$test[] = file_get_contents('system/expressionengine/modules/spam/training/spam/' . $i);
}
$spams = $test;
$c = new Collection($test);
$s = $c->tfidf();

$test = [];
for($i = 1; $i <= 25; $i++)
{
	$test[] = file_get_contents('system/expressionengine/modules/spam/training/ham/' . $i);
}
$c = new Collection($test);
$h = $c->tfidf();

$training = ['spam' => $s, 'ham' => $h];

$a = new ASCII_Printable();
foreach($spams as $spam) {
	var_dump($spam);
	var_dump($a->vectorize($spam));
}
?>