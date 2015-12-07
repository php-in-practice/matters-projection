<?php

namespace PhpInPractice\Matters\Projection;

use MabeEnum\Enum;

/**
 * @method static SOFT()
 * @method static HARD()
 */
final class ProjectionDeletion extends Enum
{
    const SOFT = 'soft';
    const HARD = 'hard';
}
