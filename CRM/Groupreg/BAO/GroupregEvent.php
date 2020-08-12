<?php
use CRM_Groupreg_ExtensionUtil as E;

class CRM_Groupreg_BAO_GroupregEvent extends CRM_Groupreg_DAO_GroupregEvent {

  /**
   * Create a new GroupregEvent based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Groupreg_DAO_GroupregEvent|NULL
   */
  // public static function create($params) {
  //   $className = 'CRM_Groupreg_DAO_GroupregEvent';
  //   $entityName = 'GroupregEvent';
  //   $hook = empty($params['id']) ? 'create' : 'edit';

  //   CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
  //   $instance = new $className();
  //   $instance->copyValues($params);
  //   $instance->save();
  //   CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

  //   return $instance;
  // } */

}
