<?php

class CRM_Groupreg_APIWrappers_Contact_IsGroupregRelated {

  /**
   * Change parameters so that output is limited to relationship-permissioned contacts.
   */
  public function fromApiInput($apiRequest) {
    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
    if ($userCid) {
      if ($isGroupregRelated = CRM_Utils_Array::value('isGroupregRelated', $apiRequest['params'])) {
        $baseCid = $this->getBaseCid($apiRequest, $userCid);
        $apiRequest['params']['id'] = $this->alterId($apiRequest, $baseCid);
        // CustomValue.get api requires "administer CiviCRM"; we are accessing
        // fields for contacts we have access to; therefore bypass permissions.
        if (isset($apiRequest['params']['api.CustomValue.get'])) {
          $apiRequest['params']['api.CustomValue.get']['check_permissions'] = FALSE;
        }
        // We're limiting to related contacts, but in fact the api will have its
        // own limitations, most notably blocking access to contacts if I don't
        // have 'view all contacts'. So we skip permissions checks.
        $apiRequest['params']['check_permissions'] = FALSE;
      }
    }
    return $apiRequest;
  }

  /**
   * Munges the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    // Append relationship details, since each of these contacts will be related
    // to the logged in user. (Yes, we do have to fetch those related contacts
    // again, which is redundant to self::fromApiInput(), but that's probably fine.)
    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
    if ($userCid) {
      if ($isGroupregRelated = CRM_Utils_Array::value('isGroupregRelated', $apiRequest['params'])) {
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
    if ($groupregRelatedOrgId = CRM_Utils_Array::value('groupregRelatedOrgId', $apiRequest['params'])) {
      $userRelatedOrgs = CRM_Groupreg_Util::getPermissionedContacts($userCid, 'Organization');
      if (array_key_exists($groupregRelatedOrgId, $userRelatedOrgs)) {
        $baseCid = $groupregRelatedOrgId;
      }
    }
    else {
      $baseCid = $userCid;
    }
    return $baseCid;
  }

  private function alterId($apiRequest, $baseCid) {
    $contactType = CRM_Utils_Array::value('contact_type', $apiRequest['params']);
    $related = CRM_Groupreg_Util::getPermissionedContacts($baseCid, $contactType);
    $relatedCids = array_keys($related);
    $relatedCids[] = -1;

    $id = CRM_Utils_Array::value('id', $apiRequest['params']);
    // If no ID param is given, just use $relatedCids.
    if (empty($id)) {
      return array('IN' => $relatedCids);
    }
    // If a single ID param is given, make sure it's in $relatedCids.
    if (
      is_numeric($id)
      && in_array($id, $relatedCids)
    ) {
      return $id;
    }
    // If an 'IN' array of IDs is given, make sure they're all in $relatedCids.
    if (
      is_array($id)
      && is_array($id['IN'])
    ) {
      $validIds = array_intersect($id['IN'], $relatedCids);
      return array('IN' => $validIds);
    }

    // If we're still here, return -1; this will give them nothing.
    return -1;
  }

}
