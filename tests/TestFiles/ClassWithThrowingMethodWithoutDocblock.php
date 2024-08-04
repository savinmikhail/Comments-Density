<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final class ClassWithThrowingMethodWithoutDocblock
{
    /**
     * @return void
     */
    public function bar(): void
    {
        throw new Exception();
    }
}