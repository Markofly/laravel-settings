<?php

return [

    'settings_table_name' => 'markofly_settings', // Settings table name

    'use_caching' => true, // Use settings value caching

    'cache_prefix' => 'markofly_settings', // Cache prefix

    /* Default settings values */
    'fields' => [
        'site_name' => [
            'default' => 'Laravel 5',
            'group' => [
                'label' => 'Site settings',
                'slug' => 'site-settings',
            ],
        ],
    ],

];
