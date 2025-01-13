<?php
use CRM_Groupreg_ExtensionUtil as E;

return [
  'name' => 'GroupregEvent',
  'table' => 'civicrm_groupreg_event',
  'class' => 'CRM_Groupreg_DAO_GroupregEvent',
  'getInfo' => fn() => [
    'title' => E::ts('Groupreg Event'),
    'title_plural' => E::ts('Groupreg Events'),
    'description' => E::ts('Stores per-event settings for groupreg extension'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Uniq identifier'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_id' => [
      'title' => E::ts('Event ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Event'),
      'entity_reference' => [
        'entity' => 'Event',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_hide_not_you' => [
      'title' => E::ts('Hide "Not You" message?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => FALSE,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
    ],
    'related_contact_tag_id' => [
      'title' => E::ts('Tag ID to apply to contacts marked as additional participants.'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Tag'),
      'default' => NULL,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
      'entity_reference' => [
        'entity' => 'Tag',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_prompt_related' => [
      'title' => E::ts('Prompt with related individuals on Additional Partipant forms?'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'default' => 0,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
    ],
    'is_require_existing_contact' => [
      'title' => E::ts('Require selection of existing contact?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => FALSE,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
    ],
    'is_primary_attending' => [
      'title' => E::ts('Primary participant is attendee'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'default' => 1,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
    ],
    'nonattendee_role_id' => [
      'title' => E::ts('Non-attending participant role'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Pseudo-FK to civicrm_option_value for participant_role'),
      'default' => 1,
      'usage' => [
        constant(fn() => 'import'),
        'export',
        'duplicate_matching',
      ],
    ],
  ],
];
