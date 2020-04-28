<?php


class CRM_Groupreg_Util {
  const primaryIsAteendeeYes = 1;
  const primaryIsAteendeeNo = 0;
  const primaryIsAteendeeSelect = 2;

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
}
