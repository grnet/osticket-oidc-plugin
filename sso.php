<?php

use ohmy\Auth2;

function isEmailAllowed($email, $domains_string) {
    $email_domain = end(explode('@', $email, 2));
    $domains = explode(',', $domains_string);

    foreach ($domains as $domain) {
        if (strcasecmp($email_domain, trim($domain)) === 0) {
            return TRUE;
        }
    }

    return strlen(trim($domains_string)) === 0;
}

class SsoAuth {
    var $config;
    var $access_token;

    function __construct($config) {
        $this->config = $config;
    }

    function triggerAuth() {
        global $ost;
        $self = $this;
        $scpf = "off";

        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        if (str_contains($actual_link, '/scp') || str_contains($actual_link, 'scpf=on')){
            $scpf = "on";
        }

        return Auth2::legs(3)
            ->set('id', $this->config->get('g-client-id'))
            ->set('secret', $this->config->get('g-client-secret'))
            ->set('redirect', rtrim($ost->getConfig()->getURL(), '/') . '/login.php?do=ext&bk=sso.client' . '&scpf=' . $scpf)
            ->set('scope', 'profile email')

            ->authorize($this->config->get('auth-url'))
            ->access($this->config->get('token-url'))

            ->finally(function($data) use ($self) {
        $self->access_token = $data['access_token'];  

            });

    }
}

class SsoStaffAuthBackend extends ExternalStaffAuthenticationBackend {
    static $id = "sso";
    static $name = "SSO";

    static $sign_in_image_url = false;
    static $service_name = "SSO";

    var $config;

    function __construct($config) {
        $this->config = $config;
        $this->sso = new SsoAuth($config);
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            if (!isEmailAllowed($_SESSION[':oauth']['email'], $this->config->get(
                'g-allowed-domains-agents')))
                $_SESSION['_staff']['auth']['msg'] = 'Login with this email address is not permitted';
            else if (($staff = StaffSession::lookup(array('email' => $_SESSION[':oauth']['email'])))
                && $staff->getId()
            ) {
                if (!$staff instanceof StaffSession) {
                    // osTicket <= v1.9.7 or so
                    $staff = new StaffSession($user->getId());
                }
                return $staff;
            }
            else
                $_SESSION['_staff']['auth']['msg'] = 'Have your administrator create a local account';
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }


    function triggerAuth() {
        parent::triggerAuth();
        $sso = $this->sso->triggerAuth();

        $sso->GET(
            $this->config->get('info-url') . "?access_token="
                . $this->sso->access_token)
            ->then(function($response) {
                require_once INCLUDE_DIR . 'class.json.php';
                if ($json = JsonDataParser::decode($response->text))
                    $_SESSION[':oauth']['email'] = $json['email'];
                Http::redirect(ROOT_PATH . 'scp');
            }
        );
    }
}

class SsoClientAuthBackend extends ExternalUserAuthenticationBackend {
    static $id = "sso.client";
    static $name = "SSO";

    static $sign_in_image_url = false;
    static $service_name = "SSO";

    function __construct($config) {
        $this->config = $config;
        $this->sso = new SsoAuth($config);
    }

    function supportsInteractiveAuthentication() {
        return false;
    }

    function signOn() {
        global $errors;
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            if (!isEmailAllowed($_SESSION[':oauth']['email'], $this->config->get(
                'g-allowed-domains-clients')))
                $errors['err'] = 'Login with this email address is not permitted';
            else if (($acct = ClientAccount::lookupByUsername($_SESSION[':oauth']['email']))
                    && $acct->getId()
                    && ($client = new ClientSession(new EndUser($acct->getUser()))))
                return $client;
            else if (isset($_SESSION[':oauth']['profile'])) {
                // TODO: Prepare ClientCreateRequest
                $profile = $_SESSION[':oauth']['profile'];
                $info = array(
                    'email' => $_SESSION[':oauth']['email'],
                    'name' => $profile['displayName'],
                );
                return new ClientCreateRequest($this, $info['email'], $info);
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }

    function triggerAuth() {
        require_once INCLUDE_DIR . 'class.json.php';
        $sso = $this->sso->triggerAuth();

        $token = $this->sso->access_token;
        $url = $this->config->get('info-url');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($resp, true);

        $_SESSION[':oauth']['profile'] = $json;
        $_SESSION[':oauth']['email'] = $json['email'];


        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        if (str_contains($actual_link, 'scpf=on' )){
            Http::redirect(ROOT_PATH .  'scp');
        }else{
            Http::redirect(ROOT_PATH .  'login.php');
        }


    }
}