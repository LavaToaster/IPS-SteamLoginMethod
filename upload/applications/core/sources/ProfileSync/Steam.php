<?php

namespace IPS\core\ProfileSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Steam Profile Sync
 */
class _Steam extends ProfileSyncAbstract
{
	/**
	 * @brief	Login handler key
	 */
	public static $loginKey = 'Steam';

	/**
	 * @brief	Icon
	 */
	public static $icon = 'steam';

	/**
	 * @brief	Authorization token
	 */
	protected $authToken = NULL;

	protected function userData() {
		$loginHandler = \IPS\Login\LoginAbstract::load('steam');

		/* If an api key is provided, attempt to load the user from steam */
		$response = null;
		$userData = null;

		if($loginHandler->settings['api_key']) {
			try {
				$response = \IPS\Http\Url::external("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$loginHandler->settings['api_key']}&steamids={$this->member->steamid}")->request()->get()->decodeJson();

				if ($response) {
					// Get the first player
					return $response['response']['players'][0];
				}
			} catch ( \IPS\Http\Request\Exception $e ) {

			}
		}

		return NULL;
	}

	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	public function connected()
	{
		return $this->member->steamid != 0;
	}

	/**
	 * Get photo
	 *
	 * @return	\IPS\Http\Url|null
	 */
	public function photo()
	{
		try
		{
			$user = $this->userData();
			return \IPS\Http\Url::external( $user['avatarfull'] );
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			return NULL;
		}
	}

	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		try
		{
			$user = $this->userData();

			return $user['personaname'];
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			return NULL;
		}
	}

	/**
	 * Disassociate
	 *
	 * @return	void
	 */
	protected function _disassociate()
	{
		$this->member->steamid = 0;
		$this->member->save();
	}
}