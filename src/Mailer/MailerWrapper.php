<?php

declare(strict_types=1);

namespace Exhaust\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Exhaust\Contracts\MailerBlueprint;

/**
 * https://github.com/PHPMailer/PHPMailer
 *
 * This class is a wrapper for the PHPMailer package
 *
 * intended way of use:
 *
 * use PHPMailer\PHPMailer\Exception as MailerException;
 *
 * try{
 *     $mailer = new MailerWrapper();
 *     $mailer
 *     ->setSMTPServer('mail.engine.com')
 *     ->setCredetials('user@engine.com', 'secret')
 *     ->isHTML(true)
 *     ->setFrom('user alias', 'user@engine.com')
 *     ->addReplyTo('other_alias', 'some_other_user@gmail.com')
 *     ->addRecipient('alias', 'some_user@gmail.com')
 *     ->addRecipient('other_alias', 'some_other_user@gmail.com')
 *     ->addReplyTo('alias', 'name@gmail.com')
 *     ->addCC('name@gmail.com')
 *     ->addBCC('name@gmail.com')
 *     ->addAttachment('path/to/file', 'file name')
 *     ->setSubject('subject text')
 *     ->setBody('body html')
 *     ->send();
 *
 *     $sentStatus = $mailer->getSentStatus();
 *
 * }catch(MailerException $e){
 *     // PHPMailer exception
 *     error_log($e->errorMessage());
 * }catch(\Exception $e){
 *     // \Exception
 *     error_log($e->getMessage());
 * }
 *
 */
class MailerWrapper
{
    public $mailerObject = null;
    public $destinations = [];
    public $isSentToCopy = false;
    public $isSentToHiddenCopy = false;
    public $copyDestinations = [];
    public $hiddenCopyDestinations = [];
    public $attachments = [];
    public $sentStatus;

    public function __constructor(array $config = [])
    {
        $this->mailerObject = new PHPMailer(true);

        if(isset($config['isSMTP'])){
            if($config['isSMTP']){
                ## solo si config seteado como true
                $this->mailerObject->isSMTP();
            }
        }else{
            ## por defecto si no se especifica en config
            $this->mailerObject->isSMTP();
        }

        if(isset($config['SMTPAuth'])){
            $this->mailerObject->SMTPAuth = $config['SMTPAuth'];
        }else{
            ## por defecto true si no especificado en config
            $this->mailerObject->SMTPAuth = true;
        }

        if(isset($config['SMTPSecure']) && $config['SMTPSecure']){
            $this->mailerObject->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailerObject->Port = 587;
        }else{
            ## valores por defecto si no especificado en config
            $this->mailerObject->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailerObject->Port = 465;
        }

        // TODO
        // $appConf = require
        // if(isset($config['use_account'])){
        //     ## solo si config seteado como true
        //     $this->mailerObject->setSMTPServer();
        // }else{
        //     ## por defecto si no se especifica en config
        //     $this->mailerObject->setSMTPServer();
        // }
    }


    /**
     * dummy function to use it with phpunit
     *
     * @param string $msg
     */
    public function echoToCli(string $msg)
    {
        echo "echoing to CLI " . json_encode($this->mailerObject) . PHP_EOL;
        if(!empty($msg)){
            echo $msg . PHP_EOL;
        }
    }

    public function activateDebug(): MailerWrapper
    {
        $this->mailerObject->SMTPDebug = SMTP::DEBUG_SERVER;
        return $this;
    }

    /**
     * set the SMTP server to send through
     *
     * @param string $SMTPServer (example.system_domain.com or ip)
     * @return MailerWrapper
     */
    public function setSMTPServer(string $SMTPServer): MailerWrapper
    {
        $this->mailerObject->Host = $SMTPServer;
        return $this;
    }

    /**
     * Set the SMTP sender credentials
     *
     * @param string $userEmail (contact@system_domain.com)
     * @param string $emailPassword
     * @param ?string|null $alias
     * @return MailerWrapper
     */
    public function setSenderCredetials(string $userEmail, string $emailPassword, ?string $alias = null): MailerWrapper
    {
        $this->mailerObject->Username = $userEmail;
        $this->mailerObject->Password = $emailPassword;

        return $this;
    }

    public function setFrom(string $alias, string $emailAccount): MailerWrapper
    {
        $this->mailerObject->setFrom($emailAccount, $alias);

        return $this;
    }

    public function addRecipient(string $alias, string $recipient): MailerWrapper
    {
        $this->destinations[] = [$alias, $recipient];

        $this->mailerObject->addAddress($alias, $recipient);

        return $this;
    }

    public function addReplyTo($alias, $emailAccount): MailerWrapper
    {
        $this->mailerObject->addReplyTo($emailAccount, $alias);

        return $this;
    }

    public function addCC($emailAccount): MailerWrapper
    {
        $this->isSentToCopy = true;
        $this->copyDestinations[] = $emailAccount;

        $this->mailerObject->addCC($emailAccount);

        return $this;
    }

    public function addBCC($emailAccount): MailerWrapper
    {
        $this->isSentToHiddenCopy = true;
        $this->hiddenCopyDestinations[] = $emailAccount;

        $this->mailerObject->addBCC($emailAccount);

        return $this;
    }

    public function addAttachment(string $filePath, string $fileName): MailerWrapper
    {
        $this->attachments[] = ['filePath' => $filePath, 'fileName' => $fileName];

        $this->mailerObject->addAttachment($filePath, $fileName);

        return $this;
    }

    public function isHTML(bool $isHTML = true): MailerWrapper
    {
        $this->mailerObject->isHTML($isHTML);

        return $this;
    }

    public function setSubject(string $subject): MailerWrapper
    {
        $this->mailerObject->Subject($subject);

        return $this;
    }

    public function setBody(string $body): MailerWrapper
    {
        $this->mailerObject->Body($body);

        return $this;
    }

    /**
     * Set a body in plain text for non-html cemail clients
     *
     * @param string $altBody
     */
    public function setAltBody(string $altBody): MailerWrapper
    {
        $this->mailerObject->AltBody($altBody);

        return $this;
    }

    public function send(): MailerWrapper
    {
        $this->sentStatus = $this->mailerObject->send();

        return $this;
    }

    public function getSentStatus()
    {
        return $this->sentStatus;
    }

}