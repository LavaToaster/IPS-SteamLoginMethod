<?php
/**
 * @brief		Steam Login Application Class
 * @author		<a href=''>Adam Lavin</a>
 * @copyright	(c) 2018 Adam Lavin
 * @package		Invision Community
 * @subpackage	Steam Login
 * @since		03 Jun 2018
 * @version
 */

namespace IPS\steamlogin;

/**
 * Steam Login Application Class
 */
class _Application extends \IPS\Application
{
    /**
     * {@inheritdoc}
     */
    public function installOther()
    {
        if (!\IPS\Db::i()->checkForColumn('core_members', 'steamid')) {
            \IPS\Db::i()->addColumn('core_members', [
                'name' => 'steamid',
                'type' => 'VARCHAR',
                'length' => 17
            ]);
        }

        if (!\IPS\Db::i()->checkForIndex('core_members', 'steamid')) {
            \IPS\Db::i()->addIndex('core_members', array(
                'name' => 'steamid',
                'type' => 'key',
                'columns' => array('steamid')
            ));
        }

        // Attempt to migrate from old hook
        try {
            $data = \IPS\Db::i()
                ->select(
                    'login_classname',
                    'core_login_methods',
                    ['login_classname = ?', 'IPS\Login\Steam']
                )
                ->first();

            // Add some new fields to it
            $data['login_settings']['show_in_ucp'] = 'always';
            $data['login_settings']['update_name_changes'] = 'disabled';

            // Update the login method class
            $data['login_classname'] = 'IPS\steamlogin\sources\Login\Steam';

            $langData = [
                'lang_id'      => 1,
                'word_app'     => 'core',
                'word_key'     => 'login_method_' . $data['id'],
                'word_default' => 'Steam',
                'word_custom'  => 'Steam',
                'word_js'      => 0,
                'word_export'  => 0
            ];

            \IPS\Db::i()->insert('core_sys_conf_settings', $langData);
            \IPS\Db::i()->update('core_login_methods', $data, ['id = ?', $data['id']]);

            \IPS\Task::queue( 'steamlogin', 'MigrateLoginData', [], 2 );
        } catch (\UnderflowException $e) {
            // If there isn't, then no worries. They can add the handler from the login handlers area.
        }
    }
}
