<?php

declare(strict_types=1);

namespace app\tests\support\spies;

use app\commands\HelloController;

use function strlen;

/**
 * Stub class for {@see HelloController} that captures output sent to STDOUT in a buffer for inspection in tests.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class HelloControllerSpy extends HelloController
{
    /**
     * Concatenated capture of every string passed to {@see stdout()}.
     */
    public string $stdoutBuffer = '';

    /**
     * @param string $string String to buffer instead of writing to `STDOUT`.
     */
    public function stdout($string)
    {
        $this->stdoutBuffer .= (string) $string;

        return strlen((string) $string);
    }
}
