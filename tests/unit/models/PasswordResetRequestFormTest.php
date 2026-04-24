<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\{PasswordResetRequestForm, User};
use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use RuntimeException;
use Yii;
use yii\base\{Event, ModelEvent};
use yii\db\BaseActiveRecord;
use yii\mail\{BaseMailer, MailEvent};
use yii\symfonymailer\Message;

/**
 * Unit tests for {@see \app\models\PasswordResetRequestForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class PasswordResetRequestFormTest extends \Codeception\Test\Unit
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

    public function testNotSendEmailsToInactiveUser(): void
    {
        $model = new PasswordResetRequestForm();

        $model->email = 'troy.becker@example.com';

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertFalse(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "SendEmail returns 'false' for an inactive user.",
        );
    }

    public function testReturnsFalseWhenMailerSendThrows(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        // okirlin has a valid (non-expired) token — token-regeneration block is skipped ($transaction=null).
        $handler = static function (): void {
            throw new RuntimeException('Simulated mailer transport failure.');
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when mailer throws with no active transaction.",
            );
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }
    }

    public function testReturnsFalseWhenMailerSendThrowsWithActiveTransaction(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        /**
         * Set expired token so the `token-regeneration` block is entered and a transaction is started. Save succeeds
         * (no `EVENT_BEFORE_UPDATE` blocker), so `$transaction` is non-null and active when the mailer throws covering
         * `rollBack()` inside the second catch block.
         */
        $user->password_reset_token = 'expiredtoken_1000000000';

        self::assertTrue(
            $user->save(false),
            'Expired token was persisted.',
        );

        $handler = static function (): void {
            throw new RuntimeException('Simulated mailer failure with active transaction.');
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when mailer throws with an active transaction.",
            );
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }
    }

    public function testReturnsFalseWhenUserSaveThrowsDuringTokenRegeneration(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        // set expired token so the token-regeneration block (`$user->save()`) is entered.
        $user->password_reset_token = 'expiredtoken_1000000000';

        self::assertTrue(
            $user->save(false),
            'Expired token was persisted.',
        );

        $handler = static function (): void {
            throw new RuntimeException('Simulated DB failure during token regeneration.');
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when user save throws during token regeneration.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }
    }

    public function testSendEmailRegeneratesExpiredToken(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        // set an expired token (timestamp far in the past).
        $user->password_reset_token = 'expiredtoken_1000000000';

        self::assertTrue(
            $user->save(false),
            'Expired token was persisted.',
        );

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertNotEmpty(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            'Email is sent after regenerating expired token.',
        );

        $user->refresh();

        self::assertNotSame(
            'expiredtoken_1000000000',
            $user->password_reset_token,
            'Expired token was replaced with a new one.',
        );
    }

    public function testSendEmailReturnsFalseWhenSaveFails(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        // set an expired token so `generatePasswordResetToken()` + `save()` path is triggered.
        $user->password_reset_token = 'expiredtoken_1000000000';

        self::assertTrue(
            $user->save(false),
            'Expired token was persisted.',
        );

        // force `save()` to fail via `EVENT_BEFORE_SAVE` at the class level.
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

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

    public function testSendEmailRollsBackRegeneratedTokenWhenMailerFails(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        $user->password_reset_token = 'expiredtoken_1000000000';

        self::assertTrue(
            $user->save(false),
            'Expired token was persisted.',
        );

        $handler = static function (MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "SendEmail returns 'false' when mail sending fails.",
            );
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        $user->refresh();

        self::assertSame(
            'expiredtoken_1000000000',
            $user->password_reset_token,
            'Regenerated token was rolled back when email sending fails.',
        );
    }

    public function testSendEmailSuccessfully(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        $model = new PasswordResetRequestForm();

        $model->email = $user->email;

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertNotEmpty(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            'Password reset email is sent successfully.',
        );

        $user->refresh();

        self::assertNotEmpty(
            $user->password_reset_token,
            'User has a password reset token after sending.',
        );

        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $emailMessage = $this->tester->grabLastSentEmail();

        self::assertInstanceOf(
            Message::class,
            $emailMessage,
            'Mailer must produce a Symfony Message to inspect the reset email.',
        );

        $to = $emailMessage->getTo();
        $from = $emailMessage->getFrom();

        self::assertIsArray(
            $to,
            "Email 'To' must be an array of recipients.",
        );
        self::assertIsArray(
            $from,
            "Email 'From' must be an array of senders.",
        );
        self::assertArrayHasKey(
            $model->email,
            $to,
            'Email is sent to the requested address.',
        );
        self::assertArrayHasKey(
            $supportEmail,
            $from,
            'Email is sent from the support address.',
        );

        $body = $emailMessage->getSymfonyEmail()->getHtmlBody() . $emailMessage->getSymfonyEmail()->getTextBody();

        self::assertNotNull(
            $user->password_reset_token,
            'User has a password reset token.',
        );

        $token = $user->password_reset_token;

        self::assertStringContainsString(
            $token,
            $body,
            'Email body contains the password reset token.',
        );
    }

    public function testSendMessageWithWrongEmailAddress(): void
    {
        $model = new PasswordResetRequestForm();

        $model->email = 'not-existing-email@example.com';

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertFalse(
            $model->sendEmail(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "SendEmail returns 'false' for a non-existing email address.",
        );
    }
}
