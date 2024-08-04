<?php

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Iterator;


interface InterfaceExtendsIterable extends Iterator
{
    /**
     * @return mixed
     */
    public function get();
}
