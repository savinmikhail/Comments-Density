<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final readonly class ClassWithMethodThatThrowsAndHasTag
{
    public function baz(): never
    {
        $this->bar();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function bar(): never
    {
        throw new Exception();
    }
}
