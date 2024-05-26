<?php

use Savinmikhail\CommentsDensity\CommentDensity;

$filename = __DIR__ . "/../tests/sample.php";
$commentDensity  = new CommentDensity($filename);
$commentDensity->printStatistics();
