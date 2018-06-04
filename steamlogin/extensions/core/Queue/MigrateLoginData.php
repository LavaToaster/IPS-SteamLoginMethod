<?php

namespace IPS\steamlogin\extensions\core\Queue;

use function array_chunk;
use function array_merge;
use function array_unique;
use function count;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Background Task: Rebuild non-content item editor content
 */
class _MigrateLoginData
{
    /**
     * @brief Number of members to covert per cycle
     */
    public $rebuild	= 100;

    /**
     * Parse data before queuing
     *
     * @param	array	$data
     * @return	array
     */
    public function preQueueData( $data )
    {
        // Code originally wrote by Aiwa. If you're somehow seeing this, then you probably care about writing a
        // something to integrate with Steam, so you should probably give his app a look over. ;)
        // https://invisioncommunity.com/files/file/8170-steam-profile-integration/

        try {
            $membersWithDuplicateIds = [];

            $query = \IPS\Db::i()->select(
                'COUNT(member_id) as total, GROUP_CONCAT(member_id) as member_ids, steamid',
                'core_members',
                null,
                null,
                null,
                'steamid',
                'total > 1',
                null
            );

            foreach ($query as $row) {
                $membersWithDuplicateIds[] = explode(',', $row['member_ids']);
            }

            $membersWithDuplicateIds = array_merge(...$membersWithDuplicateIds);

            if (count($membersWithDuplicateIds) > 0) {
                $chunkedMemberIds = array_chunk($membersWithDuplicateIds, 1000);

                foreach ($chunkedMemberIds as $memberIds) {
                    // Update members in batches of 1000
                    \IPS\Db::i()->update(
                        'core_members',
                        ['steamid' => null],
                        ['member_id' => $memberIds]
                    );
                }
            }
        } catch (\UnderflowException $e) {
            // No duplicates, your users are smarter than the average bear!
        }

        return $data;
    }

    /**
     * Run Background Task
     *
     * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
     * @param	int							$offset	Offset
     * @return	int							New offset
     * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
     */
    public function run( $data, $offset )
    {
        if ($data['total'] === 0) {
            throw new \IPS\Task\Queue\OutOfRangeException;
        }

        $method = \IPS\Db::i()
            ->select(
                'login_classname',
                'core_login_methods',
                ['login_classname = ?', 'IPS\steamlogin\sources\Login\Steam']
            )
            ->first();

        $select = 'm.*';
        $where = 'm.steamid > 0';

        $query = \IPS\Db::i()->select(
            $select,
            ['core_members', 'm'],
            $where,
            'm.member_id ASC',
            [$offset, $this->rebuild],
            null,
            null,
            '111'
        );

        $insert = array();
        foreach ($query as $row) {
            $member = \IPS\Member::constructFromData($row);
            $insert[] = array(
                'token_login_method' => $method['id'],
                'token_member'       => $member->member_id,
                'token_identifier'   => $member->steamid,
                'token_linked'       => 1,
            );
            ++$offset;
        }

        \IPS\Db::i()->insert('core_login_links', $insert);

        $count = $query->count(true);

        if ($count <= $offset) {
            // Conversion complete
            throw new \IPS\Task\Queue\OutOfRangeException;
        }

        return $offset;
    }

    /**
     * Get Progress
     *
     * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
     * @param	int						$offset	Offset
     * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
     * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
     */
    public function getProgress( $data, $offset )
    {
        return [
            'text' => \IPS\Member::loggedIn()->language()->addToStack( 'login_steam_migrating_login_data', FALSE ),
            'complete' => $data['count'] ? round( 100 / $data['count'] * $offset, 2 ) : 100
        ];
    }
}
