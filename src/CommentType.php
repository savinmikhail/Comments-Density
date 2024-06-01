<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

enum CommentType: string
{
    case LICENSE = 'license';
    case DOCBLOCK = 'docBlock';
    case TODO = 'todo';
    case FIXME = 'fixme';
    case REGULAR = 'regular';
    case MISSING_DOCBLOCK = 'missingDocblock';
}
