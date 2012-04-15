<?php
/**
 * @author Adam Lavin (Lavoaster) (Thanks PW Atticus and Micheal from IPS Forums)
 * @copyright 2012
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
class steamHandleData 
{
    public function handleData( $data )
    {
        /* Add additional fields to be queried */
        $data['members'] = array_merge( $data['members'], array( 'steamid' ) );
        
        /* Return */
        return $data;
    }
}
?>
