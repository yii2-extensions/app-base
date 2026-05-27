<?php

declare(strict_types=1);

namespace app\usecases\shared\user;

use yii\base\{InvalidArgumentException, Model};

/**
 * Handles email verification after user registration.
 *
 * Shared by the `user/verify-email` interstitial slice and the `user/confirm-email` commit slice, which is why this
 * form lives in the shared user kernel rather than in either slice.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
class VerifyEmailForm extends Model
{
    /**
     * Resolved from the verification token, or `null` if not found.
     */
    private User|null $user = null;

    /**
     * Creates a form model with given token.
     *
     * @param string $token Verification token.
     * @param array<string, mixed> $config name-value pairs that will be used to initialize the object properties.
     *
     * @throws InvalidArgumentException if token is empty or not valid.
     */
    public function __construct(string $token, array $config = [])
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException(
                'Verify email token cannot be blank.',
            );
        }

        $this->user = User::findByVerificationToken($token);

        if ($this->user === null) {
            throw new InvalidArgumentException(
                'Wrong verify email token.',
            );
        }

        parent::__construct($config);
    }

    /**
     * Verifies email.
     *
     * @return User|null Saved model or `null` if saving fails.
     */
    public function verifyEmail(): User|null
    {
        if ($this->user === null) {
            return null;
        }

        $this->user->status = User::STATUS_ACTIVE;
        $this->user->verification_token = null;

        return $this->user->save(false) ? $this->user : null;
    }
}
