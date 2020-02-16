<?php

require_once 'groupreg.civix.php';
use CRM_Groupreg_ExtensionUtil as E;

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function groupreg_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
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

  if (array_key_exists('groupregTemporarilyUnrequiredFields', $form->_attributes)) {
    // Re-add tempoarily unrequired fields to the list of required fields, so that
    // they are by default required when not hidden.
    $form->_required = array_merge($form->_required, $form->_attributes['groupregTemporarilyUnrequiredFields']);
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
  elseif ($formName == 'CRM_Price_Form_Field') {
    // Here we need to save all of our injected fields on this form.

    // Get a list of the injected fields for this form.
    $fieldNames = _groupreg_buildForm_fields($formName);

    // Get the existing settings record for this field, if any.
    $fieldId = $form->getVar('_fid');
    $groupregPriceFieldGet = \Civi\Api4\GroupregPriceField::get()
      ->addWhere('price_field_id', '=', $fieldId)
      ->execute()
      ->first();
    // If existing record wasn't found, we'll create.
    if (empty($groupregPriceFieldGet)) {
      $groupregPriceField = \Civi\Api4\GroupregPriceField::create()
        ->addValue('price_field_id', $fieldId);
    }
    // If it was found, we'll just update it.
    else {
      $groupregPriceField = \Civi\Api4\GroupregPriceField::update()
        ->addWhere('id', '=', $groupregPriceFieldGet['id']);
    }
    // Whether create or update, add the values of our injected fields.
    foreach ($fieldNames as $fieldName) {
      $groupregPriceField->addValue($fieldName, $form->_submitValues[$fieldName]);
    }
    // Create/update settings record.
    $groupregPriceField->execute();
  }
  elseif ($formName == 'CRM_Event_Form_Registration_Confirm') {
    $primaryPid = $form->getVar('_participantId');
    $formValues = $form->getVar('_values');
    if (!CRM_Utils_Array::value('isRegisteringSelf', $formValues['params'][$primaryPid], 1)) {
      $formParams = $form->getVar('_params');
      $eventId = CRM_Utils_Array::value('eventID', $formParams);

      // get nonattendee_role_id
      $groupregEventGet = \Civi\Api4\GroupregEvent::get()
        ->addWhere('event_id', '=', $eventId)
        ->execute()
        ->first();
      $nonAttendeeRoleId = CRM_Utils_Array::value('nonattendee_role_id', $groupregEventGet);
      if ($nonAttendeeRoleId && $primaryPid) {
        $participantUpdate = \Civi\Api4\Participant::update()
          ->addWhere('id', '=', $primaryPid)
          ->addValue('role_id', $nonAttendeeRoleId)
          ->execute();

      }
    }
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
    $event = \Civi\Api4\Event::get()
      ->addWhere('id', '=', $form->_eventId)
      ->execute()
      ->first();
    if (CRM_Utils_Array::value('is_multiple_registrations', $event)) {
      $jsVars = [];
      // Add fields to manage "primary is attending" for this registration.
      $groupregEvent = \Civi\Api4\GroupregEvent::get()
        ->addWhere('event_id', '=', $form->_eventId)
        ->execute()
        ->first();
      $isPrimaryAttending = CRM_Utils_Array::value('is_primary_attending', $groupregEvent, CRM_Groupreg_Util::primaryIsAteendeeYes);
      $isHideNotYou = CRM_Utils_Array::value('is_hide_not_you', $groupregEvent);
      if ($isPrimaryAttending == CRM_Groupreg_Util::primaryIsAteendeeSelect) {
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
      }
      $jsVars['isPrimaryAttending'] = $isPrimaryAttending;
      $jsVars['isHideNotYou'] = (bool) $isHideNotYou;
      $jsVars['nonAttendeeHiddenPriceFields'] = [];
      $jsVars['formId'] = $form->_attributes['id'];

      // If isPrimaryAttending is not "yes", prepare for additional features.
      if ($isPrimaryAttending != CRM_Groupreg_Util::primaryIsAteendeeYes) {
        // Allow for hiding price fields via JS.
        $jsVars['nonAttendeeHiddenPriceFields'] = _groupreg_getNonAttendeeHiddenPriceFields($form->_eventId);
        // Add a hidden field for transmitting names of dynamically hidden fields.
        $form->add('hidden', 'groupregHiddenPriceFields', NULL, array('id' => 'groupregHiddenPriceFields'));
        // Take specific action when form has been submitted; namely, we need to
        // avoid 'required' validation on price fields that were hidden by us.
        // To do this, we need to remove them from the list of 'required' elements
        // now; we can't do it in our validateForm hook implementation because
        // the core form validation runs first. Instead we do it here, so that the
        // core form validation won't enforce that required setting. We'll add
        // them back to the 'required' list in our own validateForm hook implementation,
        // so that they will properly default to being required when they aren't
        // hidden, eg. when the form reloads.
        if ($form->_flagSubmitted) {
          // Note the value of groupregHiddenPriceFields and temporarily strip them
          // from the "required" array. (We'll add them back later in hook_civicrm_validateForm().)
          $hiddenFieldNames = json_decode($form->_submitValues['groupregHiddenPriceFields']);
          $groupregTemporarilyUnrequiredFields = [];
          foreach ($hiddenFieldNames as $hiddenFieldName) {
            $index = array_search($hiddenFieldName, $form->_required);
            if ($index) {
              unset($form->_required[$index]);
              $groupregTemporarilyUnrequiredFields[] = $hiddenFieldName;
            }
          }
          // Store the list so we can add them back later.
          $form->_attributes['groupregTemporarilyUnrequiredFields'] = $groupregTemporarilyUnrequiredFields;
        }

        // Also provide more active handling of the "additional participants" select
        // control, startin with addition of an empty "-SELECT-" option, to facilitate
        // more careful UX on the reg form.
        $additional_participants = $form->getElement('additional_participants');
        $additional_participants->addOption('- ' . E::ts('SELECT') . '-', '-1');
        array_unshift($additional_participants->_options, array_pop($additional_participants->_options));
      }

      CRM_Core_Resources::singleton()->addVars('groupreg', $jsVars);
      CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_Register-is_multiple.js');
      CRM_Core_Resources::singleton()->addStyleFile('com.joineryhq.groupreg', 'css/CRM_Event_Form_Registration_Register-is_multiple.css');

      /* JavaScript actions on this form may be slow, leading to a jumpy display.
       * Hide it with style attribute to give JS code time to do its thing. JS
       * will then display the form.
       */
      $form->_attributes['style'] = "display:none";
    }
  }
  elseif ($formName == 'CRM_Event_Form_Registration_AdditionalParticipant') {
    $params = $form->getVar('_params');
    // If primary is not attending, change page title and status messages to
    // reflect decremented participant counts.
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0) {
      $total = CRM_Utils_Array::value('additional_participants', $params[0]);
      $participantNo = substr($form->getVar('_name'), 12);
      CRM_Utils_System::setTitle(ts('Register Participant %1 of %2', array(1 => $participantNo, 2 => $total)));
      _groupreg_correct_status_messages();
    }
  }
  elseif ($formName == 'CRM_Event_Form_Registration_Confirm') {
    $params = $form->getVar('_params');
    // If primary is not attending, change status message to reflect decremented
    // participant counts.
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0) {
      _groupreg_correct_status_messages();
      CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_Confirm-decrement-counters.js');
    }
  }
  elseif ($formName == 'CRM_Price_Form_Field') {
    $fieldId = $form->getVar('_fid');
    // Populate default values for our fields.
    $groupregPriceField = \Civi\Api4\GroupregPriceField::get()
      ->addWhere('price_field_id', '=', $fieldId)
      ->execute()
      ->first();
    $defaults = [];
    if (!empty($groupregPriceField)) {
      foreach ($fieldNames as $fieldName) {
        $defaults[$fieldName] = $groupregPriceField[$fieldName];
      }
    }
    $form->setDefaults($defaults);
    // Insert the JS file that will put fields in the right places and handle other on-screen behaviors.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Price_Form_Field.js');
  }
}

