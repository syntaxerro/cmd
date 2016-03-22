#!/usr/bin/env php
<?php
/** @noinspection PhpIncludeInspection */
require __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$kernel = new \SyntaxErro\Kernel\Kernel();
$kernel->loadCommands()->run();
