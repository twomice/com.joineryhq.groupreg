<?php

class CRM_Groupreg_APIWrappers_Contact {
  /**
   * Change parameters so that output is limited to relationship-permissioned contacts.
   */
  public function fromApiInput($apiRequest) {
    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
    if ($userCid) {
      $related = CRM_Contact_BAO_Relationship::getRelationship($userCid, 3, 25, NULL, NULL, NULL, NULL, TRUE);
      $relatedCids = CRM_Utils_Array::collect('cid', $related);
      $apiRequest['params']['id'] = ['IN' => $relatedCids];
    }
    return $apiRequest;
  }

  /**
   * Munges the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    // Nothing to do here.
    return $result;
  }
}