<?php

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Iterator;


interface InterfaceExtendsIterable extends Iterator
{
    public function get(): mixed;
}
