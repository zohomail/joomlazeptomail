<?php
namespace Joomla\CMS\Mail;
defined('JPATH_PLATFORM') or die;
/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use PHPMailer\PHPMailer\Exception as phpmailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Joomla\CMS\Plugin;
use Joomla\Registry\Registry;
require JPATH_LIBRARIES .'/vendor/autoload.php';
// phpcs:disable PSR1.Files.SideEffects

// phpcs:enable PSR1.Files.SideEffects

/**
 * Email Class.  Provides a common interface to send email from the Joomla! Platform
 *
 * @since  1.7.0
 */
class Mail extends PHPMailer
{
    /**
     * Mail instances container.
     *
     * @var    Mail[]
     * @since  1.7.3
     */
    protected static $instances = array();
	
	


    /**
     * Charset of the message.
     *
     * @var    string
     * @since  1.7.0
     */
    public $CharSet = 'utf-8';

    /**
     * Constructor
     *
     * @param   boolean  $exceptions  Flag if Exceptions should be thrown
     *
     * @since   1.7.0
     */
    public function __construct($exceptions = true)
    {
        parent::__construct($exceptions);

        // PHPMailer has an issue using the relative path for its language files
        $this->setLanguage('en_gb', __DIR__ . '/language/');

        // Configure a callback function to handle errors when $this->debug() is called
        $this->Debugoutput = function ($message, $level) {
            Log::add(sprintf('Error in Mail API: %s', $message), Log::ERROR, 'mail');
        };

        // If debug mode is enabled then set SMTPDebug to the maximum level
        if (\defined('JDEBUG') && JDEBUG) {
            $this->SMTPDebug = 4;
        }
		
        // Don't disclose the PHPMailer version
        $this->XMailer = ' ';
		$this->sendmailconfig='';
	    $this->resp = '';
		$this->pluginParams = \JComponentHelper::getParams('com_zohozepto');
		

        /**
         * Which validator to use by default when validating email addresses.
         * Validation patterns supported:
         * `auto` Pick best pattern automatically;
         * `pcre8` Use the squiloople.com pattern, requires PCRE > 8.0;
         * `pcre` Use old PCRE implementation;
         * `php` Use PHP built-in FILTER_VALIDATE_EMAIL;
         * `html5` Use the pattern given by the HTML5 spec for 'email' type form input elements.
         * `noregex` Don't use a regex: super fast, really dumb.
         *
         * The default used by phpmailer is `php` but this does not support dotless domains so instead we use `html5`
         *
         * @see PHPMailer::validateAddress()
         *
         * @var string|callable
         */
        PHPMailer::$validator = 'html5';
    }

    /**
     * Returns the global email object, only creating it if it doesn't already exist.
     *
     * NOTE: If you need an instance to use that does not have the global configuration
     * values, use an id string that is not 'Joomla'.
     *
     * @param   string   $id          The id string for the Mail instance [optional]
     * @param   boolean  $exceptions  Flag if Exceptions should be thrown [optional]
     *
     * @return  Mail  The global Mail object
     *
     * @since   1.7.0
     */
    public static function getInstance($id = 'Joomla', $exceptions = true)
    {
        if (empty(static::$instances[$id])) {
            static::$instances[$id] = new static($exceptions);
        }

        return static::$instances[$id];
    }

    /**
     * Send the mail
     *
     * @return  boolean  Boolean true if successful, false if exception throwing is disabled.
     *
     * @since   1.7.0
     *
     * @throws  MailDisabledException  if the mail function is disabled
     * @throws  phpmailerException     if sending failed
     */
    public function Send()
    {

        if (!Factory::getApplication()->get('mailonline', 1)) {
            throw new MailDisabledException(
                MailDisabledException::REASON_USER_DISABLED,
                Text::_('JLIB_MAIL_FUNCTION_OFFLINE'),
                500
            );
        }
        if(!empty($this->pluginParams->get('zeptomail_send_mail_token'))|| !empty($this->sendmailconfig)) {
            return $this->sendZeptoMail();
        }

        if ($this->Mailer === 'mail' && !\function_exists('mail')) {
            throw new MailDisabledException(
                MailDisabledException::REASON_MAIL_FUNCTION_NOT_AVAILABLE,
                Text::_('JLIB_MAIL_FUNCTION_DISABLED'),
                500
            );
        }

        try {
            $result = parent::send();
        } catch (phpmailerException $e) {
            // If auto TLS is disabled just let this bubble up
            if (!$this->SMTPAutoTLS) {
                throw $e;
            }

            $result = false;
        }

        /*
         * If sending failed and auto TLS is enabled, retry sending with the feature disabled
         *
         * See https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#opportunistic-tls for more info
         */
        if (!$result && $this->SMTPAutoTLS) {
            $this->SMTPAutoTLS = false;

            try {
                $result = parent::send();
            } finally {
                // Reset the value for any future emails
                $this->SMTPAutoTLS = true;
            }
        }

        return $result;
    }

