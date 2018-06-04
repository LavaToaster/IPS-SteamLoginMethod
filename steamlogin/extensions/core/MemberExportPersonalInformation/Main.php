<?php
/**
 * @brief		ACP Export Personal Information
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Sign in through Steam
 * @since		04 Jun 2018
 */

namespace IPS\steamlogin\extensions\core\MemberExportPersonalInformation;

/* To prevent PHP errors (extending class does not exist) revealing path */
use function var_dump;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Export Personal Information
 */
class _Main
{
	/**
	 * Return data
	 * @param	\IPS\Member		$member		The member
	 *
	 * @return	array
	 */
	public function getData( \IPS\Member $member )
	{
	    $data = [];

	    if ($member->steamid) {
	        $data['steam_user_id'] = $member->steamid;
        }

		return $data;
	}
}
