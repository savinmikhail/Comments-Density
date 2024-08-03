<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

final readonly class ClassThatCatchingMethodCallOfAnotherClassWhichDoesNotHaveDocblock
{
    public function baz(): void
    {
        $bar = new ClassWithThrowingMethodWithoutDocblock();
        $bar->bar();
    }
}