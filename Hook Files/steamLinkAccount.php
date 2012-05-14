<?php
/**
 * @author Adam Lavin (Lavoaster)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
class steamLinkAccount extends usercpForms_core
{         
    public function getLinks()
    {
        $this->lang->loadLanguageFile( array( 'public_steam_login' ) );
        $return = parent::getLinks();
        foreach ($return as $tabk => $tabv)
        {
            if($tabk == 6)
            {
                $return[6] = array('url'    => 'area=managesteam',
                    'title'  => $this->lang->words['manage_steam'],
                    'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'managesteam' ? 1 : 0,
                    'area'   => 'managesteam'
                );
                $return[7] = $tabv;
            }
            elseif($tabk > 6)
            {
                $return[$tabk+1] = $tabv;   
            }
            else
            {
                $return[$tabk] = $tabv;    
            }   
        }

        return $return;
    }

    public function showForm( $current_area, $errors=array() )
    {
        if ( $current_area == 'managesteam' )
        {
            require_once( IPS_ROOT_PATH . 'sources/loginauth/steam/lib/steam_openid.php' );
            $data = array();
            $data['url'] = SteamSignIn::genUrl($this->settings['board_url'].'/interface/board/linksteam.php');
            $this->hide_form_and_save_button = 1;

            if($_GET['steam'] == 'process')
            {  
                $steam_id = SteamSignIn::validate();

                if ( $steam_id )
                {
                    /* Test locally */
                    $localMember = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'members', 'where' => "steamid='".$steam_id."'"));
                    $notify = '';
                    if ( $localMember['member_id'] && !$localMember['members_display_name'] )
                    {
                        IPSMember::remove( $localMember['member_id'] , false );
                        IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'steamid' => $steam_id ) ) );
                        $notify .= "&completed=1";
                    }
                    elseif( !$localMember['member_id'])
                    {
                        IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'steamid' => $steam_id ) ) );
                        $notify .= "&completed=1";
                    }
                    else
                    {
                        $notify .= "&error=true";
                    }
                }
                $this->registry->output->silentRedirect($this->settings['base_url'].'app=core&module=usercp&tab=core&area=managesteam'.$notify);
                exit();
            }
            //if($this->request['error']) $this->registry->output->showError( $this->lang->words['steam_already_linked'], '1');
            return $this->registry->getClass('output')->getTemplate('ucp')->manageSteam($data);
        }

        return parent::showForm( $current_area, $errors );
    }
} 
?>