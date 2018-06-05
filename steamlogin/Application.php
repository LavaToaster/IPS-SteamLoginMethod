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

use function json_decode;
use function json_encode;
use function var_dump;

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
            \IPS\Db::i()->addIndex('core_members', [
                'name' => 'steamid',
                'type' => 'key',
                'columns' => ['steamid']
            ]);
        }

        try {
            // Attempt to delete old plugin.
            $this->removeOldPlugin();

            // Attempt to migrate login method settings from old plugin.
            $this->convertOldSettings();

            // Queue other data migrations.
            $this->startQueueTasks();
        } catch (\UnderflowException $e) {
            // If there isn't, then no worries. They can add the handler from the login handlers area.
        }
    }

    protected function removeOldPlugin()
    {
        $plugins = \IPS\Plugin::plugins();

        foreach ($plugins as $id => $plugin) {
            if ($plugin->name !== 'Sign in through Steam') {
                continue;
            }

            if ($plugin->author !== 'Adam Lavin') {
                continue;
            }

            $plugin->delete();
            break;
        }
    }

    protected function convertOldSettings()
    {
        $data = \IPS\Db::i()
            ->select(
                '*',
                'core_login_methods',
                ['login_classname = ?', 'IPS\Login\Steam']
            )
            ->first();

        $data['login_settings'] = json_decode($data['login_settings'], true);

        // Add some new fields to it
        $data['login_settings']['show_in_ucp'] = 'always';
        $data['login_settings']['update_name_changes'] = 'disabled';

        $data['login_settings'] = json_encode($data['login_settings']);

        // Update the login method class
        $data['login_classname'] = 'IPS\steamlogin\sources\Login\Steam';

        \IPS\Db::i()->update('core_login_methods', $data, ['login_id = ?', $data['login_id']]);
    }

    protected function startQueueTasks()
    {
        \IPS\Task::queue('steamlogin', 'MigrateLoginData', [], 2);
    }
}
