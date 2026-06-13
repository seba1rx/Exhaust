<?php

declare(strict_types=1);

namespace Exhaust\Mailer;

use Exhaust\Mailer\MailerWrapper;
use PHPMailer\PHPMailer\Exception as MailerException;


class ExhaustMailer
{
    /**
     * sends the account verification email to the registering user
     *
     * @param array $args
     * @return
     */
    public static function sendValidationEmail(array $args): void
    {
        try{
            $mailer = new MailerWrapper();
            $mailer
            ->setSMTPServer('mail.engine.com')
            ->setCredetials('user@engine.com', 'secret')
            ->isHTML(true)
            ->setFrom('noreply', 'system@engine.com')

            ->addRecipient($args['alias'], $args['email'])
            ->setSubject('Exhaust: account verification')
            ->setBody('body html')
            ->send();

            $sentStatus = $mailer->getSentStatus();

        }catch(MailerException $e){
            error_log($e->errorMessage());
        }catch(\Exception $e){
            error_log($e->getMessage());
        }
    }



}