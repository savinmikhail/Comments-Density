<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

final class ClassWithCallForThrowingMethodInDifferentClass
{
    public function baz(): void
    {
        $bar = new ClassWithThrowingMethod();
        $bar->bar();
    }
}
