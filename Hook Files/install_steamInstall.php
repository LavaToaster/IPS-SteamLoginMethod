<?php
class steamInstall
{
    public function install()
    {
        ipsRegistry::DB()->query("ALTER TABLE ".ipsRegistry::$settings['sql_tbl_prefix']."members ADD `steamid` VARCHAR(17) NULL");    
    }
    public function uninstall()
    {
        ipsRegistry::DB()->query("ALTER TABLE ".ipsRegistry::$settings['sql_tbl_prefix']."members DROP `steamid`");    
    }
}
?>