    /**
     * Set the email sender
     *
     * @param   mixed  $from  email address and Name of sender
     *                        <code>array([0] => email Address, [1] => Name)</code>
     *                        or as a string
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure.
     *
     * @since   1.7.0
     *
     * @throws  \UnexpectedValueException  if the sender is not a valid address
     * @throws  phpmailerException          if setting the sender failed and exception throwing is enabled
     */
    public function setSender($from)
    {
        if (\is_array($from)) {
            // If $from is an array we assume it has an address and a name
            if (isset($from[2])) {
                // If it is an array with entries, use them
                $result = $this->setFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]), (bool) $from[2]);
            } else {
                $result = $this->setFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]));
            }
        } elseif (\is_string($from)) {
            // If it is a string we assume it is just the address
            $result = $this->setFrom(MailHelper::cleanLine($from));
        } else {
            // If it is neither, we log a message and throw an exception
            Log::add(Text::sprintf('JLIB_MAIL_INVALID_EMAIL_SENDER', $from), Log::WARNING, 'jerror');

            throw new \UnexpectedValueException(sprintf('Invalid email sender: %s', $from));
        }

        if ($result === false) {
            return false;
        }

        return $this;
    }
	

    /**
     * Set the email subject
     *
     * @param   string  $subject  Subject of the email
     *
     * @return  Mail  Returns this object for chaining.
     *
     * @since   1.7.0
     */
    public function setSubject($subject)
    {
        $this->Subject = MailHelper::cleanLine($subject);

        return $this;
    }

    /**
     * Set the email body
     *
     * @param   string  $content  Body of the email
     *
     * @return  Mail  Returns this object for chaining.
     *
     * @since   1.7.0
     */
    public function setBody($content)
    {
        /*
         * Filter the Body
         * @todo: Check for XSS
         */
        $this->Body = MailHelper::cleanText($content);

        return $this;
    }

    /**
     * Add recipients to the email.
     *
     * @param   mixed   $recipient  Either a string or array of strings [email address(es)]
     * @param   mixed   $name       Either a string or array of strings [name(s)]
     * @param   string  $method     The parent method's name.
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure.
     *
     * @since   1.7.0
     *
     * @throws  \InvalidArgumentException if the argument array counts do not match
     * @throws  phpmailerException  if setting the address failed and exception throwing is enabled
     */
    protected function add($recipient, $name = '', $method = 'addAddress')
    {
        $method = lcfirst($method);

        // If the recipient is an array, add each recipient... otherwise just add the one
        if (\is_array($recipient)) {
            if (\is_array($name)) {
                $combined = array_combine($recipient, $name);

                if ($combined === false) {
                    throw new \InvalidArgumentException("The number of elements for each array isn't equal.");
                }

                foreach ($combined as $recipientEmail => $recipientName) {
                    $recipientEmail = MailHelper::cleanLine($recipientEmail);
                    $recipientName = MailHelper::cleanLine($recipientName);

                    // Check for boolean false return if exception handling is disabled
                    if (\call_user_func('parent::' . $method, $recipientEmail, $recipientName) === false) {
                        return false;
                    }
                }
            } else {
                $name = MailHelper::cleanLine($name);

                foreach ($recipient as $to) {
                    $to = MailHelper::cleanLine($to);

                    // Check for boolean false return if exception handling is disabled
                    if (\call_user_func('parent::' . $method, $to, $name) === false) {
                        return false;
                    }
                }
            }
        } else {
            $recipient = MailHelper::cleanLine($recipient);

            // Check for boolean false return if exception handling is disabled
            if (\call_user_func('parent::' . $method, $recipient, $name) === false) {
                return false;
            }
        }

        return $this;
    }

    /**
     * Add recipients to the email
     *
     * @param   mixed  $recipient  Either a string or array of strings [email address(es)]
     * @param   mixed  $name       Either a string or array of strings [name(s)]
     *
     * @return  Mail|boolean  Returns this object for chaining on success or false on failure when exception throwing is disabled.
     *
     * @since   1.7.0
     *
     * @throws  phpmailerException  if exception throwing is enabled
     */
    public function addRecipient($recipient, $name = '')
    {
        return $this->add($recipient, $name, 'addAddress');
    }

    /**
     * Add carbon copy recipients to the email
     *
     * @param   mixed  $cc    Either a string or array of strings [email address(es)]
     * @param   mixed  $name  Either a string or array of strings [name(s)]
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure when exception throwing is enabled.
     *
     * @since   1.7.0
     *
     * @throws  phpmailerException  if exception throwing is enabled
     */
    public function addCc($cc, $name = '')
    {
        // If the carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (isset($cc)) {
            return $this->add($cc, $name, 'addCC');
        }

        return $this;
    }

    /**
     * Add blind carbon copy recipients to the email
     *
     * @param   mixed  $bcc   Either a string or array of strings [email address(es)]
     * @param   mixed  $name  Either a string or array of strings [name(s)]
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure when exception throwing is disabled.
     *
     * @since   1.7.0
     *
     * @throws  phpmailerException  if exception throwing is enabled
     */
    public function addBcc($bcc, $name = '')
    {
        // If the blind carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (isset($bcc)) {
            return $this->add($bcc, $name, 'addBCC');
        }

        return $this;
    }

    /**
     * Add file attachment to the email
     *
     * @param   mixed   $path         Either a string or array of strings [filenames]
     * @param   mixed   $name         Either a string or array of strings [names]. N.B. if this is an array it must contain the same
     *                                number of elements as the array of paths supplied.
     * @param   mixed   $encoding     The encoding of the attachment
     * @param   mixed   $type         The mime type
     * @param   string  $disposition  The disposition of the attachment
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure when exception throwing is disabled.
     *
     * @since   3.0.1
     * @throws  \InvalidArgumentException  if the argument array counts do not match
     * @throws  phpmailerException          if setting the attachment failed and exception throwing is enabled
     */
    public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream', $disposition = 'attachment')
    {
        // If the file attachments is an array, add each file... otherwise just add the one
        if (isset($path)) {
            $result = true;

            if (\is_array($path)) {
                if (!empty($name) && \count($path) != \count($name)) {
                    throw new \InvalidArgumentException('The number of attachments must be equal with the number of name');
                }

                foreach ($path as $key => $file) {
                    if (!empty($name)) {
                        $result = parent::addAttachment($file, $name[$key], $encoding, $type);
                    } else {
                        if (!empty($name)) {
                            $result = parent::addAttachment($file, $name[$key], $encoding, $type, $disposition);
                        } else {
                            $result = parent::addAttachment($file, $name, $encoding, $type, $disposition);
                        }
                    }
                }

                // Check for boolean false return if exception handling is disabled
                if ($result === false) {
                    return false;
                }
            } else {
                $result = parent::addAttachment($path, $name, $encoding, $type);
            }

            // Check for boolean false return if exception handling is disabled
            if ($result === false) {
                return false;
            }
        }

        return $this;
    }

    /**
     * Unset all file attachments from the email
     *
     * @return  Mail  Returns this object for chaining.
     *
     * @since   3.0.1
     */
    public function clearAttachments()
    {
        parent::clearAttachments();

        return $this;
    }

    /**
     * Unset file attachments specified by array index.
     *
     * @param   integer  $index  The numerical index of the attachment to remove
     *
     * @return  Mail  Returns this object for chaining.
     *
     * @since   3.0.1
     */
    public function removeAttachment($index = 0)
    {
        if (isset($this->attachment[$index])) {
            unset($this->attachment[$index]);
        }

        return $this;
    }

    /**
     * Add Reply to email address(es) to the email
     *
     * @param   mixed  $replyto  Either a string or array of strings [email address(es)]
     * @param   mixed  $name     Either a string or array of strings [name(s)]
     *
     * @return  Mail|boolean  Returns this object for chaining on success or boolean false on failure when exception throwing is disabled.
     *
     * @since   1.7.0
     *
     * @throws  phpmailerException  if exception throwing is enabled
     */
    public function addReplyTo($replyto, $name = '')
    {
        return $this->add($replyto, $name, 'addReplyTo');
    }

    /**
     * Sets message type to HTML
     *
     * @param   boolean  $ishtml  Boolean true or false.
     *
     * @return  Mail  Returns this object for chaining.
     *
     * @since   3.1.4
     */
    public function isHtml($ishtml = true)
    {
        parent::isHTML($ishtml);

        return $this;
    }

    /**
     * Send messages using $Sendmail.
     *
     * This overrides the parent class to remove the restriction on the executable's name containing the word "sendmail"
     *
     * @return  void
     *
     * @since   1.7.0
     */
    public function isSendmail()
    {
        // Prefer the Joomla configured sendmail path and default to the configured PHP path otherwise
        $sendmail = Factory::getApplication()->get('sendmail', ini_get('sendmail_path'));

        // And if we still don't have a path, then use the system default for Linux
        if (empty($sendmail)) {
            $sendmail = '/usr/sbin/sendmail';
        }

        $this->Sendmail = $sendmail;
        $this->Mailer   = 'sendmail';
    }

    /**
     * Use sendmail for sending the email
     *
     * @param   string  $sendmail  Path to sendmail [optional]
     *
     * @return  boolean  True on success
     *
     * @since   1.7.0
     */
    public function useSendmail($sendmail = null)
    {
        $this->Sendmail = $sendmail;

        if (!empty($this->Sendmail)) {
            $this->isSendmail();

            return true;
        } else {
            $this->isMail();

            return false;
        }
    }

    /**
     * Use SMTP for sending the email
     *
     * @param   string   $auth    SMTP Authentication [optional]
     * @param   string   $host    SMTP Host [optional]
     * @param   string   $user    SMTP Username [optional]
     * @param   string   $pass    SMTP Password [optional]
     * @param   string   $secure  Use secure methods
     * @param   integer  $port    The SMTP port
     *
     * @return  boolean  True on success
     *
     * @since   1.7.0
     */
    public function useSmtp($auth = null, $host = null, $user = null, $pass = null, $secure = null, $port = 25)
    {
        $this->SMTPAuth = $auth;
        $this->Host = $host;
        $this->Username = $user;
        $this->Password = $pass;
        $this->Port = $port;

        if ($secure === 'ssl' || $secure === 'tls') {
            $this->SMTPSecure = $secure;
        }

        if (
            ($this->SMTPAuth !== null && $this->Host !== null && $this->Username !== null && $this->Password !== null)
            || ($this->SMTPAuth === null && $this->Host !== null)
        ) {
            $this->isSMTP();

            return true;
        } else {
            $this->isMail();

            return false;
        }
    }

    /**
     * Function to send an email
     *
     * @param   string   $from         From email address
     * @param   string   $fromName     From name
     * @param   mixed    $recipient    Recipient email address(es)
     * @param   string   $subject      email subject
     * @param   string   $body         Message body
     * @param   boolean  $mode         false = plain text, true = HTML
     * @param   mixed    $cc           CC email address(es)
     * @param   mixed    $bcc          BCC email address(es)
     * @param   mixed    $attachment   Attachment file name(s)
     * @param   mixed    $replyTo      Reply to email address(es)
     * @param   mixed    $replyToName  Reply to name(s)
     *
     * @return  boolean  True on success, false on failure when exception throwing is disabled.
     *
     * @since   1.7.0
     *
     * @throws  MailDisabledException  if the mail function is disabled
     * @throws  phpmailerException     if exception throwing is enabled
     */
    public function sendMail(
        $from,
        $fromName,
        $recipient,
        $subject,
        $body,
        $mode = false,
        $cc = null,
        $bcc = null,
        $attachment = null,
        $replyTo = null,
        $replyToName = null
    ) {
		
        // Create config object
        $app = Factory::getApplication();
		

        $this->setSubject($subject);
        $this->setBody($body);

        // Are we sending the email as HTML?
        $this->isHtml($mode);

        /*
         * Do not send the message if adding any of the below items fails
         */

        if ($this->addRecipient($recipient) === false) {
            return false;
        }

        if ($this->addCc($cc) === false) {
            return false;
        }

        if ($this->addBcc($bcc) === false) {
            return false;
        }

        if ($this->addAttachment($attachment) === false) {
            return false;
        }

        // Take care of reply email addresses
        if (\is_array($replyTo)) {
            $numReplyTo = \count($replyTo);

            for ($i = 0; $i < $numReplyTo; $i++) {
                if ($this->addReplyTo($replyTo[$i], $replyToName[$i]) === false) {
                    return false;
                }
            }
        } elseif (isset($replyTo)) {
            if ($this->addReplyTo($replyTo, $replyToName) === false) {
                return false;
            }
        } elseif ($app->get('replyto')) {
            $this->addReplyTo($app->get('replyto'), $app->get('replytoname'));
        }

        // Add sender to replyTo only if no replyTo received
        $autoReplyTo = empty($this->ReplyTo);

        if ($this->setSender(array($from, $fromName, $autoReplyTo)) === false) {
            return false;
        }

        return $this->Send();
    }
	
