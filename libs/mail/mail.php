<?php

/**
 * @package     Synapse
 * @subpackage  Mail
 */

defined('_INIT') or die;

include_once(VENDOR.'/phpmailer/phpmailer.php');

class Mail extends PHPMailer
{

	protected static $instances = array();
	public $CharSet = 'utf-8';


	public function __construct()
	{
		// PHPMailer has an issue using the relative path for its language files
		//$this->SetLanguage('synapse', VENDOR . '/phpmailer/language/');
	}


	public static function getInstance($id = 'Synapse')
	{
		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new Mail;
		}

		return self::$instances[$id];
	}


	public function Send()
	{

        if (($this->Mailer == 'mail') && !function_exists('mail'))
        {
            throw new RuntimeException(sprintf('%s::Send mail not enabled.', get_class($this)));
        }

        @$result = parent::Send();

        if ($result == false)
        {
            throw new RuntimeException(sprintf('%s::Send failed: "%s".', get_class($this), $this->ErrorInfo));
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
	 * @return  Mail  Returns this object for chaining.
	 * @throws  UnexpectedValueException
	 */
	public function setSender($from)
	{
		if (is_array($from))
		{
			// If $from is an array we assume it has an address and a name
			if (isset($from[2]))
			{
				// If it is an array with entries, use them
				$this->SetFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]), (bool) $from[2]);
			}
			else
			{
				$this->SetFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]));
			}
		}
		elseif (is_string($from))
		{
			// If it is a string we assume it is just the address
			$this->SetFrom(MailHelper::cleanLine($from));
		}
		else
		{
			// If it is neither, we log a message and throw an exception
			JLog::add(JText::sprintf('JLIB_MAIL_INVALID_EMAIL_SENDER', $from), JLog::WARNING, 'jerror');

			throw new UnexpectedValueException(sprintf('Invalid email Sender: %s, Mail::setSender(%s)', $from));
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
	 * @since   11.1
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
	 * @since   11.1
	 */
	public function setBody($content)
	{
		/*
		 * Filter the Body
		 * TODO: Check for XSS
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
	 * @return  Mail  Returns this object for chaining.
	 * @throws  InvalidArgumentException
	 */
	protected function add($recipient, $name = '', $method = 'AddAddress')
	{
		// If the recipient is an array, add each recipient... otherwise just add the one
		if (is_array($recipient))
		{
			if (is_array($name))
			{
				$combined = array_combine($recipient, $name);

				if ($combined === false)
				{
					throw new InvalidArgumentException("The number of elements for each array isn't equal.");
				}

				foreach ($combined as $recipientEmail => $recipientName)
				{
					$recipientEmail = MailHelper::cleanLine($recipientEmail);
					$recipientName = MailHelper::cleanLine($recipientName);
					call_user_func('parent::' . $method, $recipientEmail, $recipientName);
				}
			}
			else
			{
				$name = MailHelper::cleanLine($name);

				foreach ($recipient as $to)
				{
					$to = MailHelper::cleanLine($to);
					call_user_func('parent::' . $method, $to, $name);
				}
			}
		}
		else
		{
			$recipient = MailHelper::cleanLine($recipient);
			call_user_func('parent::' . $method, $recipient, $name);
		}

		return $this;
	}

	/**
	 * Add recipients to the email
	 *
	 * @param   mixed  $recipient  Either a string or array of strings [email address(es)]
	 * @param   mixed  $name       Either a string or array of strings [name(s)]
	 *
	 * @return  Mail  Returns this object for chaining.
	 */
	public function addRecipient($recipient, $name = '')
	{
		$this->add($recipient, $name, 'AddAddress');

		return $this;
	}

	/**
	 * Add carbon copy recipients to the email
	 *
	 * @param   mixed  $cc    Either a string or array of strings [email address(es)]
	 * @param   mixed  $name  Either a string or array of strings [name(s)]
	 *
	 * @return  Mail  Returns this object for chaining.
	 */
	public function addCC($cc, $name = '')
	{
		// If the carbon copy recipient is an array, add each recipient... otherwise just add the one
		if (isset($cc))
		{
			$this->add($cc, $name, 'AddCC');
		}

		return $this;
	}

	/**
	 * Add blind carbon copy recipients to the email
	 *
	 * @param   mixed  $bcc   Either a string or array of strings [email address(es)]
	 * @param   mixed  $name  Either a string or array of strings [name(s)]
	 *
	 * @return  Mail  Returns this object for chaining.
	 */
	public function addBCC($bcc, $name = '')
	{
		// If the blind carbon copy recipient is an array, add each recipient... otherwise just add the one
		if (isset($bcc))
		{
			$this->add($bcc, $name, 'AddBCC');
		}

		return $this;
	}

	/**
	 * Add file attachments to the email
	 *
	 * @param   mixed  $attachment  Either a string or array of strings [filenames]
	 * @param   mixed  $name        Either a string or array of strings [names]
	 * @param   mixed  $encoding    The encoding of the attachment
	 * @param   mixed  $type        The mime type
	 *
	 * @return  Mail  Returns this object for chaining.
	 * @throws  InvalidArgumentException
	 */
	public function addAttachment($attachment, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		// If the file attachments is an array, add each file... otherwise just add the one
		if (isset($attachment))
		{
			if (is_array($attachment))
			{
				if (!empty($name) && count($attachment) != count($name))
				{
					throw new InvalidArgumentException("The number of attachments must be equal with the number of name");
				}

				foreach ($attachment as $key => $file)
				{
					if (!empty($name))
					{
						parent::AddAttachment($file, $name[$key], $encoding, $type);
					}
					else
					{
						parent::AddAttachment($file, $name, $encoding, $type);
					}
				}
			}
			else
			{
				parent::AddAttachment($attachment, $name, $encoding, $type);
			}
		}

		return $this;
	}

	/**
	 * Add Reply to email address(es) to the email
	 *
	 * @param   mixed  $replyto  Either a string or array of strings [email address(es)]
	 * @param   mixed  $name     Either a string or array of strings [name(s)]
	 *
	 * @return  Mail  Returns this object for chaining.
	 */
	public function addReplyTo($replyto, $name = '')
	{
		$this->add($replyto, $name, 'AddReplyTo');

		return $this;
	}

	/**
	 * Sets message type to HTML
	 *
	 * @param   boolean  $ishtml  Boolean true or false.
	 *
	 * @return  Mail  Returns this object for chaining.
	 */
	public function isHtml($ishtml = true)
	{
		parent::IsHTML($ishtml);

		return $this;
	}

	/**
	 * Use sendmail for sending the email
	 *
	 * @param   string  $sendmail  Path to sendmail [optional]
	 *
	 * @return  boolean  True on success
	 */
	public function useSendmail($sendmail = null)
	{
		$this->Sendmail = $sendmail;

		if (!empty($this->Sendmail))
		{
			$this->IsSendmail();

			return true;
		}
		else
		{
			$this->IsMail();

			return false;
		}
	}

	/**
	 * Use SMTP for sending the email
	 *
	 * @param   boolean  $auth    SMTP Authentication [optional]
	 * @param   string   $host    SMTP Host [optional]
	 * @param   string   $user    SMTP Username [optional]
	 * @param   string   $pass    SMTP Password [optional]
	 * @param   string   $secure  Use secure methods ssl/tls
	 * @param   integer  $port    The SMTP port
	 *
	 * @return  boolean  True on success
	 */
	public function useSMTP($auth = null, $host = null, $user = null, $pass = null, $secure = null, $port = 25)
	{
		$this->SMTPAuth = $auth;
		$this->Host = $host;
		$this->Username = $user;
		$this->Password = $pass;
		$this->Port = $port;

		if ($secure == 'ssl' || $secure == 'tls')
		{
			$this->SMTPSecure = $secure;
		}

		if (($this->SMTPAuth !== null && $this->Host !== null && $this->Username !== null && $this->Password !== null)
			|| ($this->SMTPAuth === null && $this->Host !== null))
		{
			$this->IsSMTP();

			return true;
		}
		else
		{
			$this->IsMail();

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
	 * @return  boolean  True on success
	 */
	public function sendMail($from, $fromName, $recipient, $subject, $body, $mode = false, $cc = null, $bcc = null, $attachment = null, $replyTo = null, $replyToName = null)
	{
		$this->setSubject($subject);
		$this->setBody($body);

		// Are we sending the email as HTML?
		if ($mode)
		{
			$this->IsHTML(true);
		}

		$this->addRecipient($recipient);
		$this->addCC($cc);
		$this->addBCC($bcc);
		$this->addAttachment($attachment);

		// Take care of reply email addresses
		if (is_array($replyTo))
		{
			$numReplyTo = count($replyTo);

			for ($i = 0; $i < $numReplyTo; $i++)
			{
				$this->addReplyTo(array($replyTo[$i], $replyToName[$i]));
			}
		}
		elseif (isset($replyTo))
		{
			$this->addReplyTo(array($replyTo, $replyToName));
		}

		// Add sender to replyTo only if no replyTo received
		$autoReplyTo = (empty($this->ReplyTo)) ? true : false;
		$this->setSender(array($from, $fromName, $autoReplyTo));

		return $this->Send();
	}
}
