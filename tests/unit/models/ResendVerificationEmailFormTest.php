<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\{ResendVerificationEmailForm, User};
use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use RuntimeException;
use Yii;
use yii\base\{Event, ModelEvent};
use yii\db\BaseActiveRecord;
use yii\mail\MessageInterface;

/**
 * Unit tests for {@see \app\models\ResendVerificationEmailForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ResendVerificationEmailFormTest extends \Codeception\Test\Unit
{
    protected UnitTester|null $tester = null;

    /**
     * @return array{user: array{class: string, dataFile: string}}
     */
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                // @phpstan-ignore-next-line
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testEmptyEmailAddress(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => ''];

        self::assertFalse(
            $model->validate(),
            'Validation fails for an empty email.',
        );
        self::assertTrue(
            $model->hasErrors(),
            'Validation errors are present.',
        );
        self::assertSame(
            'Email cannot be blank.',
            $model->getFirstError('email'),
            'Blank email error message is correct.',
        );
    }

    public function testExceptionDuringSaveRollsBackTransaction(): void
    {
        $fixtureUser = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $fixtureUser,
            "Fixture user 'test.test' exists.",
        );

        $originalToken = $fixtureUser->verification_token;

        $handler = static function (): void {
            throw new RuntimeException('Forced exception during user save.');
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        try {
            $model = new ResendVerificationEmailForm();

            $model->attributes = ['email' => 'test.test@example.com'];

            $supportEmail = Yii::$app->params['supportEmail'];

            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when the transaction block throws.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }

        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertSame(
            $originalToken,
            $user->verification_token,
            'Transaction rollback preserved the original verification token.',
        );
        self::assertSame(
            [],
            $this->tester?->grabSentEmails() ?? [],
            'No email was dispatched after transaction rollback.',
        );
    }

    public function testInvalidEmailFormatFailsValidation(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'not-an-email'];

        self::assertFalse(
            $model->validate(),
            'Validation fails for an invalid email format.',
        );
        self::assertSame(
            'Email is not a valid email address.',
            $model->getFirstError('email'),
            'Invalid email format surfaces the format error.',
        );
    }

    public function testRateLimitBlocksRapidResend(): void
    {
        $supportEmail = Yii::$app->params['supportEmail'];

        $first = new ResendVerificationEmailForm();

        $first->attributes = ['email' => 'test.test@example.com'];

        self::assertTrue(
            $first->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            'First sendEmail call succeeds before rate-limit engages.',
        );

        $second = new ResendVerificationEmailForm();

        $second->attributes = ['email' => 'test.test@example.com'];

        self::assertFalse(
            $second->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "Second sendEmail call returns 'false' while cooldown is active.",
        );

        $this->tester?->seeEmailIsSent(1);
    }

    public function testRateLimitFailsOpenWhenCacheBackendWriteFails(): void
    {
        $failingCache = new class extends \yii\caching\ArrayCache {
            public function add($key, $value, $duration = 0, $dependency = null)
            {
                return false;
            }

            public function exists($key)
            {
                return false;
            }
        };

        $originalCache = Yii::$app->get('cache');

        Yii::$app->set('cache', $failingCache);

        try {
            $model = new ResendVerificationEmailForm();

            $model->attributes = ['email' => 'test.test@example.com'];

            $supportEmail = Yii::$app->params['supportEmail'];

            self::assertTrue(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "'SendEmail' fails open when the cache backend rejects writes.",
            );
        } finally {
            Yii::$app->set('cache', $originalCache);
        }
    }

    public function testRateLimitNormalizesCaseSoMismatchedAttemptsCountTowardsCooldown(): void
    {
        $supportEmail = Yii::$app->params['supportEmail'];

        $mismatched = new ResendVerificationEmailForm();

        $mismatched->attributes = ['email' => 'TEST.TEST@EXAMPLE.COM'];

        self::assertFalse(
            $mismatched->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "'SendEmail' returns 'false' for a case-mismatched address (lookup misses).",
        );

        $legit = new ResendVerificationEmailForm();

        $legit->attributes = ['email' => 'test.test@example.com'];

        self::assertFalse(
            $legit->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            'A case-correct resend is rate-limited after a prior case-mismatched attempt to the same address.',
        );
    }

    public function testResendToActiveUser(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test2.test@example.com'];

        self::assertTrue(
            $model->validate(),
            'Validation passes for an active user (enumeration-safe).',
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertFalse(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "'SendEmail' returns 'false' for an active user under the inactive-only filter.",
        );
    }

    public function testSendEmailReturnsFalseWhenSaveFails(): void
    {
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test.test@example.com'];

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when user save fails.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }
    }

    public function testSendEmailToNonExistingInactiveUser(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->email = 'nonexistent@example.com';

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertFalse(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "SendEmail returns 'false' when inactive user is not found.",
        );
    }

    public function testStaleTokenDetectedBeforeSend(): void
    {
        $sentinelToken = 'concurrent_overwrite_sentinel_token_value';

        $handler = static function (Event $event) use ($sentinelToken): void {
            /** @var User $sender */
            $sender = $event->sender;

            User::updateAll(
                ['verification_token' => $sentinelToken],
                ['id' => $sender->id],
            );
        };

        Event::on(User::class, BaseActiveRecord::EVENT_AFTER_UPDATE, $handler);

        try {
            $model = new ResendVerificationEmailForm();

            $model->attributes = ['email' => 'test.test@example.com'];

            $supportEmail = Yii::$app->params['supportEmail'];

            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when the token was overwritten before send.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_AFTER_UPDATE, $handler);
        }

        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertSame(
            $sentinelToken,
            $user->verification_token,
            'Concurrent overwrite was persisted in DB.',
        );
        self::assertSame(
            [],
            $this->tester?->grabSentEmails() ?? [],
            'No email was dispatched when a stale token was detected.',
        );
    }

    public function testSuccessfullyResend(): void
    {
        $userBefore = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $userBefore,
            "Fixture user 'test.test' exists before resend.",
        );
        self::assertNotNull(
            $userBefore->verification_token,
            "Fixture user 'test.test' has an initial verification token.",
        );

        $oldToken = $userBefore->verification_token;

        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test.test@example.com'];

        self::assertTrue(
            $model->validate(),
            'Validation passes for an inactive user email.',
        );
        self::assertFalse(
            $model->hasErrors(),
            'No validation errors are present.',
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertTrue(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            'Verification email is resent successfully.',
        );
        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $this->tester->seeEmailIsSent();

        /** @var MessageInterface $mail */
        $mail = $this->tester->grabLastSentEmail();

        self::assertInstanceOf(
            MessageInterface::class,
            $mail,
            'A verification email was captured.',
        );

        $to = $mail->getTo();
        $from = $mail->getFrom();

        self::assertIsArray(
            $to,
            "Email 'To' must be an array of recipients.",
        );
        self::assertIsArray(
            $from,
            "Email 'From' must be an array of senders.",
        );
        self::assertArrayHasKey(
            'test.test@example.com',
            $to,
            'Email is sent to the inactive user.',
        );
        self::assertArrayHasKey(
            $supportEmail,
            $from,
            "Email is sent 'from' the support address.",
        );
        self::assertSame(
            'Account registration at ' . Yii::$app->name,
            $mail->getSubject(),
            "Email 'subject' matches the registration template.",
        );

        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            "Fixture user 'test.test' has a verification token.",
        );
        self::assertNotSame(
            $oldToken,
            $user->verification_token,
            'Resend regenerates the verification token.',
        );

        /** @var \yii\symfonymailer\Message $mail */
        $textBody = $mail->getSymfonyEmail()->getTextBody();

        self::assertIsString(
            $textBody,
            'Email text body must be a string.',
        );
        self::assertStringContainsString(
            $user->verification_token,
            $textBody,
            "Email 'body' contains the verification 'token'.",
        );
    }

    public function testThrowRuntimeExceptionWhenMailerFailsDuringSendEmail(): void
    {
        $handler = static function (): void {
            throw new RuntimeException('Mailer transport failure');
        };

        Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test.test@example.com'];

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when mailer throws exception.",
            );
        } finally {
            Yii::$app->mailer->off(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            'Verification token is preserved after mailer exception.',
        );
    }

    public function testTokenPersistedWhenMailerSendReturnsFalse(): void
    {
        $fixtureUser = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $fixtureUser,
            "Fixture user 'test.test' exists.",
        );

        $originalToken = $fixtureUser->verification_token;

        $handler = static function (\yii\mail\MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test.test@example.com'];

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when mailer send returns 'false'.",
            );
        } finally {
            Yii::$app->mailer->off(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            'Verification token is preserved when mailer send returns false.',
        );
        self::assertNotSame(
            $originalToken,
            $user->verification_token,
            'Verification token was regenerated and committed before mailer failure.',
        );
    }

    public function testUnknownEmailAddressValidatesAndSendEmailReturnsFalse(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'aaa@bbb.cc'];

        self::assertTrue(
            $model->validate(),
            'Validation passes for an unknown email (enumeration-safe).',
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertFalse(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "'SendEmail' returns 'false' for an unknown email address.",
        );
    }

    protected function _before(): void
    {
        Yii::$app->cache->flush();
    }
}
