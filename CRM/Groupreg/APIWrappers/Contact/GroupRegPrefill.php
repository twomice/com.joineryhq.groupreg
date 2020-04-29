<?php

class CRM_Groupreg_APIWrappers_Contact_GroupRegPrefill {

  /**
   * Change parameters so that output is limited to relationship-permissioned contacts.
   */
  public function fromApiInput($apiRequest) {
    if (isset($apiRequest['params']['api.CustomValue.get'])) {
      $apiRequest['params']['api.CustomValue.get']['check_permissions'] = FALSE;
    }
    // Note that while building our "groupregPrefillContactTypeContact" entityRef field,
    // CiviCRM will call the contact.get api with our groupregPrefillContactType=true param
    // AND with a specified contact ID, if the entityRef field has a default value
    // (as on some form reloads after validation failure). In this case we should
    // not change the parameters, and should ignore our groupregPrefillContactType=true
    // param. In such cases, overwriting it with our 'id IN []' param will cause
    // the field to be build wrongly (not sure exactly how or why), leading to endless
    // reload of the entityRef field via AJAX api. Only when `id` is missing
    // should we bother to insert our 'id IN []' param.
    if ($apiRequest['params']['id']) {
      return $apiRequest;
    }
    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
    if ($userCid) {
      $groupregPrefillContactType = CRM_Utils_Array::value('groupregPrefillContactType', $apiRequest['params']);
      if (
        $groupregPrefillContactType == 'Individual'
        || $groupregPrefillContactType == 'Organization'
      ) {
        $baseCid = $this->getBaseCid($apiRequest, $userCid);
        $related = CRM_Groupreg_Util::getPermissionedContacts($baseCid, NULL, NULL, $groupregPrefillContactType);
        $relatedCids = array_keys($related);
        $relatedCids[] = -1;
        $apiRequest['params']['id'] = ['IN' => $relatedCids];
        // We're limiting to related contacts, but in fact the api will have its
        // own limitations, most notably blocking access to contacts if I don't
        // have 'view all contacts'. So we skip permissions checks.
        $apiRequest['params']['check_permissions'] = FALSE;
        $apiRequest['params']['contact_type'] = $groupregPrefillContactType;
      }
    }
    return $apiRequest;
  }

  /**
   * Munges the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    // Append relationship details, since each of these contacts will be related
    // to the logged in user. (Actually have to fetch those related contacts
    // again, redundant to self::fromApiInput(), but that's probably fine.)
    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
    if ($userCid) {
      $groupregPrefillContactType = CRM_Utils_Array::value('groupregPrefillContactType', $apiRequest['params']);
      if (
        $groupregPrefillContactType == 'Individual'
        || $groupregPrefillContactType == 'Organization'
      ) {
        $baseCid = $this->getBaseCid($apiRequest, $userCid);
        $related = CRM_Contact_BAO_Relationship::getRelationship($baseCid, CRM_Contact_BAO_Relationship::CURRENT, 25, NULL, NULL, NULL, NULL, TRUE);
        $related = CRM_Utils_Array::rekey($related, 'cid');
        foreach ($result['values'] as &$value) {
          $cid = $value['contact_id'];
          $relationship = $related[$cid];
          $value['rtype'] = $relationship['rtype'];
          $value['relationship_type_id'] = $relationship['relationship_type_id'];
          $value['relationship_id'] = $relationship['id'];
        }
      }
    }
    return $result;
  }

  private function getBaseCid($apiRequest, $userCid) {
    $baseCid = 0;
    if ($groupregPrefillRelatedOrgId = CRM_Utils_Array::value('groupregPrefillRelatedOrgId', $apiRequest['params'])) {
      $userRelatedOrgs = CRM_Groupreg_Util::getPermissionedContacts($userCid, NULL, NULL, 'Organization');
      if (array_key_exists($groupregPrefillRelatedOrgId, $userRelatedOrgs)) {
        $baseCid = $groupregPrefillRelatedOrgId;
      }
    }
    else {
      $baseCid = $userCid;
    }
    return $baseCid;
  }

}
