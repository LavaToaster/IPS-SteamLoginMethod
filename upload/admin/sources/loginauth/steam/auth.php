<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
if ( ! defined( 'IN_IPB' ) )
{
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class login_steam extends login_core implements interface_login
{
/**
*       Properties passed from database entry for this method
*       @access protected
*       @param  array
*/
protected $method_config        = array();
/**
*       Properties passed from conf.php for this method
*       @access protected
*       @param  array
*/
protected $external_conf        = array();

/**
*       Constructor
*       @access public
*       @param  object  ipsRegistry object
*       @param  array   DB entry array
*       @param  array   conf.php array
*/
public function __construct( ipsRegistry $registry, $method, $conf=array() )
{
        $this->method_config    = $method;
        $this->external_conf    = $conf;
        
        require_once( IPS_ROOT_PATH . 'sources/loginauth/steam/lib/steam_openid.php' );
                
        parent::__construct( $registry );
}

/**
*       Authenticate the member against your own system
*       @access  public
*       @param   string  Username
*       @param   string  Email Address
*       @param   string  Plain text password entered from log in form
*/
public function authenticate( $username, $email_address, $password )
{
    
    $board_url = ipsRegistry::$settings['logins_over_https'] ? ipsRegistry::$settings['board_url_https'] : ipsRegistry::$settings['board_url'];
    
    if(ipsRegistry::$settings['logins_over_https'] and !$_SERVER['HTTPS']) $this->registry->output->silentRedirect( ipsRegistry::$settings['base_url_https']."app=core&amp;module=global&amp;section=login&amp;do=process&amp;use_steam=1&amp;auth_key=".ipsRegistry::instance()->member()->form_hash );

    $steam_url = SteamSignIn::genUrl($board_url.'/interface/board/steam.php');
    
    
    $steam_id = SteamSignIn::validate();

    if( !$steam_id AND $this->request['use_steam'] )
    {
        $this->registry->output->silentRedirect( $steam_url );
    }
    
    
    
    if ( $steam_id )
    {
        /* Test locally */
        $localMember = $this->DB->buildAndFetch(array('select' => 'member_id', 'from' => 'members', 'where' => "steamid='".$steam_id."'"));
                
        if ( $localMember['member_id'] )
        {
            $this->member_data = $localMember;
            $this->return_code = 'SUCCESS';
        }
        else
        {
            $email = $name = '';
            
            $this->member_data = $this->createLocalMember( array( 'members'            => array(
                                                                                         'email'                    => $email,
                                                                                         'name'                        => $name,
                                                                                         'members_l_username'        => strtolower($name),
                                                                                         'members_display_name'        => $name,
                                                                                         'members_l_display_name'    => strtolower($name),
                                                                                         'joined'                    => time(),
                                                                                         'members_created_remote'    => 1,
                                                                                         'steamid'                    => $steam_id,
                                                                                        ),
                                                                                        ) );
            $this->return_code = 'SUCCESS';
        }
        return true;
    }
}
}