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
        // Get a list of all current relationships for the baseContact.
        $baseCid = $this->getBaseCid($apiRequest, $userCid);
        $relatedContacts = CRM_Contact_BAO_Relationship::getRelationship($baseCid, CRM_Contact_BAO_Relationship::CURRENT, 25, NULL, NULL, NULL, NULL, TRUE);

        // Get a list of ids for relationship_type that are valid for selection in this use case,
        // according to the contact_type of the baseContact.
        $baseContactType = \Civi\Api4\Contact::get()
          ->addSelect('contact_type')
          ->addWhere('id', '=', $baseCid)
          ->execute()
          ->first()['contact_type'];
        $selectableRelationshipTypeIds = array_keys(CRM_Groupreg_Util::getRelationshipTypesForContactType($baseContactType));

        // Filter relatedContacts to only those relationships that are of valid types.
        $relatedContacts = array_filter($relatedContacts, function($relatedContact) use ($selectableRelationshipTypeIds){
          return in_array($relatedContact['relationship_type_id'], $selectableRelationshipTypeIds);
        });
        // Rekey these relationships to the related contactId. If any contact
        // has more than one relationship to baseContact, this will have the
        // effect of reducing the array length (to only one relationship per
        // related conact), but that is acceptable -- because the purpose of this
        // code is to provide one "defaul" membership type in the groupreg "additional
        // participant" form, and there can only be one default value; if there's
        // more than one current relationship of a valid type for this individual,
        // we only need one of those membership types, and we don't care which one
        // it is (or rather, we have no way to prefer one over another).
        $relatedContacts = CRM_Utils_Array::rekey($relatedContacts, 'cid');
        foreach ($result['values'] as &$value) {
          // For each of the contacts in the api results, append this relationship information.
          $cid = $value['contact_id'];
          $relationship = $relatedContacts[$cid];
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
