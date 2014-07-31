<?php

class AuthCAS extends AuthPluginBase {

    protected $storage = 'DbStorage';
    static protected $description = 'Authentication on CAS';
    static protected $name = 'CAS';
    protected $settings = array(
        'server' => array(
            'type' => 'string',
            'label' => 'CAS server hostname',
            'default' => 'idp.upce.cz'
        ),
        'port' => array(
            'type' => 'int',
            'label' => 'CAS server port',
            'default' => '443'
        ),
        'context' => array(
            'type' => 'string',
            'label' => 'CAS server context',
            'default' => 'jasig'
        ),
    );

    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);

        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('beforeLogin');
        $this->subscribe('newUserSession');
        $this->subscribe('afterLogout');
    }

    private function initCas() {
        require_once('CAS/CAS.php');
        Yii::registerAutoloader('CAS_autoload');
        phpCAS::setDebug('/www/pruzkum/log-custom/cas.log');
        phpCAS::client(CAS_VERSION_2_0, $this->get('server'), (int) $this->get('port'), $this->get('context'));
        phpCAS::setNoCasServerValidation();
    }

    public function beforeLogin() {
        $this->initCas();
        if (!phpCAS::checkAuthentication()) {
            phpCAS::forceAuthentication();
            exit;
        }
        $sUser = phpCAS::getUser();

        $aUserMappings = $this->api->getConfigKey('auth_webserver_user_map', array());
        if (isset($aUserMappings[$sUser])) {
            $sUser = $aUserMappings[$sUser];
        }
        $this->setUsername($sUser);
        $this->setAuthPlugin(); // This plugin handles authentication, halt further execution of auth plugins
    }

    public function newUserSession() {
        /* @var $identity LSUserIdentity */
        $sUser = $this->getUserName();

        $oUser = $this->api->getUserByName($sUser);
        if (is_null($oUser)) {
            if (function_exists("hook_get_auth_webserver_profile")) {
                // If defined this function returns an array
                // describing the default profile for this user
                $aUserProfile = hook_get_auth_webserver_profile($sUser);
            } elseif ($this->api->getConfigKey('auth_webserver_autocreate_user')) {
                $aUserProfile = $this->api->getConfigKey('auth_webserver_autocreate_profile');
            }
        } else {
            $this->setAuthSuccess($oUser);
            return;
        }

        if ($this->api->getConfigKey('auth_webserver_autocreate_user') && isset($aUserProfile) && is_null($oUser)) { // user doesn't exist but auto-create user is set
            $oUser = new User;
            $oUser->users_name = $sUser;
            $oUser->password = hash('sha256', createPassword());
            $oUser->full_name = $aUserProfile['full_name'];
            $oUser->parent_id = 1;
            $oUser->lang = $aUserProfile['lang'];
            $oUser->email = $aUserProfile['email'];

            if ($oUser->save()) {
                $permission = new Permission;
                $permission->setPermissions($oUser->uid, 0, 'global', $this->api->getConfigKey('auth_webserver_autocreate_permissions'), true);

                // read again user from newly created entry
                $this->setAuthSuccess($oUser);
                return;
            } else {
                $this->setAuthFailure(self::ERROR_USERNAME_INVALID);
            }
        }
    }

    public function afterLogout() {
        
    }

}
