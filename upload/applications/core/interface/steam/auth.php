<?php

require_once str_replace( 'applications/core/interface/steam/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';


$openidParams = array();

foreach (array_keys($_GET) as $key) {
	if (strpos($key, 'openid_') === 0) {
		$openidParams[$key] = $_GET[$key];
	}
}

$params = http_build_query($openidParams);

if (\IPS\Request::i()->openid_invalidate_handle == 'ucp') {
	\IPS\Output::i()->redirect(
		\IPS\Http\Url::internal(
			"app=core&module=system&controller=settings&area=profilesync&service=Steam&loginProcess=steam&" . $params,
			'front',
			'settings_Steam'
		)
	);
} else {
	\IPS\Output::i()->redirect(
		\IPS\Http\Url::internal(
			"app=core&module=system&controller=login&loginProcess=steam&" . $params,
			\IPS\Request::i()->openid_invalidate_handle
		)
	);
}