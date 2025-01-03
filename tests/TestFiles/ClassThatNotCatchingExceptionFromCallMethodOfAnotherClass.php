<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Mockery\Exception;

final readonly class ClassThatNotCatchingExceptionFromCallMethodOfAnotherClass
{
    public function baz(): void
    {
        $bar = new ClassWithThrowingMethod();

        try {
            $bar->bar();
        } catch (Exception) {
        }
    }
}
