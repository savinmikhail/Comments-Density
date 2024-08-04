<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final class ClassWithCallForThrowingMethodInDifferentClassInTryCatch
{
    public function baz(): void
    {
        $bar = new ClassWithThrowingMethod();
        try {
            $bar->bar();
        } catch (Exception $exception) {

        }
    }
}