<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final readonly class ClassWithThrowingMethod
{
    /**
     * @return void
     * @throws Exception
     */
    public function bar(): never
    {
        throw new Exception();
    }
}
