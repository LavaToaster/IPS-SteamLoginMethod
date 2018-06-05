<?php

namespace IPS\steamlogin\sources\Login;

use DateInterval;
use OutOfRangeException;
use UnderflowException;

class _Steam extends \IPS\Login\Handler
{
    use \IPS\Login\Handler\ButtonHandler;

    /**
     * {@inheritdoc}
     */
    public static $allowMultiple = false;

    /**
     * Cached steam user data
     */
    protected $_cachedUserData = [];

    /**
     * {@inheritdoc}
     */
    public function authenticateButton(\IPS\Login $login)
    {
        /* If we haven't been redirected back, redirect the user to external site */
        if (!isset(\IPS\Request::i()->openid_ns)) {
            $returnUrl = $login->url->setQueryString([
                '_processLogin' => $this->id,
                'csrfKey'       => \IPS\Session::i()->csrfKey,
                'ref'           => \IPS\Request::i()->ref,
            ]);

            $redirectTo = \IPS\Http\Url::external('https://steamcommunity.com/openid/login')->setQueryString([
                'openid.ns'         => 'http://specs.openid.net/auth/2.0',
                'openid.mode'       => 'checkid_setup',
                'openid.return_to'  => $returnUrl,
                'openid.realm'      => (string)\IPS\Http\Url::internal('', 'none'),
                'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
                'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            ]);

            \IPS\Output::i()->redirect($redirectTo);
        }

        $steamUserId = $this->validateAndGetSteamId();

        /* Find their local account if they have already logged in using this method in the past */
        try {
            $link = \IPS\Db::i()->select('*', 'core_login_links', ['token_login_method=? AND token_identifier=?', $this->id, $steamUserId])->first();
            $member = \IPS\Member::load($link['token_member']);

            /* If the user never finished the linking process, or the account has been deleted, discard this token */
            if (!$link['token_linked'] || !$member->member_id) {
                \IPS\Db::i()->delete('core_login_links', ['token_login_method=? AND token_member=?', $this->id, $link['token_member']]);
                throw new UnderflowException();
            }

            /* ... and return the member object */
            return $member;
        } catch (UnderflowException $e) {
        }

        /* Otherwise, we need to either create one or link it to an existing one */
        try {
            /* If the user is setting this up in the User CP, they are already logged in. Ask them to reauthenticate to link those accounts */
            if ($login->type === \IPS\Login::LOGIN_UCP) {
                $exception = new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::MERGE_SOCIAL_ACCOUNT);
                $exception->handler = $this;
                $exception->member = $login->reauthenticateAs;
                throw $exception;
            }

            $steamUserData = [
                'personaname' => null,
            ];

            if ($this->settings['use_steam_name'] && $this->settings['api_key']) {
                $steamUserData = $this->_userData($steamUserId);
            }

            /* Try to create one. NOTE: Invision Community will automatically throw an exception which we catch below if $email matches an existing account, if registration is disabled, or if Spam Defense blocks the account creation */
            $member = $this->createAccount($steamUserData['personaname']);

            /* If we're still here, a new account was created. Store something in core_login_links so that the next time this user logs in, we know they've used this method before */
            \IPS\Db::i()->insert('core_login_links', [
                'token_login_method' => $this->id,
                'token_member'       => $member->member_id,
                'token_identifier'   => $steamUserId,
                'token_linked'       => 1,
            ]);

            /* Log something in their history so we know that this login handler created their account */
            $member->logHistory('core', 'social_account', [
                'service'      => static::getTitle(),
                'handler'      => $this->id,
                'account_id'   => $steamUserId,
                'account_name' => $steamUserData['personaname'],
                'linked'       => TRUE,
                'registered'   => TRUE
            ]);

            $member->steamid = $steamUserId;

            /* Set up syncing options. NOTE: See later steps of the documentation for more details - it is fine to just copy and paste this code */
            if ($syncOptions = $this->syncOptions($member, TRUE)) {
                $profileSync = [];
                foreach ($syncOptions as $option) {
                    $profileSync[$option] = ['handler' => $this->id, 'ref' => NULL, 'error' => NULL];
                }
                $member->profilesync = $profileSync;
            }

            $member->save();

            return $member;
        } catch (\IPS\Login\Exception $exception) {
            /* If the account creation was rejected because there is already an account with a matching email address
                make a note of it in core_login_links so that after the user reauthenticates they can be set as being
                allowed to use this login handler in future */
            if ($exception->getCode() === \IPS\Login\Exception::MERGE_SOCIAL_ACCOUNT) {
                \IPS\Db::i()->insert('core_login_links', [
                    'token_login_method' => $this->id,
                    'token_member'       => $exception->member->member_id,
                    'token_identifier'   => $steamUserId,
                    'token_linked'       => 0,
                ]);
            }

            throw $exception;
        }
    }

    /**
     * Validates Steam OpenID request
     *
     * @return bool|string
     */
    protected function validateAndGetSteamId()
    {
        $params = [
            'openid.ns'           => 'http://specs.openid.net/auth/2.0',
            'openid.assoc_handle' => \IPS\Request::i()->openid_assoc_handle,
            'openid.signed'       => \IPS\Request::i()->openid_signed,
            'openid.sig'          => \IPS\Request::i()->openid_sig,
            'openid.mode'         => 'check_authentication'
        ];

        // Get all the params that were sent back and resend them for validation
        foreach (explode(',', $params['openid.signed']) as $item) {
            // First some security checks, ensure the param exists before attempting to call it
            $parameterName = 'openid_' . str_replace('.', '_', $item);

            if (!isset(\IPS\Request::i()->$parameterName)) {
                continue;
            }

            $params['openid.' . $item] = \IPS\Request::i()->$parameterName;
        }

        // Validate whether it's true and if we have a good ID
        preg_match('#^https://steamcommunity.com/openid/id/(\d{17,25})#', \IPS\Request::i()->openid_claimed_id, $matches);
        $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

        try {
            $response = (string)\IPS\Http\Url::external('https://steamcommunity.com/openid/login')->request()->post($params);
        } catch (\IPS\Http\Request\Exception $e) {
            throw new \IPS\Login\Exception('login_steam_openid_connection_failure', \IPS\Login\Exception::NO_ACCOUNT);

        }

        // Return our final value
        $isValid = preg_match('/is_valid\s*:\s*true/i', $response) && ($steamID64 !== 0);

        if (!$isValid) {
            throw new \IPS\Login\Exception('login_steam_openid_validation_failure', \IPS\Login\Exception::NO_ACCOUNT);
        }

        return $isValid ? $steamID64 : false;
    }

    /**
     * Get steam user data
     *
     * @param $steamUserId
     * @param bool $acpSettingTest
     * @return array
     * @throws \Exception
     */
    protected function _userData(
        $steamUserId,
        $acpSettingTest = false
    ) {
        $cacheKey = 'steamuser.' . $steamUserId;

        if (!isset($this->_cachedUserData[$steamUserId])) {
            try {
                // Attempt to retrieve it from cache first
                $steamUserData = \IPS\Data\Cache::i()->getWithExpire($cacheKey, true);
            } catch (OutOfRangeException $e) {
                try {
                    $response = \IPS\Http\Url::external("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->settings['api_key']}&steamids={$steamUserId}")
                        ->request()
                        ->get()
                        ->decodeJson();

                    if ($response === null) {
                        throw new \IPS\Login\Exception($acpSettingTest ? 'login_steam_settings_api_connection_failure' : 'login_steam_api_config_failure', \IPS\Login\Exception::NO_ACCOUNT);
                    }

                    $steamUserData = $response['response']['players'][0];

                    // Store it in cache
                    \IPS\Data\Cache::i()->storeWithExpire($cacheKey, $steamUserData, (new \IPS\DateTime())->add(new DateInterval('PT1H')), true);
                } catch (\IPS\Http\Request\Exception $e) {
                    throw new \IPS\Login\Exception('login_steam_api_connection_failure', \IPS\Login\Exception::NO_ACCOUNT);
                }
            }

            $this->_cachedUserData[$steamUserId] = $steamUserData;
        }

        return $this->_cachedUserData[$steamUserId];
    }

    /**
     * {@inheritdoc}
     */
    public static function getTitle()
    {
        return 'login_steam_method';
    }

    /**
     * {@inheritdoc}
     */
    public function syncOptions(
        \IPS\Member $member,
        $defaultOnly = FALSE
    ) {
        $options = [
            'photo'
        ];

        if (
            isset($this->settings['update_name_changes'])
            && (
                $this->settings['update_name_changes'] === 'optional'
                || $this->settings['update_name_changes'] === 'force'
            )
        ) {
            $options[] = 'name';
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function completeLink(
        \IPS\Member $member,
        $details
    ) {
        $data = \IPS\Db::i()->select( '*', 'core_login_links', ['token_login_method=? AND token_member=? AND token_linked=0', $this->id, $member->member_id], NULL, NULL, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first();

        $member->steamid = $data['token_identifier'];
        $member->save();

        parent::completeLink($member, $details);
    }

    /**
     * {@inheritdoc}
     */
    public function disassociate(\IPS\Member $member = NULL)
    {
        $member = $member ?: \IPS\Member::loggedIn();

        $member->steamid = null;
        $member->save();

        parent::disassociate($member);
    }

    /**
     * {@inheritdoc}
     */
    public function buttonColor()
    {
        return '#171a21';
    }

    /**
     * {@inheritdoc}
     */
    public function buttonIcon()
    {
        return 'steam'; // A fontawesome icon
    }

    /**
     * {@inheritdoc}
     */
    public function buttonText()
    {
        return 'login_steam'; // Create a language string for this
    }

    /**
     * {@inheritdoc}
     */
    public function acpForm()
    {
        return [
            'show_in_ucp'         => new \IPS\Helpers\Form\Radio(
                'login_handler_show_in_ucp',
                isset($this->settings['show_in_ucp']) ? $this->settings['show_in_ucp'] : 'always',
                true,
                [
                    'options' => [
                        'always'   => 'login_handler_show_in_ucp_always',
                        'loggedin' => 'login_handler_show_in_ucp_loggedin',
                        'disabled' => 'login_handler_show_in_ucp_disabled',
                    ],
                ]
            ),
            'api_key'             => new \IPS\Helpers\Form\Text(
                'login_steam_key',
                isset($this->settings['api_key']) ? $this->settings['api_key'] : '',
                true
            ),
            'use_steam_name'      => new \IPS\Helpers\Form\YesNo(
                'login_steam_name',
                isset($this->settings['use_steam_name']) ? $this->settings['use_steam_name'] : true,
                true,
                [
                    'togglesOn' => ['login_update_name_changes_inc_optional'],
                ]
            ),
            'update_name_changes' => new \IPS\Helpers\Form\Radio(
                'login_update_name_changes',
                isset($this->settings['update_name_changes']) ? $this->settings['update_name_changes'] : 'disabled',
                false,
                [
                    'options' => [
                        'force'    => 'login_update_changes_yes',
                        'optional' => 'login_update_changes_optional',
                        'disabled' => 'login_update_changes_no',
                    ]
                ],
                null,
                null,
                null,
                'login_update_name_changes_inc_optional'
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function testSettings()
    {
        $this->_userData(76561198010571702, true);
    }

    /**
     * {@inheritdoc}
     */
    public function userProfilePhoto(\IPS\Member $member)
    {
        if (!($link = $this->_link($member))) {
            throw new \IPS\Login\Exception(NULL, \IPS\Login\Exception::INTERNAL_ERROR);
        }

        $steamUserData = $this->_userData($link['token_identifier']);

        if ($steamUserData['avatarfull']) {
            return \IPS\Http\Url::external($steamUserData['avatarfull']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function userId(\IPS\Member $member)
    {
        if (!($link = $this->_link($member))) {
            throw new \IPS\Login\Exception(NULL, \IPS\Login\Exception::INTERNAL_ERROR);
        }

        return $link['token_identifier'];
    }

    /**
     * {@inheritdoc}
     */
    public function userProfileName(\IPS\Member $member)
    {
        if (!($link = $this->_link($member))) {
            throw new \IPS\Login\Exception(NULL, \IPS\Login\Exception::INTERNAL_ERROR);
        }

        return $this->_userData($link['token_identifier'])['personaname'];
    }

    /**
     * {@inheritdoc}
     */
    public function userLink(
        $identifier,
        $username
    ) {
        return \IPS\Http\Url::external('https://steamcommunity.com/profiles/' . $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function logoForDeviceInformation()
    {
        return \IPS\Theme::i()->resource('logos/login/Steam.svg', 'steamlogin', 'interface');
    }
}
