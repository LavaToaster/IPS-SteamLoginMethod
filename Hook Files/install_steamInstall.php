<?php
class steamInstall
{
    public function install()
    {
        if(!ipsRegistry::DB()->checkForField('steamid', 'members'))
        {
            ipsRegistry::DB()->addField('members', 'steamid', 'VARCHAR(17)', NULL);
        }
    }
    public function uninstall()
    {
        if(ipsRegistry::DB()->checkForField('steamid', 'members'))
        {
            ipsRegistry::DB()->dropField('members', 'steamid');
        }
    }
}
?>