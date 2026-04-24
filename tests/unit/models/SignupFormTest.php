<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\{SignupForm, User};
use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use RuntimeException;
use Yii;
use yii\base\{Event, ModelEvent};
use yii\db\BaseActiveRecord;
use yii\mail\{BaseMailer, MailEvent, MessageInterface};
use yii\symfonymailer\Message;

/**
 * Unit tests for {@see \app\models\SignupForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class SignupFormTest extends \Codeception\Test\Unit
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

    public function testCorrectSignup(): void
    {
        $model = new SignupForm(
            [
                'username' => 'some_username',
                'email' => 'some_email@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        $user = $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name);

        self::assertTrue(
            $user,
            "Signup returns 'true' on success.",
        );

        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $user = $this->tester->grabRecord(
            User::class,
            [
                'username' => 'some_username',
                'email' => 'some_email@example.com',
                'status' => User::STATUS_INACTIVE,
            ],
        );

        self::assertInstanceOf(
            User::class,
            $user,
            "Signup persisted an 'inactive' user.",
        );
        self::assertNotNull(
            $user->verification_token,
            'Persisted user has a verification token.',
        );
        self::assertNotEmpty(
            $user->verification_token,
            "Persisted user verification 'token' is not empty.",
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
            'A verification email was sent.',
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
            'some_email@example.com',
            $to,
            'Email is sent to the registered address.',
        );
        self::assertArrayHasKey(
            $supportEmail,
            $from,
            'Email is sent from the support address.',
        );
        self::assertSame(
            'Account registration at ' . Yii::$app->name,
            $mail->getSubject(),
            'Email subject matches the registration template.',
        );

        /** @var Message $mail */
        $textBody = $mail->getSymfonyEmail()->getTextBody();

        self::assertIsString(
            $textBody,
            'Email text body must be a string.',
        );
        self::assertStringContainsString(
            $user->verification_token,
            $textBody,
            'Email body contains the verification token.',
        );
    }

    public function testNotCorrectSignup(): void
    {
        $model = new SignupForm(
            [
                'username' => 'troy.becker',
                'email' => 'troy.becker@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        self::assertNull(
            $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name),
            "Validation failure returns 'null'.",
        );
        self::assertNotEmpty(
            $model->getErrors('username'),
            'A username validation error is present.',
        );
        self::assertNotEmpty(
            $model->getErrors('email'),
            'An email validation error is present.',
        );
        self::assertSame(
            'This username has already been taken.',
            $model->getFirstError('username'),
            'Username uniqueness error message is correct.',
        );
        self::assertSame(
            'This email address has already been taken.',
            $model->getFirstError('email'),
            'Email uniqueness error message is correct.',
        );
    }

    public function testSignupReturnsFalseWhenSaveFails(): void
    {
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_INSERT, $handler);

        $model = new SignupForm(
            [
                'username' => 'save_fail_user',
                'email' => 'save_fail@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "Signup returns 'false' when user save fails.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertNull(
            User::findOne(['username' => 'save_fail_user']),
            'User was not persisted after rollback.',
        );
    }

    public function testSignupReturnsFalseWhenSendEmailFails(): void
    {
        $handler = static function (MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new SignupForm(
            [
                'username' => 'email_fail_user',
                'email' => 'email_fail@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "Signup returns 'false' when email sending fails.",
            );
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotNull(
            User::findOne(['username' => 'email_fail_user']),
            'User was persisted even after email failure (email is sent outside the DB transaction).',
        );
    }

    public function testSignupRollsBackTransactionWhenUserSaveThrows(): void
    {
        $handler = static function (): void {
            throw new RuntimeException('Database failure during user save');
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_INSERT, $handler);

        $model = new SignupForm(
            [
                'username' => 'rollback_user',
                'email' => 'rollback@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "Signup returns 'false' when the user save throws inside the transaction.",
            );
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertNull(
            User::findOne(['username' => 'rollback_user']),
            'User row was rolled back when an exception was thrown before the transaction commit.',
        );
    }

    public function testThrowRuntimeExceptionWhenMailerFailsDuringSignup(): void
    {
        $handler = static function (): void {
            throw new RuntimeException('Mailer transport failure');
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        $model = new SignupForm(
            [
                'username' => 'exception_user',
                'email' => 'exception@example.com',
                'password' => 'some_password',
            ],
        );

        $supportEmail = Yii::$app->params['supportEmail'];

        try {
            self::assertFalse(
                $model->signup(Yii::$app->mailer, $supportEmail, Yii::$app->name),
                "Signup returns 'false' when mailer throws exception.",
            );
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotNull(
            User::findOne(['username' => 'exception_user']),
            'User was persisted even after mailer exception (email is sent outside the DB transaction).',
        );
    }
}
