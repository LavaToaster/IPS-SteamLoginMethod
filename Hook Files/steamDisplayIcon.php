<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
class steamDisplayIcon
{
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->lang     = $this->registry->getClass('class_localization');
        $this->lang->loadLanguageFile( array( 'public_steam_login' ) );
    }
           
    public function getOutput()
    {   
        $IPBHTML = "<a href='".ipsRegistry::$settings['base_url']."app=core&amp;module=global&amp;section=login&amp;do=process&amp;use_steam=1&amp;auth_key=".ipsRegistry::instance()->member()->form_hash."'><img src='".ipsRegistry::$settings['board_url']."/public/style_extra/signin/login-steam-icon.png' alt='{$this->lang->words['log_in_steam']}' /></a>";
        return $IPBHTML;
    }
}
?>