<?php

/**
 * @package     Synapse
 * @subpackage  String/Punycode
 */

defined('_INIT') or die;

include_once(VENDOR.'/idna_convert/idna_convert.class.php');

abstract class StringPunycode
{
	/**
	 * Transforms a UTF-8 string to a Punycode string
	 * @param   string  $utfString  The UTF-8 string to transform
	 * @return  string  The punycode string
	 */
	public static function toPunycode($utfString)
	{
		$idn = new idna_convert;

		return $idn->encode($utfString);
	}

	/**
	 * Transforms a Punycode string to a UTF-8 string
	 * @param   string  $punycodeString  The Punycode string to transform
	 * @return  string  The UF-8 URL
	 */
	public static function fromPunycode($punycodeString)
	{
		$idn = new idna_convert;

		return $idn->decode($punycodeString);

	}

	/**
	 * Transforms a UTF-8 URL to a Punycode URL
	 *
	 * @param   string  $uri  The UTF-8 URL to transform
	 *
	 * @return  string  The punycode URL
	 *
	 * @since   3.1.2
	 */
	public static function urlToPunycode($uri)
	{
		$parsed = String::parse_url($uri);

		if (!isset($parsed['host']) || $parsed['host'] == '')
		{
			// If there is no host we do not need to convert it.
			return '';
		}

		$host = $parsed['host'];
		$hostExploded = explode('.', $host);
		$newhost = '';

		foreach ($hostExploded as $hostex)
		{
			$hostex = static::toPunycode($hostex);
			$newhost .= $hostex . '.';
		}

		$newhost = substr($newhost, 0, -1);
		$newuri = '';

		if (!empty($parsed['scheme']))
		{
			// Assume :// is required although it is not always.
			$newuri .= $parsed['scheme'] . '://';
		}

		if (!empty($newhost))
		{
			$newuri .= $newhost;
		}

		if (!empty($parsed['port']))
		{
			$newuri .= ':' . $parsed['port'];
		}

		if (!empty($parsed['path']))
		{
			$newuri .= $parsed['path'];
		}

		if (!empty($parsed['query']))
		{
			$newuri .= '?' . $parsed['query'];
		}

		return $newuri;
	}

	/**
	 * Transforms a Punycode URL to a UTF-8 URL
	 * @param   string  $uri  The Punycode URL to transform
	 * @return  string  The UTF-8 URL
	 */
	public static function urlToUTF8($uri)
	{
		if (empty($uri))
		{
			return;
		}

		$parsed = String::parse_url($uri);

		if (!isset($parsed['host']) || $parsed['host'] == '')
		{
			// If there is no host we do not need to convert it.
			return $uri;
		}

		$host = $parsed['host'];
		$hostExploded = explode('.', $host);
		$newhost = '';

		foreach ($hostExploded as $hostex)
		{
			$hostex = self::fromPunycode($hostex);
			$newhost .= $hostex . '.';
		}

		$newhost = substr($newhost, 0, -1);
		$newuri = '';

		if (!empty($parsed['scheme']))
		{
			// Assume :// is required although it is not always.
			$newuri .= $parsed['scheme'] . '://';
		}

		if (!empty($newhost))
		{
			$newuri .= $newhost;
		}

		if (!empty($parsed['port']))
		{
			$newuri .= ':' . $parsed['port'];
		}

		if (!empty($parsed['path']))
		{
			$newuri .= $parsed['path'];
		}

		if (!empty($parsed['query']))
		{
			$newuri .= '?' . $parsed['query'];
		}

		return $newuri;
	}

	/**
	 * Transforms a UTF-8 e-mail to a Punycode e-mail
	 * This assumes a valid email address
	 * @param   string  $email  The UTF-8 e-mail to transform
	 * @return  string  The punycode e-mail
	 */
	public static function emailToPunycode($email)
	{
		$explodedAddress = explode('@', $email);

		// Not addressing UTF-8 user names
		$newEmail = $explodedAddress[0];

		if (!empty($explodedAddress[1]))
		{
			$domainExploded = explode('.', $explodedAddress[1]);
			$newdomain = '';

			foreach ($domainExploded as $domainex)
			{
				$domainex = static::toPunycode($domainex);
				$newdomain .= $domainex . '.';
			}

			$newdomain = substr($newdomain, 0, -1);
			$newEmail = $newEmail . '@' . $newdomain;
		}

		return $newEmail;
	}

	/**
	 * Transforms a Punycode e-mail to a UTF-8 e-mail
	 * This assumes a valid email address
	 *
	 * @param   string  $email  The punycode e-mail to transform
	 *
	 * @return  string  The punycode e-mail
	 *
	 * @since   3.1.2
	 */
	public static function emailToUTF8($email)
	{
		$explodedAddress = explode('@', $email);

		// Not addressing UTF-8 user names
		$newEmail = $explodedAddress[0];

		if (!empty($explodedAddress[1]))
		{
			$domainExploded = explode('.', $explodedAddress[1]);
			$newdomain = '';

			foreach ($domainExploded as $domainex)
			{
				$domainex = static::fromPunycode($domainex);
				$newdomain .= $domainex . '.';
			}

			$newdomain = substr($newdomain, 0, -1);
			$newEmail = $newEmail . '@' . $newdomain;
		}

		return $newEmail;
	}
}
