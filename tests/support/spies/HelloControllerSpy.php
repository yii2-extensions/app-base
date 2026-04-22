<?php

declare(strict_types=1);

namespace app\tests\support\spies;

use app\commands\HelloController;

use function strlen;

/**
 * Test spy for {@see HelloController}.
 *
 * Yii2's `Controller::stdout()` writes directly to the `STDOUT` stream via `fwrite`, which
 * bypasses PHPUnit / Codeception output capture. This spy buffers everything passed to
 * `stdout()` into {@see $stdoutBuffer} so tests can assert on it.
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