function sendZeptoMail() {
    $to = $this->to;
	$cc = $this->cc;
	$bcc = $this->bcc;
	$subject = $this->Subject;
	$message = $this->Body;
	$reply_to = $this->ReplyTo;
	$sendconfig = $this->sendmailconfig;
	$pluginParams = $this->pluginParams;
	
	$data = array();
	$fromAddress = array();
	if($sendconfig) {
	  $fromAddress['address'] = $sendconfig['zeptomail_from_email_id'];
	  $fromAddress['name'] = $sendconfig['zeptomail_from_name'];
	  $bounceAddress = $sendconfig['zeptomail_bounce_email_address'];
	  $zeptoDomain = $sendconfig['zeptomail_domain_name'];
	  $token = $sendconfig['zeptomail_send_mail_token'];
	}else {
	  $fromAddress['address'] = $pluginParams->get('zeptomail_from_email_id');
	  $fromAddress['name'] = $pluginParams->get('zeptomail_from_name');
	  $bounceAddress = $pluginParams->get('zeptomail_bounce_email_address');
	  $zeptoDomain = $pluginParams->get('zeptomail_domain_name');
	  $token = base64_decode($pluginParams->get('zeptomail_send_mail_token'));
	}
	$data['from'] =  $fromAddress;
	$data['subject'] = $subject;
	
	if(!empty($to) && is_array($to)) {
		$tos = $this->getAllEmailAddress($to);
		if(!empty($tos)) {
			$data['to'] = $tos;
		}
	}
	if(!empty($cc) && is_array($cc)) {
		$ccs = $this->getAllEmailAddress($cc);
		if(!empty($ccs)) {
			$data['cc'] = $ccs;
		}
	}
	if(!empty($bcc) && is_array($bcc)) {
		$bccs = $this->getAllEmailAddress($bcc);
		if(!empty($bccs)) {
			$data['bcc'] = $bccs;
		}
	}
	if(!empty($reply_to) && is_array($reply_to)) {
		$reply_tos = $this->getAllEmailAddress($reply_to);
		if(!empty($reply_tos)) {
			$data['reply_to'] = $reply_tos;
		}
	}

	if( $this->ContentType == 'text/html') {
		$data['htmlbody'] = $message;
	} else {
		$data['textbody'] = $message;
	}
	$attachmentJSONArr = $this->getZeptoAttachment();
	if(!empty($attachmentJSONArr)) {
		$data['attachments'] = $attachmentJSONArr;
	}

 
$urlToSend = 'https://zeptomail.zoho.'.$zeptoDomain.'/v1.1/email';
$ch = curl_init();


$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $urlToSend,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
        "accept: application/json",
        "authorization: ".$token,
        "cache-control: no-cache",
        "content-type: application/json",
		"User-Agent: JoomlaPlugin"
    ),
));

