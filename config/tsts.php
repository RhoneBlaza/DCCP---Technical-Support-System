<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default settings (seeded into the `settings` table on first run).
    |--------------------------------------------------------------------------
    | These values are the source of truth for the SettingsSeeder and are used
    | by SettingsService as fallbacks when a setting row is missing.
    |
    */

    'default_settings' => [
        'organization_name' => ['value' => 'Data Center College of the Philippines - Bangued', 'type' => 'string'],
        'system_name' => ['value' => 'Tech Support Ticketing System', 'type' => 'string'],
        'system_short_name' => ['value' => 'TSTS', 'type' => 'string'],
        'support_email' => ['value' => 'itsupport@dccp-bangued.edu.ph', 'type' => 'string'],
        'support_phone' => ['value' => '', 'type' => 'string'],
        'ticket_prefix' => ['value' => 'DCCP', 'type' => 'string'],
        'default_priority_id' => ['value' => '', 'type' => 'integer'],
        'max_attachment_kb' => ['value' => '5120', 'type' => 'integer'],
        'allowed_attachment_extensions' => ['value' => ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'txt'], 'type' => 'json'],
        'max_attachments_per_message' => ['value' => '5', 'type' => 'integer'],
        'auto_close_days' => ['value' => '5', 'type' => 'integer'],
        'audit_retention_days' => ['value' => '365', 'type' => 'integer'],
        'notification_retention_days' => ['value' => '90', 'type' => 'integer'],
        'id_image_retention_days' => ['value' => '0', 'type' => 'integer'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hard-coded attachment allowlist.
    |--------------------------------------------------------------------------
    | Admins may only narrow `allowed_attachment_extensions` to a subset of
    | these. Executable / script types can never be enabled from Settings.
    |
    */

    'attachment_extension_allowlist' => ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'txt'],

    /*
    |--------------------------------------------------------------------------
    | Fixed badge color palette for ticket statuses / priorities.
    |--------------------------------------------------------------------------
    */

    'status_colors' => [
        'gray', 'blue', 'indigo', 'teal', 'green', 'yellow', 'orange', 'red', 'rose', 'purple',
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate ticket detection
    |--------------------------------------------------------------------------
    | A requester filing the same request for the same category twice in quick
    | succession is almost always a double submit rather than a second problem.
    | The match is a soft warning: the requester may still create the ticket,
    | but has to acknowledge the near-identical open ticket first.
    |
    | Set `enabled` to false to switch the warning off entirely.
    |
    */

    'duplicate_detection' => [
        'enabled' => true,
        'window_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reasonable per-page choices for tables.
    |--------------------------------------------------------------------------
    */

    'per_page_choices' => [15, 25, 50],
];
