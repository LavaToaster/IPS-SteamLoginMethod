<?php

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

/**
 * Steam Login Handler
 */
class _Steam extends \IPS\Login\LoginAbstract
{
	/**
	 * @brief    Login handler key
	 */
	public static $loginKey = 'Steam';

	/**
	 * @brief    Icon
	 */
	public static $icon = 'steam';

	/**
	 * Get Form
	 *
	 * @param    string $url The URL for the login page
	 * @param    bool   $ucp Is UCP? (as opposed to login form)
	 *
	 * @return    string
	 */
	public function loginForm($url, $ucp = false)
	{
		return \IPS\Theme::i()->getTemplate('plugins', 'core',
			'global')->steam((string)\IPS\Http\Url::external('https://steamcommunity.com/openid/login')->setQueryString(array(
			'openid.ns' => 'http://specs.openid.net/auth/2.0',
			'openid.mode' => 'checkid_setup',
			'openid.return_to' => (string)\IPS\Http\Url::internal('applications/core/interface/steam/auth.php', 'none'),
			'openid.realm' => (string)\IPS\Http\Url::internal('', 'none'),
			'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
			'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
			'openid.assoc_handle' => ($ucp ? 'ucp' : \IPS\Dispatcher::i()->controllerLocation)
		)));
	}

	/**
	 * Can a member sign in with this login handler?
	 * Used to ensure when a user disassociates a social login that they have some other way of logging in
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	public function canProcess(\IPS\Member $member)
	{
		// Return a truthy or falsy value
		return (bool) $member->steamid;
	}

	/**
	 * Authenticate
	 *
	 * @param    string      $url    The URL for the login page
	 * @param    \IPS\Member $member If we want to integrate this login method with an existing member, provide the
	 *                               member object
	 *
	 * @return    \IPS\Member
	 * @throws    \IPS\Login\Exception
	 */
	public function authenticate($url, $member = null)
	{
		try {
			$steamId = $this->validate();

			if (!$steamId) {
				throw new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::INTERNAL_ERROR);
			}

			/* If an api key is provided, attempt to load the user from steam */
			$response = null;
			$userData = null;

			if ($this->settings['api_key']) {
				try {
					$response = \IPS\Http\Url::external("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->settings['api_key']}&steamids={$steamId}")->request()->get()->decodeJson();

					if ($response) {
						// Get the first player
						$userData = $response['response']['players'][0];
					}
				} catch (\IPS\Http\Request\Exception $e) {
					throw new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::INTERNAL_ERROR, $e);
				}
			}

			/* Find  member */
			$newMember = false;

			if ($member === null) {
				try {
					$memberData = \IPS\Db::i()->select('*', 'core_members', array('steamid=?', $steamId))->first();
					$member = \IPS\Member::constructFromData($memberData);
				} catch (\UnderflowException $e) {
					$member = \IPS\Member::load(null);
				}

				if (!$member->member_id) {

					$member = new \IPS\Member;
					$member->member_group_id = \IPS\Settings::i()->member_group;

					if (\IPS\Settings::i()->reg_auth_type == 'admin' or \IPS\Settings::i()->reg_auth_type == 'admin_user') {
						$member->members_bitoptions['validating'] = true;
					}

					if (isset($userData)) {

						if ($this->settings['use_steam_name']) {
							$existingUsername = \IPS\Member::load($userData['personaname'], 'name');
							if (!$existingUsername->member_id) {

								$member->name = $userData['personaname'];
							}
						}

						$member->profilesync = json_encode(array(
							static::$loginKey => array(
								'photo' => true,
								'cover' => false,
								'status' => ''
							)
						));

					}
					$newMember = true;
				}
			}

			/* Create member */
			$member->steamid = $steamId;
			$member->save();

			/* Sync */
			if ($newMember) {
				if (\IPS\Settings::i()->reg_auth_type == 'admin_user') {
					\IPS\Db::i()->update('core_validating', array('user_verified' => 1),
						array('member_id=?', $member->member_id));
				}

				$sync = new \IPS\core\ProfileSync\Steam($member);
				$sync->sync();
			}

