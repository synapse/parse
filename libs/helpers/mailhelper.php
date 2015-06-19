<?php

/**
 * @package     Synapse
 * @subpackage  Helpers/Mail
 */

defined('_INIT') or die;


class MailHelper
{
    /**
	 * Cleans single line inputs.
	 * @param   string  $value  String to be cleaned.
	 * @return  string  Cleaned string.
	 */
	public static function cleanLine($value)
	{
		$value = StringPunycode::emailToPunycode($value);

		return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', $value));
	}

    /**
	 * Cleans multi-line inputs.
	 * @param   string  $value  Multi-line string to be cleaned.
	 * @return  string  Cleaned multi-line string.
	 */
	public static function cleanText($value)
	{
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)(content-type:|to:|cc:|bcc:)/i', '', $value));
	}

	/**
	 * Cleans any injected headers from the email body.
	 * @param   string  $body  email body string.
	 * @return  string  Cleaned email body string.
	 */
	public static function cleanBody($body)
	{
		// Strip all email headers from a string
		return preg_replace("/((From:|To:|Cc:|Bcc:|Subject:|Content-type:) ([\S]+))/", "", $body);
	}

	/**
	 * Cleans any injected headers from the subject string.
	 * @param   string  $subject  email subject string.
	 * @return  string  Cleaned email subject string.
	 */
	public static function cleanSubject($subject)
	{
		return preg_replace("/((From:|To:|Cc:|Bcc:|Content-type:) ([\S]+))/", "", $subject);
	}

	/**
	 * Verifies that an email address does not have any extra headers injected into it.
	 * @param   string  $address  email address.
	 * @return  mixed   email address string or boolean false if injected headers are present.
	 */
	public static function cleanAddress($address)
	{
		if (preg_match("[\s;,]", $address))
		{
			return false;
		}

		return $address;
	}

	/**
	 * Verifies that the string is in a proper email address format.
	 * @param   string  $email  String to be verified.
	 * @return  boolean  True if string has the correct format; false otherwise.
	 */
	public static function isEmailAddress($email)
	{
		// Split the email into a local and domain
		$atIndex = strrpos($email, "@");
		$domain = substr($email, $atIndex + 1);
		$local = substr($email, 0, $atIndex);

		// Check Length of domain
		$domainLen = strlen($domain);

		if ($domainLen < 1 || $domainLen > 255)
		{
			return false;
		}

		/*
		 * Check the local address
		 * We're a bit more conservative about what constitutes a "legal" address, that is, A-Za-z0-9!#$%&\'*+/=?^_`{|}~-
		 * Also, the last character in local cannot be a period ('.')
		 */
		$allowed = 'A-Za-z0-9!#&*+=?_-';
		$regex = "/^[$allowed][\.$allowed]{0,63}$/";

		if (!preg_match($regex, $local) || substr($local, -1) == '.')
		{
			return false;
		}

		// No problem if the domain looks like an IP address, ish
		$regex = '/^[0-9\.]+$/';

		if (preg_match($regex, $domain))
		{
			return true;
		}

		// Check Lengths
		$localLen = strlen($local);

		if ($localLen < 1 || $localLen > 64)
		{
			return false;
		}

		// Check the domain
		$domain_array = explode(".", rtrim($domain, '.'));
		$regex = '/^[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/';

		foreach ($domain_array as $domain)
		{
			// Convert domain to punycode
			$domain = StringPunycode::toPunycode($domain);

			// Must be something
			if (!$domain)
			{
				return false;
			}

			// Check for invalid characters
			if (!preg_match($regex, $domain))
			{
				return false;
			}

			// Check for a dash at the beginning of the domain
			if (strpos($domain, '-') === 0)
			{
				return false;
			}

			// Check for a dash at the end of the domain
			$length = strlen($domain) - 1;

			if (strpos($domain, '-', $length) === $length)
			{
				return false;
			}
		}

		return true;
	}
}