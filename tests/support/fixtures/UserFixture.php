<?php

declare(strict_types=1);

namespace app\tests\support\fixtures;

use app\usecases\shared\user\User;
use yii\test\ActiveFixture;

/**
 * Provides user fixture data for authentication tests.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;
}
