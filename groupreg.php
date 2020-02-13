<?php

require_once 'groupreg.civix.php';
use CRM_Groupreg_ExtensionUtil as E;

function groupreg_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    $form->addElement('checkbox', 'isHideNotYou', ts('Hide "Not you" message?'));
    $form->addElement('checkbox', 'isPromptRelated', ts('Prompt with related individuals on Additional Partipant forms?'));
    $form->addRadio('isPrimaryAttending', E::ts('Primary participant is attendee'), [
      CRM_Groupreg_Util::primaryIsAteendeeYes => E::ts("Yes"),
      CRM_Groupreg_Util::primaryIsAteendeeNo => E::ts("No"),
      CRM_Groupreg_Util::primaryIsAteendeeSelect => E::ts("Allow user to select"),
    ], NULL, '<BR />');

    $bhfe = $form->get_template_vars('beginHookFormElements');
    if (!$bhfe) {
      $bhfe = [];
    }
    $bhfe[] = 'isHideNotYou';
    $bhfe[] = 'isPromptRelated';
    $bhfe[] = 'isPrimaryAttending';
    $form->assign('beginHookFormElements', $bhfe);

    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_ManageEvent_Registration.js');
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function groupreg_civicrm_config(&$config) {
  _groupreg_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function groupreg_civicrm_xmlMenu(&$files) {
  _groupreg_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function groupreg_civicrm_install() {
  _groupreg_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function groupreg_civicrm_postInstall() {
  _groupreg_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function groupreg_civicrm_uninstall() {
  _groupreg_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function groupreg_civicrm_enable() {
  _groupreg_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function groupreg_civicrm_disable() {
  _groupreg_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function groupreg_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _groupreg_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function groupreg_civicrm_managed(&$entities) {
  _groupreg_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function groupreg_civicrm_caseTypes(&$caseTypes) {
  _groupreg_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function groupreg_civicrm_angularModules(&$angularModules) {
  _groupreg_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function groupreg_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _groupreg_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function groupreg_civicrm_entityTypes(&$entityTypes) {
  _groupreg_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function groupreg_civicrm_themes(&$themes) {
  _groupreg_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
function groupreg_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function groupreg_civicrm_navigationMenu(&$menu) {
  _groupreg_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _groupreg_civix_navigationMenu($menu);
} // */
