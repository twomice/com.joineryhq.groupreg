<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id 
 * @property string $event_id 
 * @property bool|string $is_hide_not_you 
 * @property string $related_contact_tag_id 
 * @property string $is_prompt_related 
 * @property bool|string $is_require_existing_contact 
 * @property string $is_primary_attending 
 * @property string $nonattendee_role_id 
 */
class CRM_Groupreg_DAO_GroupregEvent extends CRM_Groupreg_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_groupreg_event';

}
