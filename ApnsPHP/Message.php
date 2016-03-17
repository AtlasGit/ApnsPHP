<?php
/**
 * @file
 * ApnsPHP_Message class definition.
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://code.google.com/p/apns-php/wiki/License
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to aldo.armiento@gmail.com so we can send you a copy immediately.
 *
 * @author (C) 2010 Aldo Armiento (aldo.armiento@gmail.com)
 * @version $Id$
 */

/**
 * @defgroup ApnsPHP_Message Message
 * @ingroup ApplePushNotificationService
 */

/**
 * The Push Notification Message.
 *
 * The class represents a message to be delivered to an end user device.
 * Notification Service.
 *
 * @ingroup ApnsPHP_Message
 * @see http://tinyurl.com/ApplePushNotificationPayload
 */
class ApnsPHP_Message
{
	/**
	 * @var int The maximum size allowed for a notification payload.
	 */
	const PAYLOAD_MAXIMUM_SIZE = 2048;

	/**
	 * @var string The Apple-reserved aps namespace.
	 */
	const APPLE_RESERVED_NAMESPACE = 'aps';

	/**
	 * @var bool If the JSON payload is longer than maximum allowed size, shorts message text
	 */
	protected $_bAutoAdjustLongPayload = true;

	/**
	 * @var array Recipients device tokens.
	 */
	protected $_aDeviceTokens = [];

	/**
	 * @var string Alert message to display to the user.
	 */
	protected $_sText;
	/**
	 * @var integer Number to badge the application icon with.
	 */
	protected $_nBadge;
	/**
	 * @var string Sound to play.
	 */
	protected $_sSound;
	/**
	 * @var string notification category.
	 */
	protected $_sCategory;
	/**
	 * @var True to initiates the Newsstand background download. @see http://tinyurl.com/ApplePushNotificationNewsstand
	 */
	protected $_bContentAvailable;
	/**
	 * @var string|array
	 */
	protected $_alert;

	/**
	 * @var mixed Custom properties container.
	 */
	protected $_aCustomProperties;
	/**
	 * @var int That message will expire in 604800 seconds (86400 * 7, 7 days) if not successful delivered.
	 */
	protected $_nExpiryValue = 604800;
	/**
	 * @var mixed Custom message identifier.
	 */
	protected $_mCustomIdentifier;

	/**
	 * Constructor.
	 *
	 * @param  $sDeviceToken @type string @optional Recipients device token.
	 */
	public function __construct($sDeviceToken = null) {
		if ($sDeviceToken !== null) {
			$this->addRecipient($sDeviceToken);
		}
	}

	/**
	 * PHP Magic Method. When an object is "converted" to a string, JSON-encoded
	 * payload is returned.
	 * @return mixed|string @type string JSON-encoded payload.
	 */
	public function __toString() {
		try {
			$sJSONPayload = $this->getPayload();
		} catch (ApnsPHP_Message_Exception $e) {
			$sJSONPayload = '';
		}
		return $sJSONPayload;
	}

