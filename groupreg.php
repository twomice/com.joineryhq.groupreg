<?php

require_once 'groupreg.civix.php';
use CRM_Groupreg_ExtensionUtil as E;

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function groupreg_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Validating the "online registration" event config form, ensure 'non-attending role'
    // is specfied if 'primary participant is attending' is anything but "yes".

    // Don't bother with this if we've disabled online registration, or if
    // 'allow multiple' is false, or if 'primary participant is attending' is
    // 'yes'
    if (
      CRM_Utils_Array::value('is_online_registration', $form->_submitValues)
      && CRM_Utils_Array::value('is_multiple_registrations', $form->_submitValues)
      && (CRM_Utils_Array::value('is_primary_attending', $form->_submitValues) != CRM_Groupreg_Util::primaryIsAteendeeYes)
    ) {
      if (empty(CRM_Utils_Array::value('nonattendee_role_id', $form->_submitValues))) {
        $errors['nonattendee_role_id'] = E::ts('The field "Primary participant is attendee" is not set to "Yes"; you must specify a non-attending role.');
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess/
 */
function groupreg_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Here we need to save all of our injected fields on this form.

    // Get a list of the injected fields for this form.
    $fieldNames = _groupreg_buildForm_fields($formName);

    // Get the existing settings record for this event, if any.
    $groupregEventGet = \Civi\Api4\GroupregEvent::get()
      ->addWhere('event_id', '=', $form->_id)
      ->execute()
      ->first();
    // If existing record wasn't found, we'll create.
    if (empty($groupregEventGet)) {
      $groupregEvent = \Civi\Api4\GroupregEvent::create()
        ->addValue('event_id', $form->_id);
    }
    // If it was found, we'll just update it.
    else {
      $groupregEvent = \Civi\Api4\GroupregEvent::update()
        ->addWhere('id', '=', $groupregEventGet['id']);
    }
    // Whether create or update, add the values of our injected fields.
    foreach ($fieldNames as $fieldName) {
      $groupregEvent->addValue($fieldName, $form->_submitValues[$fieldName]);
    }
    // Create/update settings record.
    $groupregEvent->execute();
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function groupreg_civicrm_buildForm($formName, &$form) {
  // Get fieldnames for this form, if any, and assign to template.
  $fieldNames = _groupreg_buildForm_fields($formName, $form);
  if (!empty($fieldNames)) {
    $bhfe = $form->get_template_vars('beginHookFormElements');
    if (!$bhfe) {
      $bhfe = [];
    }
    foreach ($fieldNames as $fieldName) {
      $bhfe[] = $fieldName;
    }
    $form->assign('beginHookFormElements', $bhfe);
  }

  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Populate default values for our fields.
    $groupregEvent = \Civi\Api4\GroupregEvent::get()
      ->addWhere('event_id', '=', $form->_id)
      ->execute()
      ->first();
    $defaults = [];
    if (!empty($groupregEvent)) {
      foreach ($fieldNames as $fieldName) {
        $defaults[$fieldName] = $groupregEvent[$fieldName];
      }
    }
    // 'is_primary_attending' defaults to 'yes' even if no settings exist for this event.
    $defaults['is_primary_attending'] = CRM_Utils_Array::value('is_primary_attending', $defaults, 1);
    $form->setDefaults($defaults);
    // Insert the JS file that will put fields in the right places and handle other on-screen behaviors.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_ManageEvent_Registration.js');
  }
  elseif ($formName == 'CRM_Event_Form_Registration_Register') {
    // Is event set for multiple participant registration?
    $event = civicrm_api3('event', 'getSingle', ['id' => $form->_eventId]);
    if (CRM_Utils_Array::value('is_multiple_registrations', $event)) {
      $form->addRadio('isRegisteringSelf', E::ts('Are you registering yourself for this event?'), [
        '1' => E::ts("Yes, I'm attending"),
        '0' => E::ts("No, I'm only registering other people"),
      ]);

      $bhfe = $form->get_template_vars('beginHookFormElements');
      if (!$bhfe) {
        $bhfe = [];
      }
      $bhfe[] = 'isRegisteringSelf';
      $form->assign('beginHookFormElements', $bhfe);

      CRM_Core_Resources::singleton()->addScriptFile('namelessevents', 'js/CRM_Event_Form_Registration_Register-is_multiple.js');
    }
  }
}

/**
 * Add injected elements to $form (if provided), and in any case return a list
 * of the injected fields for $formName.
 *
 * @param type $formName
 * @param type $form
 * @return string
 */
function _groupreg_buildForm_fields($formName, &$form = NULL) {
  $fieldNames = [];
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    if ($form !== NULL) {
      $form->addElement('checkbox', 'is_hide_not_you', ts('Hide "Not you" message?'));
      $form->addElement('checkbox', 'is_prompt_related', ts('Prompt with related individuals on Additional Partipant forms?'));
      $form->addRadio('is_primary_attending', E::ts('Primary participant is attendee'), [
        CRM_Groupreg_Util::primaryIsAteendeeYes => E::ts("Yes"),
        CRM_Groupreg_Util::primaryIsAteendeeNo => E::ts("No"),
        CRM_Groupreg_Util::primaryIsAteendeeSelect => E::ts("Allow user to select"),
      ], NULL, '<BR />');
      $form->addElement('select', 'nonattendee_role_id', ts('Non-attendee role'), ['' => E::ts('- select -')] + CRM_Event_BAO_Participant::buildOptions('participant_role_id') , ['class' => 'crm-select2']);
    }
    $fieldNames = [
      'is_hide_not_you',
      'is_prompt_related',
      'is_primary_attending',
      'nonattendee_role_id',
    ];
  }
  return $fieldNames;
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
