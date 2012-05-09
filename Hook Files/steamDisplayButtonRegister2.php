<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
class steamDisplayButtonRegister2
{
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->lang     = $this->registry->getClass('class_localization');
        ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_steam_login' ), 'core' );
    }
    
    public function getOutput()
    {
        $base_url = ipsRegistry::$settings['base_url'];
        $board_url = ipsRegistry::$settings['board_url'];
        //var_dump($this->lang->words);
        $hash = ipsRegistry::instance()->member()->form_hash;
        if(!(IPSLib::loginMethod_enabled('facebook') || IPSLib::loginMethod_enabled('twitter'))){
            $IPBHTML = <<<HTML
                    <div class='ipsBox_container ipsBox_notice ipsForm ipsForm_horizontal' id='external_services'>
                        <strong class='ipsField_title' id='save_time'>{$this->lang->words['want_to_save_time']}</strong>
                        <div class='ipsField_content'>
                            <ul class='ipsList_inline'>
                                <li><a href="{$base_url}app=core&amp;module=global&amp;section=login&amp;do=process&amp;use_steam=1&amp;auth_key={$hash}"><img src='{$board_url}/public/style_extra/signin/login-steam.png' alt='Steam' /></a></li>
                            </ul>
                        </div>
                    </div>
HTML;
        }
        return $IPBHTML;
    }
}
?>