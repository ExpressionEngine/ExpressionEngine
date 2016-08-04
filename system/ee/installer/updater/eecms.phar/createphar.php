<?php

$src_folder = __DIR__.'/src';
$build_folder = __DIR__.'/build';

$phar = new Phar($build_folder . '/eecms.phar', 0, 'eecms.phar');
$phar->setStub($phar->createDefaultStub('index.php'));
$phar->buildFromDirectory($src_folder);
