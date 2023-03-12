<?php

class CRM_Groupreg_Util {
  const primaryIsAteendeeYes = 1;
  const primaryIsAteendeeNo = 0;
  const primaryIsAteendeeSelect = 2;

  const promptRelatedIndividual = 1;
  const promptRelatedOrganization = 2;

  /**
   * Get groupreg settings per event.
   *
   * @staticvar Array $eventSettings
   * @param Int $eventId
   * @return Array of settings.
   */
  public static function getEventSettings($eventId) {
    static $eventSettings = [];
    if (!in_array($eventId, $eventSettings)) {
      // Add fields to manage "primary is attending" for this registration.
      $eventSettings[$eventId] = \Civi\Api4\GroupregEvent::get()
        ->addWhere('event_id', '=', $eventId)
        ->setCheckPermissions(FALSE)
        ->execute()
        ->first();
    }
    return $eventSettings[$eventId];
  }

  /**
   * For any given contactType, return an api4 result of active relationship types
   * that can be created between that ContatType an an Individual.
   *
   * @param type $otherContactType
   * @return type
   */
  public static function getRelationshipTypesForContactType($otherContactType) {
    $relationshipTypes = \Civi\Api4\RelationshipType::get()
      ->addWhere('is_active', '=', 1)
      ->addClause('OR',
        [
          'AND', [
            ['contact_type_a', '=', 'Individual'],
            ['contact_type_b', '=', $otherContactType],
          ],
        ],
        [
          'AND', [
            ['contact_type_a', '=', $otherContactType],
            ['contact_type_b', '=', 'Individual'],
          ],
        ]
      )
      ->setCheckPermissions(FALSE)
      ->execute();
    return CRM_Utils_Array::rekey($relationshipTypes, 'id');
  }

  /**
   * Build a list of quickform select options for the 'relationship type' field
   * on groupreg "additional participants" form.
   *
   * @param type $otherContactType
   * @return type
   */
  public static function getRelationshipTypeOptions($otherContactType) {
    $relationshipTypeOptions = [];
    $relationshipTypes = self::getRelationshipTypesForContactType($otherContactType);

    foreach ($relationshipTypes as $relationshipType) {
      if ($relationshipType['contact_type_a'] == $otherContactType) {
        $relationshipTypeOptions["{$relationshipType['id']}_a_b"] = $relationshipType['label_a_b'];
      }
      if ($relationshipType['contact_type_b'] == $otherContactType) {
        $relationshipTypeOptions["{$relationshipType['id']}_b_a"] = $relationshipType['label_b_a'];
      }
    }
    // TODO: support limitation of these types (and possibly re-labeling of them)
    // in the UI.
    return $relationshipTypeOptions;
  }

  public static function hasPermissionedRelatedContact($cid, $contactType = NULL) {
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__])) {
      Civi::$statics[__CLASS__][__FUNCTION__] = [];
    }
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__][$cid][$contactType])) {
      Civi::$statics[__CLASS__][__FUNCTION__][$cid][$contactType] = FALSE;
      $relationships = CRM_Contact_BAO_Relationship::getRelationship($cid, CRM_Contact_BAO_Relationship::CURRENT, NULL, NULL, NULL, NULL, NULL, TRUE);
      if ($contactType) {
        foreach ($relationships as $relationshipId => $relationship) {
          if (
            $relationship['contact_type'] == $contactType
            && ($relationship["is_permission_{$relationship['rtype']}"] == 1)
          ) {
            Civi::$statics[__CLASS__][__FUNCTION__][$cid][$contactType] = TRUE;
            break;
          }
        }
      }
      else {
        Civi::$statics[__CLASS__][__FUNCTION__][$cid][$contactType] = !empty($relationships);
      }
    }
    return Civi::$statics[__CLASS__][__FUNCTION__][$cid][$contactType];
  }

  /**
   * Function to return list of permissioned contacts for a given contact and relationship type.
   * Copied and modified from CRM_Contact_BAO_Relationship::getPermissionedContacts(), with
   * improvements made to support both a=>b and b=>a relationship types.
   *
   * @param int $contactID
   *   contact id whose permissioned contacts are to be found.
   * @param string $contactType
   *
   * @return array
   *   Array of contacts
   */
  public static function getPermissionedContacts($contactID, $contactType = NULL) {
    $contacts = [];
    $args = [1 => [$contactID, 'Integer']];
    $relationshipTypeClause = $contactTypeClause = '';

    if ($contactType) {
      $contactTypeClause = ' AND cc.contact_type = %3 ';
      $args[3] = [$contactType, 'String'];
    }

    $query = "
SELECT cc.id as id, cc.sort_name as name
FROM civicrm_relationship cr, civicrm_contact cc
WHERE
  (
    (
      cr.contact_id_a         = %1 AND
      cr.is_permission_a_b    = 1
    )
  OR
    (
      cr.contact_id_b         = %1 AND
      cr.is_permission_b_a    = 1
    )
  ) AND
  cc.id = if(cr.contact_id_a = %1, cr.contact_id_b, cr.contact_id_a) AND
  IF(cr.end_date IS NULL, 1, (DATEDIFF( CURDATE( ), cr.end_date ) <= 0)) AND
  cr.is_active = 1 AND
  cc.is_deleted = 0
  $relationshipTypeClause
  $contactTypeClause
";

    $dao = CRM_Core_DAO::executeQuery($query, $args);
    while ($dao->fetch()) {
      $contacts[$dao->id] = [
        'name' => $dao->name,
        'value' => $dao->id,
      ];
    }

    return $contacts;
  }

}
