<?php

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Iterator;

/**
 * @template T
 */
interface TemplatedInterface extends Iterator
{
    /**
     * @return T
     */
    public function get(): mixed;
}