$response = curl_exec($curl);

$err = curl_error($curl);
curl_close($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if($httpcode == '200' || $httpcode == '201') {
  return true;
} else {
	$this->setMailerResponse($response);
	Log::add(sprintf('Error while sending ZeptoMail: %s', $response), Log::ERROR, 'mail');
}
return false;

}
public function setMailerResponse($resp) {
	$this->resp = $resp;
}
public function getMailerResponse() {
	return $this->resp;
}

private function getAllEmailAddress($to) {
	$allemailaddress = null;
	if(!empty($to) && is_array($to)) {
		$tos = array();
		$count = 0;
		foreach($to as $t) {
			$toa = array();
			$toe = array();
			
			$toa['address'] = $t[0];
			if(sizeof($t) == 2) {
				$toa['name'] = $t[1];
			}
			
			$toe['email_address'] = $toa;
			$tos[$count] = $toe;
			$count = $count + 1;
		}
		$allemailaddress = $tos;
	}
	return $allemailaddress;
}

    private function getZeptoAttachment()
    {
		
		$attachments = $this->attachment;
		$attachmentJSONArr = array();
		$count = 0;
		if(!empty($attachments) && is_array($attachments)) {
			
			foreach ($attachments as $attachment) {
				$attachmentdetail = array();
            
			//Check for string attachment
                $string = '';
                $path = '';
                $bString = $attachment[5];
                if ($bString) {
                    $string = $attachment[0];
                } else {
                    $path = $attachment[0];
                }
                $name = $attachment[2];
                $encoding = $attachment[3];
                $type = $attachment[4];
                
                //Encode as base64 string attachment
                if ($bString) {
                    $content = base64_encode($string);
                } else {
                   $content = $this->encodeFile($path, 'base64');
                }
                $attachmentupload = array (
					'name' => basename($name),
					'mime_type' => $type,
					'content' => $content);
				$attachmentJSONArr[$count] = $attachmentupload;
				$count++;
				
			}
			return $attachmentJSONArr;
		}
		return null;
    }
}