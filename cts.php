<?php
ini_set('display_errors','on');
$phar = new Phar(__DIR__ ."/cts.phar",0,"cts.phar");
$phar->buildFromDirectory(__DIR__."/cts");
$phar->setDefaultStub("index.php");
echo 'ok';