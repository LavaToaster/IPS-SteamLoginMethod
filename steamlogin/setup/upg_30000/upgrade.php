<?php

namespace IPS\steamlogin\setup\upg_30000;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 3.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * ...
	 *
	 * @return	array|bool If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		return TRUE;
	}
}
