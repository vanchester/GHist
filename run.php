#!/usr/bin/env php
<?php
namespace GHist;

require __DIR__.'/GHist/SplClassLoader.php';

$loader = new \SplClassLoader();
$loader->register();

$outputAdapter = new Adapter\TextFile(__DIR__.'/history/', 'admin@vanchester.ru');

$steps = new Steps($outputAdapter);

$steps->showWelcomeMsg();
$steps->login();
$steps->selectLabel();

$steps->exportHistory();