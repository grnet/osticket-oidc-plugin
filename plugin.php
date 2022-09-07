<?php

return array(
    'id' =>             'auth:oath2', # notrans
    'version' =>        '1',
    'name' =>           /* trans */ 'Keycloak Authentication and Lookup',
    'author' =>         'GRNET',
    'description' =>    /* trans */ 'Provides a configurable authentication backend
        for authenticating staff and clients using an OAUTH2 server
        interface.',
    'plugin' =>         'authentication.php:OauthAuthPlugin',
    'requires' => array(
        "ohmy/auth" => array(
            "version" => "*",
            "map" => array(
                "ohmy/auth/src" => 'lib',
            )
        ),
    ),
);

?>