function _groupreg_correct_status_messages() {
  $session = CRM_Core_Session::singleton();
  $statuses = $session->getStatus(TRUE);
  $additionalRegex = '/' . E::ts('Registration information for participant %1 has been saved.', array(1 => '([0-9]+)')) . '/';
  foreach ($statuses as $status) {
    $matches = [];
    if (preg_match($additionalRegex, $status['text'], $matches)) {
      $correctedParticipantCount = --$matches[1];
      if ($correctedParticipantCount == 0) {
        $status['text'] = E::ts('Your information has been saved.');

      }
      else {
        $status['text'] = E::ts('Registration information for participant %1 has been saved.', array(1 => $correctedParticipantCount));
      }
    }
    $status['options'] = $status['options'] ?: [];
    call_user_func_array('CRM_Core_Session::setStatus', $status);
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
      $form->addElement('checkbox', 'is_hide_not_you', E::ts('Hide "Not you" message?'));
      $form->addElement('checkbox', 'is_prompt_related', E::ts('Prompt with related individuals on Additional Partipant forms?'));
      $form->addRadio('is_primary_attending', E::ts('Primary participant is attendee'), [
        CRM_Groupreg_Util::primaryIsAteendeeYes => E::ts("Yes"),
        CRM_Groupreg_Util::primaryIsAteendeeNo => E::ts("No"),
        CRM_Groupreg_Util::primaryIsAteendeeSelect => E::ts("Allow user to select"),
      ], NULL, '<BR />');
      $form->addElement(
        'select',
        'nonattendee_role_id',
        E::ts('Non-attendee role'),
        ['' => E::ts('- select -')] + CRM_Event_BAO_Participant::buildOptions('participant_role_id'),
        ['class' => 'crm-select2']
      );
    }
    $fieldNames = [
      'is_hide_not_you',
      'is_prompt_related',
      'is_primary_attending',
      'nonattendee_role_id',
    ];
  }
  elseif ($formName == 'CRM_Price_Form_Field') {
    if ($form !== NULL) {
      $form->addElement('checkbox', 'is_hide_non_participant', E::ts('Hide from non-participating primary registrants?'));
    }
    $fieldNames = [
      'is_hide_non_participant',
    ];
  }
  return $fieldNames;
}

/**
 * Get a list of all price field IDs for the given event's price set (if any) which
 * are marked for hiding from non-attending registrants.
 *
 * @param type $eventId
 * @return type
 */
function _groupreg_getNonAttendeeHiddenPriceFields($eventId) {
  $nonAttendeeHiddenPriceFields = [];
  // Get the price set for this event, if any.
  $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventId);
  if ($priceSetId) {
    // Get the price fields for this price set, including our own groupreg
    // settings for each field.
    $fieldsGet = civicrm_api3('PriceField', 'get', [
      'price_set_id' => $priceSetId,
      'api.GroupregPriceField.get' => [],
      'options' => ['limit' => 0],
    ]);
    // Loop through fields, and add to jsvars if the field is marked for
    // hiding from non-attending registrants.
    foreach ($fieldsGet['values'] as $priceFieldId => $priceField) {
      if (!empty($priceField['api.GroupregPriceField.get'])) {
        $groupregPriceField = reset($priceField['api.GroupregPriceField.get']);
        if ($groupregPriceField['is_hide_non_participant']) {
          $nonAttendeeHiddenPriceFields[] = $priceFieldId;
        }
      }
    }
  }
  return $nonAttendeeHiddenPriceFields;
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
