<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
class steamDisplayButton
{
        public function getOutput()
        {
                $base_url = ipsRegistry::$settings['base_url'];
                $board_url = ipsRegistry::$settings['board_url'];
                $hash = ipsRegistry::instance()->member()->form_hash;
                $IPBHTML = <<<HTML
<li><a href="{$base_url}app=core&amp;module=global&amp;section=login&amp;do=process&amp;use_steam=1&amp;auth_key={$hash}" class='ipsButton_secondary fixed_width'><img src='{$board_url}/public/style_extra/signin/login-steam-icon.png' alt='Sign in through Steam' /> &nbsp; Sign In with Steam</a></li>
HTML;

                return $IPBHTML;
        }
}
?>