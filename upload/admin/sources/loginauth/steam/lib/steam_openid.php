<?php

/**
 *
 * @package Steam Community API
 * @copyright (c) 2010 ichimonai.com
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 *
 */
class SteamSignIn
{
    const STEAM_LOGIN = 'https://steamcommunity.com/openid/login';

    /**
     * Get the URL to sign into steam
     *
     * @param mixed returnTo URI to tell steam where to return, MUST BE THE FULL URI WITH THE PROTOCOL
     * @param bool useAmp Use &amp; in the URL, true; or just &, false.
     * @return string The string to go in the URL
     */
    public static function genUrl($returnTo = false, $useAmp = true)
    {
        if (!$returnTo) {
            $returnTo = (ipsRegistry::$settings['logins_over_https'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        }

        $params = array(
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => (ipsRegistry::$settings['logins_over_https'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        );

        $sep = ($useAmp) ? '&amp;' : '&';

        return self::STEAM_LOGIN . '?' . http_build_query($params, '', $sep);
    }

    /**
     * Validate the incoming data
     *
     * @return string Returns the SteamID64 if successful or empty string on failure
     */
    public static function validate()
    {
        // Start off with some basic params
        $params = array(
            'openid.assoc_handle' => $_GET['openid_assoc_handle'],
            'openid.signed' => $_GET['openid_signed'],
            'openid.sig' => $_GET['openid_sig'],
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
        );

        // Get all the params that were sent back and resend them for validation
        $signed = explode(',', $_GET['openid_signed']);
        foreach ($signed as $item) {
            $val = $_GET['openid_' . str_replace('.', '_', $item)];
            $params['openid.' . $item] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
        }

        // Finally, add the all important mode. 
        $params['openid.mode'] = 'check_authentication';

        //why do we do this? cause file_get_contents to a url goes left in many server configs... IPS is sturdier
        $classToLoad = IPSLib::loadLibrary(IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement');
        $classFileManagement = new $classToLoad;
        $result = $classFileManagement->postFileContents(self::STEAM_LOGIN, $params);
        //also... i could swear i commited this fork.

        // Validate wheather it's true and if we have a good ID
        preg_match("#^http://steamcommunity.com/openid/id/([0-9]{17,25})#", $_GET['openid_claimed_id'], $matches);
        $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

        // Return our final value
        return preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamID64 : false;
    }
}