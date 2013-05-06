<?php
class AuthJasigCAS extends AuthPluginBase
{
  protected $storage = 'DbStorage';    
  static protected $description = 'Auth: Jasig CAS Authentication';
  static protected $name = 'Jasig CAS';

  protected $settings = array(
    'cas_host' => array(
      'type' => 'string',
      'label' => 'CAS server hostname (e.g. sso.example.com)'
    ),
    'cas_context' => array(
      'type' => 'string',
      'label' => 'CAS servlet context (e.g. /cas)'
    ),
    'cas_port' => array(
      'type' => 'int',
      'label' => 'Port of CAS server (e.g. 443)'
    ),
    'cert_path' => array(
      'type' => 'string',
      'label' => 'Path to the CA chain that issued the cas server certificate (e.g. /var/www/limesurvey/certs.pem)'
    ),
    'ignore_cert' => array(
      'type' => 'checkbox',
      'label' => 'Ignore CAS server certificate validation (NOT RECOMMENDED FOR PRODUCTION USE!)'
    ),
  );

  public function __construct(PluginManager $manager, $id) {
    parent::__construct($manager, $id);
    $this->subscribe('beforeLogin');
    $this->subscribe('newUserSession');
  }

  public function beforeLogin() {
    $cas_host = $this->get('cas_host');
    $cas_port = (int)$this->get('cas_port');
    $cas_context = $this->get('cas_context');
    
    require_once 'phpCAS/CAS.php';
    phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
    
    if ($this->get('ignore_cert', null, null, false)) {
      phpCAS::setNoCasServerValidation();
    } else {
      $cas_server_ca_cert_path = $this->get($cas_server_ca_cert_path);
      phpCAS::setCasServerCACert($cas_server_ca_cert_path);
    }
    
    phpCAS::forceAuthentication();
  
    $this->setUsername(phpCAS::getUser());
    $this->setAuthPlugin(); // This plugin handles authentication, halt further execution of auth plugins
  }

  public function newUserSession() {
    $username = $this->getUsername();
    $user = $this->api->getUserByName($username);
    if ($user !== null) {
      $this->setAuthSuccess($user);
      return;
    }
    $this->setAuthFailure(self::ERROR_USERNAME_INVALID);
  }
}