//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class steamlogin_hook_AddSteamToLoginHandlerMethods extends _HOOK_CLASS_
{
    public static function handlerClasses() {
	try
	{
	        $methods = parent::handlerClasses();
	
	        $methods[] = 'IPS\steamlogin\sources\Login\Steam';
	
	        return $methods;
	}
	catch ( \RuntimeException $e )
	{
		if ( method_exists( get_parent_class(), __FUNCTION__ ) )
		{
			return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
		}
		else
		{
			throw $e;
		}
	}
    }
}
