<?php
$kcurl = $keycloakConfig['provider']['baseUrl'] . '/realms/' . $keycloakConfig['provider']['realm'] . '/account/#/security/signingin';
$fields = [
    [
        'key' => __('ID'),
        'path' => 'id'
    ],
    [
        'key' => __('UUID'),
        'path' => 'uuid'
    ],
    [
        'key' => __('Username'),
        'path' => 'username'
    ],
    [
        'type' => 'generic',
        'key' => __('Email'),
        'path' => 'individual.email',
        'url' => '/individuals/view/{{0}}',
        'url_vars' => 'individual_id'
    ],
    [
        'type' => 'generic',
        'key' => __('Organisation'),
        'path' => 'organisation.name',
        'url' => '/organisations/view/{{0}}',
        'url_vars' => 'organisation.id'
    ],
    [
        'type' => 'generic',
        'key' => __('Role'),
        'path' => 'role.name',
        'url' => '/roles/view/{{0}}',
        'url_vars' => 'role.id'
    ],
    [
        'key' => __('First name'),
        'path' => 'individual.first_name'
    ],
    [
        'key' => __('Last name'),
        'path' => 'individual.last_name'
    ],
    [
        'key' => __('Alignments'),
        'type' => 'alignment',
        'path' => 'individual',
        'scope' => 'individuals'
    ]
];
if ($keycloakConfig['enabled'] && $loggedUser['id'] == $entity['id']) {
    $fields[] = [
        'type' => 'generic',
        'key' => __('Modify keycloak profile'),
        'path' => 'username',
        'url' => $kcurl,
        'requirements' => false
    ];
}
echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'data' => $entity,
        'fields' => $fields,
        'children' => [
            [
                'url' => '/AuthKeys/index?Users.id={{0}}',
                'url_params' => ['id'],
                'title' => __('Authentication keys')
            ],
            [
                'url' => '/EncryptionKeys/index?owner_id={{0}}',
                'url_params' => ['individual_id'],
                'title' => __('Encryption keys')
            ],
            [
                'url' => '/UserSettings/index?Users.id={{0}}',
                'url_params' => ['id'],
                'title' => __('User settings')
            ]
        ]
    ]
);
