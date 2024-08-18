<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final readonly class ClassWithThrowingMethodWithoutDocblock
{
    /**
     * @return void
     */
    public function bar(): never
    {
        throw new Exception();
    }
}