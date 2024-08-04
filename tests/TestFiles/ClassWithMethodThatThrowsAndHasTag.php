<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Exception;

final class ClassWithMethodThatThrowsAndHasTag
{
    public function baz(): void
    {
        $this->bar();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function bar(): void
    {
        throw new Exception();
    }
}
