<?php
use CRM_Groupreg_ExtensionUtil as E;

/**
 * GroupregPrefill.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_groupreg_prefill_Get_spec(&$spec) {
//  $spec['magicword']['api.required'] = 1;
}

/**
 * GroupregPrefill.Get API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_groupreg_prefill_Get($params) {
  unset($params['return']);
  dsm(var_export($params, 1), '$params');
  $ret = civicrm_api('Contact', 'getList', $params);
  dsm($ret, 'ret in '. __FUNCTION__);
  return $ret;
}


/**
 * Get parameters for getlist function.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 */
//function _civicrm_api3_groupreg_prefill_getlist_params(&$request) {
//  require_once(/var/www/vine/sites/all/modules/civicrm/api/v3/Contact.php);
//  return _civicrm_api3_contact_getlist_params($request);
//}

/**
 * Get output for getlist function.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
//function _civicrm_api3_groupreg_prefill_getlist_output($result, $request) {
//  return _civicrm_api3_contact_getlist_output($result, $request);
//}