			/* Return */
			return $member;
		} catch (\IPS\Http\Request\Exception $e) {
			throw new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::INTERNAL_ERROR);
		}
	}

	/**
	 * Link Account
	 *
	 * @param    \IPS\Member $member  The member
	 * @param    mixed       $details Details as they were passed to the exception thrown in authenticate()
	 *
	 * @return    void
	 */
	public static function link(\IPS\Member $member, $details)
	{
		// This isn't used as an existing member with the same username as the incoming
		// member cannot override the link account suggestion. Additionally this would
		// also leak the existing members email to the new user.
	}

	/**
	 * ACP Settings Form
	 *
	 * @param    string $url URL to redirect user to after successful submission
	 *
	 * @return    array    List of settings to save - settings will be stored to core_login_handlers.login_settings DB
	 *                     field
	 * @code
	return array( 'savekey'	=> new \IPS\Helpers\Form\[Type]( ... ), ... );
	 * @endcode
	 */
	public function acpForm()
	{
		return array(
			'api_key' => new \IPS\Helpers\Form\Text('login_steam_key',
				(isset($this->settings['api_key'])) ? $this->settings['api_key'] : '', false),
			'use_steam_name' => new \IPS\Helpers\Form\YesNo('login_steam_name',
				(isset($this->settings['use_steam_name'])) ? $this->settings['use_steam_name'] : false, true),
		);
	}

	/**
	 * Test Settings
	 *
	 * @return    bool
	 * @throws    \IPS\Http\Request\Exception
	 * @throws    \UnexpectedValueException    If response code is not 200
	 */
	public function testSettings()
	{
		if ($this->settings['api_key']) {
			try {
				$response = \IPS\Http\Url::external("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->settings['api_key']}&steamids=")->request()->get();

				if ($response->httpResponseCode != 200) {
					throw new \InvalidArgumentException(\IPS\Member::loggedIn()->language()->addToStack('login_3p_bad',
						false,
						array('sprintf' => array(\IPS\Member::loggedIn()->language()->addToStack('login_handler_steam')))));
				}

			} catch (\IPS\Http\Request\Exception $e) {

				throw new \InvalidArgumentException(\IPS\Member::loggedIn()->language()->addToStack('login_3p_bad',
					false,
					array('sprintf' => array(\IPS\Member::loggedIn()->language()->addToStack('login_handler_steam')))));

			}
		}
		return true;
	}

	/**
	 * Can a member change their email/password with this login handler?
	 *
	 * @param    string      $type   'email' or 'password'
	 * @param    \IPS\Member $member The member
	 *
	 * @return    bool
	 */
	public function canChange($type, \IPS\Member $member)
	{
		return false;
	}

	/**
	 * This will validate the incoming Steam OpenID request
	 *
	 * @package Steam Community API
	 * @copyright (c) 2010 ichimonai.com
	 * @license http://opensource.org/licenses/mit-license.php The MIT License
	 *
	 * @return int|bool
	 */
	private function validate()
	{
		$params = array(
			'openid.signed' => \IPS\Request::i()->openid_signed,
			'openid.sig' => str_replace(' ', '+', \IPS\Request::i()->openid_sig),
			'openid.ns' => 'http://specs.openid.net/auth/2.0',
		);

		foreach ($params as $key => &$value) {
			$value = urldecode($value);
		}

		// Get all the params that were sent back and resend them for validation
		$signed = explode(',', urldecode(\IPS\Request::i()->openid_signed));
		foreach ($signed as $item) {
			$val = \IPS\Request::i()->{'openid_' . str_replace('.', '_', $item)};

			if ($item !== 'response_nonce' || strpos($val, '%') !== false) {
				$val = urldecode($val);
			}

			$params['openid.' . $item] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
		}

		// Finally, add the all important mode.
		$params['openid.mode'] = 'check_authentication';

		// Validate whether it's true and if we have a good ID
		preg_match("#^http://steamcommunity.com/openid/id/([0-9]{17,25})#", urldecode($_GET['openid_claimed_id']), $matches);
		$steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

		$response = (string)\IPS\Http\Url::external('https://steamcommunity.com/openid/login')->request()->post($params);

		$values = array();

		foreach (explode("\n", $response) as $value) {
			$data = explode(":", $value);

			$key = $data[0];
			unset($data[0]);

			$values[$key] = implode(':', $data);
		}

		// Return our final value
		return $values['is_valid'] === 'true' ? $steamID64 : false;
	}
}
