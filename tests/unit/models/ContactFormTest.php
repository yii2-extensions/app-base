<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\ContactForm;
use app\tests\support\UnitTester;
use Yii;
use yii\symfonymailer\Message;

/**
 * Unit tests for {@see \app\models\ContactForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ContactFormTest extends \Codeception\Test\Unit
{
    public UnitTester|null $tester = null;

    public function testEmailIsSentOnContact(): void
    {
        $model = new ContactForm();

        $model->attributes = [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'phone' => '(555) 123-4567',
            'subject' => 'very important letter subject',
            'body' => 'body of current message',
            'turnstileToken' => 'test-token',
        ];

        self::assertNotEmpty(
            $model->contact(
                Yii::$app->mailer,
                'admin@example.com',
                'noreply@example.com',
                'Example.com mailer',
            ),
            'Contact email is sent successfully.',
        );

        // using Yii2 module actions to check email was sent.
        self::assertNotNull(
            $this->tester,
            'UnitTester must be configured for email assertions.',
        );

        $this->tester->seeEmailIsSent();

        $emailMessage = $this->tester->grabLastSentEmail();

        self::assertInstanceOf(
            Message::class,
            $emailMessage,
            'Mailer must produce a Symfony Message to inspect the contact email.',
        );

        $to = $emailMessage->getTo();
        $from = $emailMessage->getFrom();
        $replyTo = $emailMessage->getReplyTo();

        self::assertIsArray(
            $to,
            "Email 'To' must be an array of recipients.",
        );
        self::assertIsArray(
            $from,
            "Email 'From' must be an array of senders.",
        );
        self::assertIsArray(
            $replyTo,
            "Email 'Reply-To' must be an array of addresses.",
        );
        self::assertArrayHasKey(
            'admin@example.com',
            $to,
            'Email is sent to the admin address.',
        );
        self::assertArrayHasKey(
            'noreply@example.com',
            $from,
            "Email is sent from the 'noreply' address.",
        );
        self::assertArrayHasKey(
            'tester@example.com',
            $replyTo,
            "'Reply-to' is set to the contact email.",
        );
        self::assertSame(
            'very important letter subject',
            $emailMessage->getSubject(),
            "Email 'subject' matches the form input.",
        );

        $textBody = $emailMessage->getSymfonyEmail()->getTextBody();

        self::assertIsString(
            $textBody,
            'Email text body must be a string.',
        );
        self::assertStringContainsString(
            'body of current message',
            $textBody,
            "Email 'body' contains the form message.",
        );
        self::assertStringContainsString(
            '(555) 123-4567',
            $textBody,
            "Email 'body' contains the phone number.",
        );
    }
}
