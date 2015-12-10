<?php

namespace PhpInPractice\Matters\Projection;

use MabeEnum\Enum;

/**
 * @method static SOFT()
 * @method static HARD()
 */
final class DeletionMode extends Enum
{
    const SOFT = 'soft';
    const HARD = 'hard';
}
