<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\{Controller, ExitCode};

/**
 * Echoes the first argument that you have entered.
 *
 * Provided as an example for learning how to create console commands.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
class HelloController extends Controller
{
    /**
     * Command echoes what you have entered as the message.
     *
     * @param string $message Message to be echoed.
     *
     * @return int Exit code
     */
    public function actionIndex(string $message = 'hello world'): int
    {
        $this->stdout("{$message}\n");

        return ExitCode::OK;
    }
}
