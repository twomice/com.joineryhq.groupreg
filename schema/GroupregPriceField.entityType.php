<?php
use CRM_Groupreg_ExtensionUtil as E;

return [
  'name' => 'GroupregPriceField',
  'table' => 'civicrm_groupreg_price_field',
  'class' => 'CRM_Groupreg_DAO_GroupregPriceField',
  'getInfo' => fn() => [
    'title' => E::ts('Groupreg Price Field'),
    'title_plural' => E::ts('Groupreg Price Fields'),
    'description' => E::ts('Stores per-field settings for groupreg extension'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique GroupregPriceField ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'price_field_id' => [
      'title' => E::ts('Price Field ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Price Field'),
      'entity_reference' => [
        'entity' => 'PriceField',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_hide_non_participant' => [
      'title' => E::ts('Hide from non-participating primary registrants?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
  ],
];
