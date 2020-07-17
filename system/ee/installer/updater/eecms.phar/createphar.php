<?php

$src_folder = __DIR__.'/src';
$build_folder = realpath(__DIR__.'/../../../');

$phar = new Phar($build_folder . '/eecms.phar', 0, 'eecms.phar');
$phar->setStub("#!/usr/bin/env php\n".$phar->createDefaultStub('index.php'));
$phar->buildFromDirectory($src_folder);
rename($build_folder.'/eecms.phar', $build_folder.'/eecms');
