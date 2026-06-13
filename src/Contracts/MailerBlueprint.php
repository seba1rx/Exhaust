<?php

namespace Exhaust\Contracts;

/**
 * This interface is a blueprint to implement Mailer handling.
 * Use whatever PHP mailer library resource to handle mailing
 */
interface MailerBlueprint
{

    /**
     * Array containing all the recipients the email will be sent to
     * @var array [[recipient => value, ?alias => value]]
     */
    public array $recipients{ get; }

    /**
     * Array containing all the CC recipients the email will be sent to
     * @var array [[recipient => value, ?alias => value]]
     */
    public array $recipients_copy { get; }

    /**
     * Array containing all the BCC recipients the email will be sent to
     * @var array [[recipient => value, ?alias => value]]
     */
    public array $recipients_hidden { get; }

    /**
     * List of attatchments in the mail to be sent
     * @var array [[filePath => value, fileName => value]]
     */
    public array $attachments { get; }

    /**
     * The sent status, set when the email is sent
     *
     *@var bool
     */
    public $sentStatus { get; }


    /**
     * Loads to the mailer object properties the configuration default values.
     * Loas the implementing properties default values.
     * The configuration should be defined in the conf.php file
     *
     * @param array $mailer_conf
     * @return void
     */
    public function loadDefaults(array $mailer_conf): void;

    /**
     * Sets the SMTP server
     *
     * @param string $SMTPServer - example: 'demo.system_domain.com' or ip
     */
    public function setSMTPServer(string $SMTPServer): self;

    /**
     * Sets the SMTP sender credentials
     *
     * @param string $userEmail - example: 'contact@system_domain.com'
     * @param string $emailPassword
     * @param ?string|null $alias - example: 'Domain contact'
     */
    public function setSenderCredetials(string $userEmail, string $emailPassword, ?string $alias = null): self;

    /**
     * Sets the email sender using the userEmail and alias properties of the mailer object
     */
    public function setFrom(): self;

    /**
     * Adds to the mailer object the recipients that the mailer will email to.
     * Should fill the recipients property as [[alias => recipient]] for convenience
     *
     * @param string $recipient
     * @param ?string $alias
     */
    public function addRecipient(string $recipient, ?string $alias = null);

    /**
     * Adds to the mailer object the 'reply to' data
     *
     * @param string $emailAccount
     * @param string $alias
     */
    public function addReplyTo(string $emailAccount, ?string $alias = null);

    /**
     * Adds to the mailer object a CC recipient
     * Should fill the recipients_copy property as [[alias => recipient]] for convenience
     *
     * @param string $ccRecipient
     * @param ?string $ccAlias
     * @return MailerBlueprint
     */
    public function addCCRecipient(string $ccRecipient, ?string $ccAlias = null);

    /**
     * Adds to the mailer object a BCC recipient.
     * Should fill the recipients_hidden property as [[recipient => value, ?alias => value]] for convenience
     *
     * @param string $bccRecipient
     * @param ?string $bccAlias
     */
    public function addBCCRecipient(string $bccRecipient, ?string $bccAlias = null);

    /**
     * Adds to the attachments property the files to be attatched in the email to be sent
     * Should fill the attachments property as [[filePath => value, fileName => value]]
     *
     * @param string $filePath
     * @param string $fileName
     */
    public function addAttachment(string $filePath, string $fileName);

    /**
     * Sets in the mailer object the property that indicates if the mail is an HTML mail
     *
     * @param bool $is_html = true
     */
    public function defineMailAsHTML(bool $is_html = true);

    /**
     * Sets the subject property in the mailer object
     *
     * @param string $subject
     */
    public function setSubject(string $subject);

    /**
     * Sets the body property in the mailer object
     *
     * @param string $body
     */
    public function setBody(string $body);

    /**
     * Sets a body in plain text in the mailer object for non-html email clients
     *
     * @param string $altBody
     */
    public function setAltBody(string $altBody);

    /**
     * Sends the email.
     * Should set the sentStatus property.
     */
    public function send();
}