	/**
	 * Add a recipient device token.
	 *
	 * @param  $sDeviceToken @type string Recipients device token.
	 * @throws ApnsPHP_Message_Exception if the device token
	 *         is not well formed.
	 */
	public function addRecipient($sDeviceToken) {
		if (!preg_match('~^[a-f0-9]{64}$~i', $sDeviceToken)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid device token '{$sDeviceToken}'"
			);
		}
		$this->_aDeviceTokens[] = $sDeviceToken;
	}

	/**
	 * @return array|string
	 */
	public function getAlert() {
		return $this->_alert;
	}

	/**
	 * @param string|array $alert
	 */
	public function setAlert($alert) {
		if (!is_string($alert) && !is_array($alert)) {
			throw new InvalidArgumentException('Alert must be string or array (dict)');
		}
		$this->_alert = $alert;
	}

	/**
	 * Get the auto-adjust long payload value.
	 * @return boolean The auto-adjust long payload value.
	 */
	public function getAutoAdjustLongPayload() {
		return $this->_bAutoAdjustLongPayload;
	}

	/**
	 * Get the number to badge the application icon with.
	 * @return string @type integer The number to badge the application icon with.
	 */
	public function getBadge() {
		return $this->_nBadge;
	}

	/**
	 * Get the category of notification
	 * @return string @type string The notification category
	 */
	public function getCategory() {
		return $this->_sCategory;
	}

	/**
	 * Get if should initiates the Newsstand background download.
	 * @return string @type boolean Initiates the Newsstand background download property.
	 */
	public function getContentAvailable() {
		return $this->_bContentAvailable;
	}

	/**
	 * Get the custom message identifier.
	 *
	 * @return mixed The custom message identifier.
	 */
	public function getCustomIdentifier() {
		return $this->_mCustomIdentifier;
	}

	/**
	 * Get the custom property value.
	 *
	 * @param  $sName @type string Custom property name.
	 * @throws ApnsPHP_Message_Exception if no property exists with the specified
	 *         name.
	 * @return string The custom property value.
	 */
	public function getCustomProperty($sName) {
		if (!array_key_exists($sName, $this->_aCustomProperties)) {
			throw new ApnsPHP_Message_Exception(
				"No property exists with the specified name '{$sName}'."
			);
		}
		return $this->_aCustomProperties[$sName];
	}

	/**
	 * Get the first custom property name.
	 *
	 * @deprecated Use getCustomPropertyNames() instead.
	 *
	 * @return @type string The first custom property name.
	 */
	public function getCustomPropertyName() {
		if (!is_array($this->_aCustomProperties)) {
			return null;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $aKeys[0];
	}

	/**
	 * Get all custom properties names.
	 *
	 * @return array All properties names.
	 */
	public function getCustomPropertyNames() {
		if (!is_array($this->_aCustomProperties)) {
			return [];
		}
		return array_keys($this->_aCustomProperties);
	}

	/**
	 * Get the first custom property value.
	 *
	 * @deprecated Use getCustomProperty() instead.
	 *
	 * @return mixed The first custom property value.
	 */
	public function getCustomPropertyValue() {
		if (!is_array($this->_aCustomProperties)) {
			return null;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $this->_aCustomProperties[$aKeys[0]];
	}

	/**
	 * Get the expiry value.
	 *
	 * @return integer The expire message value (in seconds).
	 */
	public function getExpiry() {
		return $this->_nExpiryValue;
	}

	/**
	 * Convert the message in a JSON-encoded payload.
	 *
	 * @throws ApnsPHP_Message_Exception if payload is longer than maximum allowed
	 *         size and AutoAdjustLongPayload is disabled.
	 * @return string JSON-encoded payload.
	 */
	public function getPayload() {
		$sJSON = json_encode($this->_getPayload(), defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
		if (!defined('JSON_UNESCAPED_UNICODE') && function_exists('mb_convert_encoding')) {
			$sJSON = preg_replace_callback(
				'~\\\\u([0-9a-f]{4})~i',
				create_function('$aMatches', 'return mb_convert_encoding(pack("H*", $aMatches[1]), "UTF-8", "UTF-16");'),
				$sJSON);
		}

		$sJSONPayload = str_replace(
			'"' . self::APPLE_RESERVED_NAMESPACE . '":[]',
			'"' . self::APPLE_RESERVED_NAMESPACE . '":{}',
			$sJSON
		);
		$nJSONPayloadLen = strlen($sJSONPayload);

		if ($nJSONPayloadLen > self::PAYLOAD_MAXIMUM_SIZE) {
			if ($this->_bAutoAdjustLongPayload) {
				$nMaxTextLen = $nTextLen = strlen($this->_sText) - ($nJSONPayloadLen - self::PAYLOAD_MAXIMUM_SIZE);
				if ($nMaxTextLen > 0) {
					while (strlen($this->_sText = mb_substr($this->_sText, 0, --$nTextLen, 'UTF-8')) > $nMaxTextLen) ;
					return $this->getPayload();
				} else {
					throw new ApnsPHP_Message_Exception(
						"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
						self::PAYLOAD_MAXIMUM_SIZE . " bytes. The message text can not be auto-adjusted."
					);
				}
			} else {
				throw new ApnsPHP_Message_Exception(
					"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
					self::PAYLOAD_MAXIMUM_SIZE . " bytes"
				);
			}
		}

		return $sJSONPayload;
	}

	/**
	 * Get a recipient.
	 *
	 * @param int $nRecipient @type integer @optional Recipient number to return.
	 * @return mixed if no recipient number
	 *         exists.
	 * @throws ApnsPHP_Message_Exception if no recipient number
	 *         exists.
	 */
	public function getRecipient($nRecipient = 0) {
		if (!isset($this->_aDeviceTokens[$nRecipient])) {
			throw new ApnsPHP_Message_Exception(
				"No recipient at index '{$nRecipient}'"
			);
		}
		return $this->_aDeviceTokens[$nRecipient];
	}

	/**
	 * Get all recipients.
	 * @return array Array of all recipients device token.
	 */
	public function getRecipients() {
		return $this->_aDeviceTokens;
	}

	/**
	 * Get the number of recipients.
	 * @return int @type integer Recipient's number.
	 */
	public function getRecipientsNumber() {
		return count($this->_aDeviceTokens);
	}

	/**
	 * Get the sound to play.
	 * @return int @type string The sound to play.
	 */
	public function getSound() {
		return $this->_sSound;
	}

	/**
	 * Get the alert message to display to the user.
	 * @return string @type string The alert message to display to the user.
	 */
	public function getText() {
		return $this->_sText;
	}

	/**
	 * Set the auto-adjust long payload value.
	 *
	 * @param  $bAutoAdjust @type boolean If true a long payload is shorted cutting
	 *         long text value.
	 */
	public function setAutoAdjustLongPayload($bAutoAdjust) {
		$this->_bAutoAdjustLongPayload = (boolean)$bAutoAdjust;
	}

	/**
	 * Set the number to badge the application icon with.
	 *
	 * @param  $nBadge @type integer A number to badge the application icon with.
	 * @throws ApnsPHP_Message_Exception if badge is not an
	 *         integer.
	 */
	public function setBadge($nBadge) {
		if (!is_int($nBadge)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid badge number '{$nBadge}'"
			);
		}
		$this->_nBadge = $nBadge;
	}

	/**
	 * Set the category of notification
	 *
	 * @param  $sCategory @type string @optional A category for ios8 notification actions.
	 */
	public function setCategory($sCategory = '') {
		$this->_sCategory = $sCategory;
	}

	/**
	 * Initiates the Newsstand background download.
	 * @see http://tinyurl.com/ApplePushNotificationNewsstand
	 *
	 * @param  $bContentAvailable @type boolean True to initiates the Newsstand background download.
	 * @throws ApnsPHP_Message_Exception if ContentAvailable is not a
	 *         boolean.
	 */
	public function setContentAvailable($bContentAvailable = true) {
		if (!is_bool($bContentAvailable)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid content-available value '{$bContentAvailable}'"
			);
		}
		$this->_bContentAvailable = $bContentAvailable ? true : null;
	}

	/**
	 * Set the custom message identifier.
	 *
	 * The custom message identifier is useful to associate a push notification
	 * to a DB record or an User entry for example. The custom message identifier
	 * can be retrieved in case of error using the getCustomIdentifier()
	 * method of an entry retrieved by the getErrors() method.
	 * This custom identifier, if present, is also used in all status message by
	 * the ApnsPHP_Push class.
	 *
	 * @param  $mCustomIdentifier @type mixed The custom message identifier.
	 */
	public function setCustomIdentifier($mCustomIdentifier) {
		$this->_mCustomIdentifier = $mCustomIdentifier;
	}

	/**
	 * Set a custom property.
	 *
	 * @param  $sName @type string Custom property name.
	 * @param  $mValue @type mixed Custom property value.
	 * @throws ApnsPHP_Message_Exception if custom property name is not outside
	 *         the Apple-reserved 'aps' namespace.
	 */
	public function setCustomProperty($sName, $mValue) {
		if (trim($sName) == self::APPLE_RESERVED_NAMESPACE) {
			throw new ApnsPHP_Message_Exception(
				"Property name '" . self::APPLE_RESERVED_NAMESPACE . "' can not be used for custom property."
			);
		}
		$this->_aCustomProperties[trim($sName)] = $mValue;
	}

	/**
	 * Set the expiry value.
	 *
	 * @param  $nExpiryValue @type integer This message will expire in N seconds
	 *         if not successful delivered.
	 * @throws ApnsPHP_Message_Exception
	 */
	public function setExpiry($nExpiryValue) {
		if (!is_int($nExpiryValue)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid seconds number '{$nExpiryValue}'"
			);
		}
		$this->_nExpiryValue = $nExpiryValue;
	}

	/**
	 * Set the sound to play.
	 *
	 * @param  $sSound @type string @optional A sound to play ('default sound' is
	 *         the default sound).
	 */
	public function setSound($sSound = 'default') {
		$this->_sSound = $sSound;
	}

	/**
	 * Set the alert message to display to the user.
	 *
	 * @param  $sText @type string An alert message to display to the user.
	 */
	public function setText($sText) {
		$this->_sText = $sText;
	}

	/**
	 * Get the payload dictionary.
	 * @return mixed @type array The payload dictionary.
	 */
	protected function _getPayload() {
		$aPayload[self::APPLE_RESERVED_NAMESPACE] = [];

		if (isset($this->_alert)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['alert'] = $this->_alert;
		} else if (isset($this->_sText)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['alert'] = (string)$this->_sText;
		}

		if (isset($this->_nBadge) && $this->_nBadge >= 0) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['badge'] = (int)$this->_nBadge;
		}
		if (isset($this->_sSound)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['sound'] = (string)$this->_sSound;
		}
		if (isset($this->_bContentAvailable)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['content-available'] = (int)$this->_bContentAvailable;
		}
		if (isset($this->_sCategory)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['category'] = (string)$this->_sCategory;
		}

		if (is_array($this->_aCustomProperties)) {
			foreach ($this->_aCustomProperties as $sPropertyName => $mPropertyValue) {
				$aPayload[$sPropertyName] = $mPropertyValue;
			}
		}

		return $aPayload;
	}
}
