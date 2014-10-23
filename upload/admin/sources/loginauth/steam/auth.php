<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
if (!defined('IN_IPB')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class login_steam extends login_core implements interface_login
{
    /**
     * @param array $method_config Properties passed from database entry for this method
     */
    protected $method_config = array();
    /**
     * @param array $external_conf Properties passed from conf.php for this method
     */
    protected $external_conf = array();

    /**
     * Constructor
     *
     * @param ipsRegistry $registry
     * @param array $method
     * @param array $conf
     */
    public function __construct(ipsRegistry $registry, $method, $conf = array())
    {
        $this->method_config = $method;
        $this->external_conf = $conf;

        require_once(IPS_ROOT_PATH . 'sources/loginauth/steam/lib/steam_openid.php');

        parent::__construct($registry);
    }

    /**
     * Authenticates the steam member
     *
     * @param string $username Username
     * @param string $email_address Email Address
     * @param string $password Plain text password entered from log in form
     *
     * @return bool
     */
    public function authenticate($username, $email_address, $password)
    {
        //Does the board use HTTPS For logins?
        $board_url = ipsRegistry::$settings['logins_over_https'] ? ipsRegistry::$settings['board_url_https'] : ipsRegistry::$settings['board_url'];

        //If the board uses HTTPS For logins and we are not using HTTPS get our butts onto HTTPS. Why? Because open id thinks the same website using http and https are actually different ones
        if (ipsRegistry::$settings['logins_over_https'] and $_SERVER['HTTPS'] != 'on') $this->registry->output->silentRedirect(ipsRegistry::$settings['base_url_https'] . "app=core&amp;module=global&amp;section=login&amp;do=process&amp;use_steam=1&amp;auth_key=" . ipsRegistry::instance()->member()->form_hash);

        $steam_url = SteamSignIn::genUrl($board_url . '/interface/board/steam.php');

        // I say, Does this user be who he claims to be?
        $steam_id = $this->request['use_steam'] ? SteamSignIn::validate() : null;

        if (!$steam_id AND $this->request['use_steam']) {
            $this->registry->output->silentRedirect($steam_url);
        }


        //Passport Please
        if ($steam_id) {
            //We have validated your Identity!
            $localMember = $this->DB->buildAndFetch(array('select' => 'member_id', 'from' => 'members', 'where' => "steamid='" . $steam_id . "'"));

            //Have you been here before?
            if ($localMember['member_id']) {
                //Welcome Back lets just log you in here
                $this->member_data = IPSMember::load($localMember['member_id'], 'extendedProfile,groups');;
                $this->return_code = 'SUCCESS';
            } else {
                //Welcome, lets just set you up a temporary account you can fill in the details in a second
                $email = $name = '';

                $this->member_data = $this->createLocalMember(array('members' => array(
                    'email' => $email,
                    'name' => $name,
                    'members_l_username' => strtolower($name),
                    'members_display_name' => $name,
                    'members_l_display_name' => strtolower($name),
                    'joined' => time(),
                    'members_created_remote' => 1,
                    'steamid' => $steam_id,
                ),
                ));
                $this->return_code = 'SUCCESS';
            }
            $this->request['rememberMe'] = TRUE;
            return true;
        }
    }
}