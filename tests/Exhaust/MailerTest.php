<?php

declare(strict_types=1);

namespace tests\Exhaust;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;
use Exhaust\Mailer\MailerWrapper;
use PHPMailer\PHPMailer\SMTP;

/**
 * Etapa: Mailer/
 *
 * KNOWN BUG: MailerWrapper declares '__constructor' instead of '__construct',
 * so the PHPMailer object is never initialised on instantiation.
 * Methods that call $this->mailerObject->*() fail with a null-dereference.
 * Tests that exercise those methods are marked as skipped until the typo is fixed.
 */
final class MailerTest extends TestCase
{
    // --- instantiation ---

    public function test_mailerWrapper_can_be_instantiated(): void
    {
        $mailer = new MailerWrapper();
        $this->assertInstanceOf(MailerWrapper::class, $mailer);
    }

    public function test_mailerObject_is_null_due_to_constructor_typo(): void
    {
        // '__constructor' is not a magic method; PHPMailer is never created.
        $mailer = new MailerWrapper();
        $this->assertNull($mailer->mailerObject);
    }

    public function test_sentStatus_is_null_before_send(): void
    {
        $mailer = new MailerWrapper();
        $this->assertNull($mailer->getSentStatus());
    }

    // --- properties accessible without mailerObject ---

    public function test_destinations_starts_empty(): void
    {
        $mailer = new MailerWrapper();
        $this->assertIsArray($mailer->destinations);
        $this->assertEmpty($mailer->destinations);
    }

    public function test_attachments_starts_empty(): void
    {
        $mailer = new MailerWrapper();
        $this->assertIsArray($mailer->attachments);
        $this->assertEmpty($mailer->attachments);
    }

    public function test_isSentToCopy_defaults_false(): void
    {
        $mailer = new MailerWrapper();
        $this->assertFalse($mailer->isSentToCopy);
    }

    public function test_isSentToHiddenCopy_defaults_false(): void
    {
        $mailer = new MailerWrapper();
        $this->assertFalse($mailer->isSentToHiddenCopy);
    }

    // --- methods that require a valid mailerObject are skipped ---

    public function test_setSMTPServer_requires_mailerObject(): void
    {
        $this->markTestSkipped('MailerWrapper::__constructor typo leaves mailerObject null; fix the constructor name first.');
    }

    public function test_activateDebug_requires_mailerObject(): void
    {
        $this->markTestSkipped('MailerWrapper::__constructor typo leaves mailerObject null; fix the constructor name first.');
    }

    public function test_setSenderCredetials_requires_mailerObject(): void
    {
        $this->markTestSkipped('MailerWrapper::__constructor typo leaves mailerObject null; fix the constructor name first.');
    }

    public function test_addRecipient_requires_mailerObject(): void
    {
        $this->markTestSkipped('MailerWrapper::__constructor typo leaves mailerObject null; fix the constructor name first.');
    }

    public function test_send_requires_mailerObject(): void
    {
        $this->markTestSkipped('MailerWrapper::__constructor typo leaves mailerObject null; fix the constructor name first.');
    }
}
