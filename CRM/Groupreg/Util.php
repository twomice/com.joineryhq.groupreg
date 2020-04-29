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

  public static function getRelationshipTypeOptions($otherContactType) {
    $relationshipTypeOptions = [];
    $relationshipTypes = \Civi\Api4\RelationshipType::get()
      ->addWhere('is_active', '=', 1)
      ->addClause('OR',
        ['AND', [
          ['contact_type_a', '=', 'Individual'],
          ['contact_type_b', '=', $otherContactType]
        ]],
        ['AND', [
          ['contact_type_a', '=', $otherContactType],
          ['contact_type_b', '=', 'Individual']
        ]]
      )
      ->execute();
    foreach ($relationshipTypes as $relationshipType) {
      dsm($relationshipType, '$relationshipType');
      if ($relationshipType['contact_type_a'] == 'Individual') {
        $relationshipTypeOptions["{$relationshipType['id']}_a_b"] = $relationshipType['label_a_b'];
      }
      if ($relationshipType['contact_type_b'] == 'Individual') {
        $relationshipTypeOptions["{$relationshipType['id']}_b_a"] = $relationshipType['label_b_a'];
      }
    }
    // TODO: support limitation of these types (and possibly re-labeling of them)
    // in the UI.
    return $relationshipTypeOptions;
  }
}
