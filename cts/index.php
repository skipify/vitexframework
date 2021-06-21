<?php

define("ROOT",__DIR__);
define("SRC",ROOT.'/src');

if (!version_compare(PHP_VERSION, '8.0', '>=')) {
    exit("Need At least PHP version 8.0.0");
}

include (__DIR__.'/src/ParseOption.php');
include (ROOT .'/src/Utils.php');
$commands = include(__DIR__.'/src/command.php');


$command = array_shift($argv);
$command = array_shift($argv);

if(!in_array($command,$commands)){
    echo  'Not Found Command:' .$command ;
    exit;
}
$parseOption = new ParseOption($argv);
/**
 * @var $parser CommandInterface
 */
$className = ucfirst($command).'Command';


$parser = new $className($parseOption->parse());

$parser->run();