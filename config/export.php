<?php

return [
    'users' => [
        'columns' => [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'birthday' => 'Birthday',
        ],
        'file_path' => 'exports/users.xlsx',
        'default_password' => 'password123',
    ],
    'roles' => [
        'columns' => [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            'description' => 'Description',
        ],
        'file_path' => 'exports/roles.xlsx',
    ],
    'messages' => [
        'success' => [
            'create' => 'Row %d: Successfully created record with ID %d',
            'update' => 'Row %d: Successfully updated record with ID %d',
        ],
        'error' => [
            'duplicate' => 'Row %d: Record with ID %d already exists',
            'unknown' => 'Row %d: An error occurred while processing the record',
        ],
    ],
];