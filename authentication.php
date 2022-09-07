<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class OauthAuthPlugin extends Plugin {
    var $config_class = "OauthPluginConfig";

    function bootstrap() {
        $config = $this->getConfig();
        # ----- SSO Sign-In ---------------------
        $sso = $config->get('g-enabled');
        if (in_array($sso, array('all', 'staff'))) {
            require_once('sso.php');
            StaffAuthenticationBackend::register(
                new SsoStaffAuthBackend($this->getConfig()));
        }
        if (in_array($sso, array('all', 'client'))) {
            require_once('sso.php');
            UserAuthenticationBackend::register(
                new SsoClientAuthBackend($this->getConfig()));
        }
    }
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
    dirname(__file__).'/lib'));
$loader->register();
