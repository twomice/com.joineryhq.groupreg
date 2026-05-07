<?php

/**
 * Description of Hook
 */
class CRM_Groupreg_Hook {

  public static function alterOrgs(&$orgs, &$disabledOrgCids) {
    $null = NULL;
    CRM_Utils_Hook::singleton()->invoke(
      ['orgs', 'disabledOrgCids'],
      $orgs,
      $disabledOrgCids,
      $null,
      $null,
      $null,
      $null,
      'civicrm_groupreg_alterOrgs'
    );
  }

  public static function alterIndividuals(&$individuals) {
    $null = NULL;
    CRM_Utils_Hook::singleton()->invoke(
      ['individuals'],
      $individuals,
      $null,
      $null,
      $null,
      $null,
      $null,
      'civicrm_groupreg_alterIndividuals'
    );
  }

  public static function validateAdditionalParticipant($fields, &$errors) {
    $null = NULL;
    CRM_Utils_Hook::singleton()->invoke(
      ['fields', 'errors'],
      $fields,
      $errors,
      $null,
      $null,
      $null,
      $null,
      'civicrm_groupreg_validateAdditionalParticipant'
    );
  }

}
