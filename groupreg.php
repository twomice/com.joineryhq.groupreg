<?php

require_once 'groupreg.civix.php';
use CRM_Groupreg_ExtensionUtil as E;

/**
 * Implements hook_civicrm_apiWrappers().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_apiWrappers/
 */
function groupreg_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if (
    strtolower($apiRequest['entity']) == 'contact'
    && strtolower($apiRequest['action']) == 'get'
    && CRM_Utils_Array::value('isGroupregPrefill', $apiRequest['params'], 0)
  ) {
    $wrappers[] = new CRM_Groupreg_APIWrappers_Contact();
  }
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function groupreg_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Validating the "online registration" event config form, for groupreg settings.
    // Don't bother with these validations if we've disabled online registration, or if
    // 'allow multiple' is false.
    if (
      CRM_Utils_Array::value('is_online_registration', $form->_submitValues)
      && CRM_Utils_Array::value('is_multiple_registrations', $form->_submitValues)
    ) {
      // Ensure 'non-attending role' is specfied if 'primary participant is attending' is anything but "yes".
      if (CRM_Utils_Array::value('is_primary_attending', $form->_submitValues) != CRM_Groupreg_Util::primaryIsAteendeeYes) {
        if (empty(CRM_Utils_Array::value('nonattendee_role_id', $form->_submitValues))) {
          $errors['nonattendee_role_id'] = E::ts('The field "Primary participant is attendee" is not set to "Yes"; you must specify a non-attending role.');
        }
      }
      // Do not allow both 'is_prompt_related' and 'is_prompt_related_hop' at the same time.
      if (CRM_Utils_Array::value('is_prompt_related', $form->_submitValues)
        && CRM_Utils_Array::value('is_prompt_related_hop', $form->_submitValues)
      ) {
        $errors['is_prompt_related'] = E::ts('The fields "Prompt for Additional Participant through individual relationships" and  "Prompt for Additional Participant through organization relationships" cannot both be selected; please choose only one.');
        $errors['is_prompt_related_hop'] = E::ts('The fields "Prompt for Additional Participant through individual relationships" and  "Prompt for Additional Participant through organization relationships" cannot both be selected; please choose only one.');
      }
    }
  }

  if (array_key_exists('groupregTemporarilyUnrequiredFields', $form->_attributes)) {
    // Re-add temporarily unrequired fields to the list of required fields, so that
    // they are by default required when not hidden.
    $form->_required = array_merge($form->_required, $form->_attributes['groupregTemporarilyUnrequiredFields']);
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess/
 */
function groupreg_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Here we need to save all of our injected fields on this form.

    // Get a list of the injected fields for this form.
    $fieldNames = _groupreg_buildForm_fields($formName);

    // Get the existing settings record for this event, if any.
    $eventSettings = CRM_Groupreg_Util::getEventSettings($form->_id);
    // If existing record wasn't found, we'll create.
    if (empty($eventSettings)) {
      $groupregEvent = \Civi\Api4\GroupregEvent::create()
        ->addValue('event_id', $form->_id);
    }
    // If it was found, we'll just update it.
    else {
      $groupregEvent = \Civi\Api4\GroupregEvent::update()
        ->addWhere('id', '=', $eventSettings['id']);
    }
    // Whether create or update, add the values of our injected fields.
    foreach ($fieldNames as $fieldName) {
      $value = $form->_submitValues[$fieldName];
      if ($fieldName == 'related_contact_tag_id' && $value == -1) {
        $value = NULL;
      }
      $groupregEvent->addValue($fieldName, $value);
    }
    // Create/update settings record.
    $groupregEvent
      ->setCheckPermissions(FALSE)
      ->execute();
  }
  elseif ($formName == 'CRM_Price_Form_Field') {
    // Here we need to save all of our injected fields on this form.

    // Get a list of the injected fields for this form.
    $fieldNames = _groupreg_buildForm_fields($formName);

    // Get the existing settings record for this field, if any.
    $fieldId = $form->getVar('_fid');
    $groupregPriceFieldGet = \Civi\Api4\GroupregPriceField::get()
      ->addWhere('price_field_id', '=', $fieldId)
      ->setCheckPermissions(FALSE)
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
    $groupregPriceField
      ->setCheckPermissions(FALSE)
      ->execute();
  }
  elseif ($formName == 'CRM_Event_Form_Registration_AdditionalParticipant') {
    //  DEPRECATED: the idea here was to remove the contact_id for all additional participants,
    //  thereby forcing duplicate contact creation; rationale was that we don't want
    //  to create permissioned relationships to existing contacts; however, we've solved
    //  that elsewhere by creating the relationships as inactive and tagging the contacts
    //  for review. Leaving this code here for now since it represents the results of
    //  some measurable effort.
    //    $formParams = $form->get('params');
    //    end($formParams);
    //    $lastKey = key($formParams);
    //    unset($formParams[$lastKey]['contact_id']);
    //    $form->set('params', $formParams);
  }
  elseif ($formName == 'CRM_Event_Form_Registration_Confirm') {
    $formParams = $form->getVar('_params');
    $eventId = CRM_Utils_Array::value('eventID', $formParams);
    // Only take action if event is configured for groupreg.
    $eventSettings = CRM_Groupreg_Util::getEventSettings($eventId);
    if (empty($eventSettings)) {
      return;
    }

    $primaryPid = $form->getVar('_participantId');
    $formValues = $form->getVar('_values');
    if (!CRM_Utils_Array::value('isRegisteringSelf', $formValues['params'][$primaryPid], 1)) {

      // get nonattendee_role_id
      $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($eventId);
      $nonAttendeeRoleId = CRM_Utils_Array::value('nonattendee_role_id', $groupregEventSettings);
      if ($nonAttendeeRoleId && $primaryPid) {
        $participantUpdate = \Civi\Api4\Participant::update()
          ->addWhere('id', '=', $primaryPid)
          ->addValue('role_id', $nonAttendeeRoleId)
          ->setCheckPermissions(FALSE)
          ->execute();
      }
    }
    // Create relationships if needed, from the list of created participant IDs
    // in $form->__participantIDS.
    foreach ($form->getVar('_participantIDS') as $participantId) {
      // If it's the primary participant, just get the contact_id from the participant record.
      if ($participantId == $primaryPid) {
        $participant = \Civi\Api4\Participant::get()
          ->addWhere('id', '=', $participantId)
          ->setCheckPermissions(FALSE)
          ->execute()
          ->first();
        $primaryParticipantCid = $participant['contact_id'];
        continue;
      }
      // For all other participants, we'll create/update a relationship to the
      // primary participant contact.
      $participantParams = CRM_Utils_Array::value($participantId, $formValues['params']);
      // Relationship type was submitted as 'N_a_b' or 'N_b_a' in the form.
      // We can split it up and use those parts.
      $relationshipType = CRM_Utils_Array::value('groupregRelationshipType', $participantParams);
      if ($relationshipType) {
        list($relationshipTypeId, $rpos1, $rpos2) = explode('_', $relationshipType);
        $permission_column = "is_permission_{$rpos1}_{$rpos2}";

        // An existing relationship would be recorded in the groupregRelationshipId field
        // for each additional participant.
        $relationshipId = CRM_Utils_Array::value('groupregRelationshipId', $participantParams);
        if ($relationshipId) {
          // We have an existing relationship; we'll just update.
          // FIXME: use get api to verify that the given relationshipId is actually
          // for a relationship between these contacts.
          $relationship = \Civi\Api4\Relationship::update()
            ->addWhere('id', '=', $relationshipId);
        }
        else {
          // We probably need to create a new relationship, noting that such may
          // already exist.
          // Get the contact_id from the participant record.
          $participant = \Civi\Api4\Participant::get()
            ->addWhere('id', '=', $participantId)
            ->setCheckPermissions(FALSE)
            ->execute()
            ->first();
          $participantCid = $participant['contact_id'];
          // Use the 'create' api and populate known values.
          $relationship = \Civi\Api4\Relationship::create()
            ->addValue('contact_id_' . $rpos1, $primaryParticipantCid)
            ->addValue('contact_id_' . $rpos2, $participantCid)
            ->addValue('description', E::ts('Relationship created by Group Registration'))
            ->addValue($permission_column, 1);
            // For security, unless specified otherwise, we make all new permissioned relationships inactive,
            // pending staff review. Contact will be tagged for review.
            if ($eventSettings['related_contact_tag_id']) {
              $relationship->addValue('is_active', 0);
              // This would also mean we're configured to tag such additional
              // participant contacts for review; do so now.
              if ($tagId = $eventSettings['related_contact_tag_id']) {
                $entityTag = \Civi\Api4\EntityTag::create()
                  ->addValue('tag_id', $tagId)
                  ->addValue('entity_table', 'civicrm_contact')
                  ->addValue('entity_id', $participantCid)
                  ->setCheckPermissions(FALSE)
                  ->execute();
              }
            }
        }
        // Fill in a few remaining values and save that (new or existing) relationship.
        $relationship
          ->addValue('relationship_type_id', $relationshipTypeId);
        try {
          $relationship
            ->setCheckPermissions(FALSE)
            ->execute();
        }
        catch (Exception $e) {
          // If the error is because relationship already exists, we can ignore
          // it. Otherwise, throw it to be handled upstream.
          if ($e->getMessage() != 'Duplicate Relationship') {
            throw $e;
          }
        }
      }
      else {
        // TODO: this should not be. Log an error.
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
    _groupreg_add_bhfe($fieldNames, $form);
  }

  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Populate default values for our fields.
    $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_id);
    $defaults = [];
    if (!empty($groupregEventSettings)) {
      foreach ($fieldNames as $fieldName) {
        $defaults[$fieldName] = $groupregEventSettings[$fieldName];
      }
    }
    // 'is_primary_attending' defaults to 'yes' even if no settings exist for this event.
    $defaults['is_primary_attending'] = CRM_Utils_Array::value('is_primary_attending', $defaults, 1);
    // Convert defined NULL to -1 for related_contact_tag_id.
    if (CRM_Utils_Array::value('related_contact_tag_id', $defaults, 'FALSE') == NULL) {
      $defaults['related_contact_tag_id'] = -1;
    }
    $form->setDefaults($defaults);
    // Insert the JS file that will put fields in the right places and handle other on-screen behaviors.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_ManageEvent_Registration.js');
  }
  elseif ($formName == 'CRM_Event_Form_Registration_Register') {
    // Is event set for multiple participant registration?
    $event = \Civi\Api4\Event::get()
      ->addWhere('id', '=', $form->_eventId)
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();
    if (CRM_Utils_Array::value('is_multiple_registrations', $event)) {
      $jsVars = [];
      // Add fields to manage "primary is attending" for this registration.
      $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);
      $isPrimaryAttending = CRM_Utils_Array::value('is_primary_attending', $groupregEventSettings, CRM_Groupreg_Util::primaryIsAteendeeYes);
      $isHideNotYou = CRM_Utils_Array::value('is_hide_not_you', $groupregEventSettings);
      if ($isPrimaryAttending == CRM_Groupreg_Util::primaryIsAteendeeSelect) {
        $form->addRadio('isRegisteringSelf', E::ts('Are you registering yourself for this event?'), [
          '1' => E::ts("Yes, I'm attending"),
          '0' => E::ts("No, I'm only registering other people"),
        ]);

        _groupreg_add_bhfe(['isRegisteringSelf'], $form);
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
        if (!empty($jsVars['nonAttendeeHiddenPriceFields'])) {
          // If any fields can be hidden for non-attending registrant, then it's
          // possible that the registrant will have no price option selected. This
          // will generate an error in civicrm: "Select at least one option from Event Fee(s).",
          // in CRM_Event_Form_Registration::validatePriceSet(). This rather
          // hackish workaround gets around that requirement by creating a dummy
          // field that fits the naming requirements of a price field, with a
          // value of 0, so that it will appear to CRM_Event_Form_Registration::validatePriceSet()
          // that a price option has been selected.
          $form->addElement('hidden', 'price_groupRegPlaceholder_olFCYhkSeWjmWekLtWCA', '0', ['id' => 'price_groupRegPlaceholder_olFCYhkSeWjmWekLtWCA']);
        }
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
            if ($index !== FALSE) {
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
       * To give JS code time to do its thing, hide it with style attribute (hiding
       * with JS is obviously too slow, and CSS usually is too). JS will then
       * display the form when it's ready.
       */
      $form->_attributes['style'] = "display:none";
    }
  }
  elseif ($formName == 'CRM_Event_Form_Registration_AdditionalParticipant') {
    $params = $form->getVar('_params');
    // If primary is not attending:
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0) {
      // Change page title and status messages to reflect decremented
      // participant counts.
      $total = CRM_Utils_Array::value('additional_participants', $params[0]);
      $participantNo = substr($form->getVar('_name'), 12);
      CRM_Utils_System::setTitle(ts('Register Participant %1 of %2', array(1 => $participantNo, 2 => $total)));
      _groupreg_correct_status_messages();

      // Also hide "skip participant" on first additional participant; this is
      // meant to prevent user from subitting only themselves while still saying
      // they won't attend.
      // TODO: Having to use !important is a bad smell, would like to completely
      // remove the button via php.
      CRM_Core_Resources::singleton()->addStyle('span.crm-button_qf_Participant_1_next_skip {display:none !important  ;}', 1, 'html-header');
    }
    // Allow "skip participant" without requring 'groupregRelationshipType' field.
    if ($form->_flagSubmitted) {
      $button = substr($form->controller->getButtonName(), -4);
      if ($button == 'skip') {
        // Note the value of groupregHiddenPriceFields and temporarily strip them
        // from the "required" array. (We'll add them back later in hook_civicrm_validateForm().)
        $hiddenFieldNames = ['groupregRelationshipType'];
        $groupregTemporarilyUnrequiredFields = [];
        foreach ($hiddenFieldNames as $hiddenFieldName) {
          $index = array_search($hiddenFieldName, $form->_required);
          if ($index !== FALSE) {
            unset($form->_required[$index]);
            $groupregTemporarilyUnrequiredFields[] = $hiddenFieldName;
          }
        }
        // Store the list so we can add them back later.
        $form->_attributes['groupregTemporarilyUnrequiredFields'] = $groupregTemporarilyUnrequiredFields;
      }
    }
  }
  elseif ($formName == 'CRM_Price_Form_Field') {
    $fieldId = $form->getVar('_fid');
    // Populate default values for our fields.
    $groupregPriceField = \Civi\Api4\GroupregPriceField::get()
      ->addWhere('price_field_id', '=', $fieldId)
      ->setCheckPermissions(FALSE)
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
  elseif (
    $formName == 'CRM_Event_Form_Registration_Confirm'
    || $formName == 'CRM_Event_Form_Registration_ThankYou'
  ) {
    $params = $form->getVar('_params');
    // If primary is not attending, change status message to reflect decremented
    // participant counts.
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0) {
      _groupreg_correct_status_messages();
      CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_-decrement-counters.js');
    }
  }
}

function _groupreg_add_bhfe(array $elementNames, CRM_Core_Form &$form) {
  $bhfe = $form->get_template_vars('beginHookFormElements');
  if (!$bhfe) {
    $bhfe = [];
  }
  foreach ($elementNames as $elementName) {
    $bhfe[] = $elementName;
  }
  $form->assign('beginHookFormElements', $bhfe);
}

function _groupreg_correct_status_messages() {
  $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
  if (!is_array($statuses)) {
    // If ther are no statuses, $statuses could be NULL. Just return.
    return;
  };
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
      $form->addElement('checkbox', 'is_prompt_related', E::ts('Prompt for Additional Participant through individual relationships?'));
      $form->addElement('checkbox', 'is_prompt_related_hop', E::ts('Prompt for Additional Participant through organization relationships?'));
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
      $tagIdLabel = E::ts('Tag for relationship review on related contacts');
      $form->addElement(
        'select',
        'related_contact_tag_id',
        $tagIdLabel,
         ['' => E::ts('- select -')] + CRM_Core_BAO_EntityTag::buildOptions('tag_id') + ['-1' => E::ts('- NONE: ENABLE PERMISSIONED RELATIONSHIPS -')],
        ['class' => 'crm-select2']
      );
      $form->addRule('related_contact_tag_id', E::ts('The field "%1" is required', [1 => $tagIdLabel]), 'required');
    }
    $fieldNames = [
      'is_hide_not_you',
      'is_prompt_related',
      'is_prompt_related_hop',
      'is_primary_attending',
      'related_contact_tag_id',
      'nonattendee_role_id',
    ];
  }
  elseif ($formName == 'CRM_Event_Form_Registration_AdditionalParticipant') {
    if ($form !== NULL) {
      $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);
      if (CRM_Utils_Array::value('is_prompt_related', $groupregEventSettings)) {
        $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
        $firstRelationship = CRM_Contact_BAO_Relationship::getRelationship($userCid, 3, 1, NULL, NULL, NULL, NULL, TRUE);
        if ($firstRelationship) {
          // EntityRef field for related contacts.
          $entityRefParams = [
            'create' => FALSE,
            'api' => [
              'params' => [
                // This param is watched for in CRM_Groupreg_APIWrappers_Contact::fromApiInput();
                'isGroupregPrefill' => TRUE,
              ],
              'extra' => [
                // These extra parameters are provided in CRM_Groupreg_APIWrappers_Contact::toApiOutput()
                // and expected by the select2 change handler in CRM_Event_Form_Registration_AdditionalParticipant-not-self-reg.js
                'relationship_type_id',
                'rtype',
                'relationship_id',
              ],
            ],
          ];
          $form->addEntityRef('groupregPrefillContact', E::ts('Select a person'), $entityRefParams);

          // Hidden field to hold id of an existing relationship.
          $form->addElement('hidden', 'groupregRelationshipId', '', ['id' => 'groupregRelationshipId']);

          CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_AdditionalParticipant-is-prompt-related.js');
        }
      }

      // Select2 list of relationship types.
      // TODO: support limitation of these types (and possibly re-labeling of them)
      // in the UI.
      $relationshipTypeParams = [
        'contact_id' => $userCid,
        'contact_type' => 'Individual',
        'is_form' => TRUE,
      ];
      $relationshipTypeOptions = CRM_Contact_BAO_Relationship::buildOptions('relationship_type_id', NULL, $relationshipTypeParams);
      $form->add('select', 'groupregRelationshipType', E::ts('My relationship to this person'), $relationshipTypeOptions, TRUE, array('class' => 'crm-select2', 'style' => 'width: 100%;', 'placeholder' => '- ' . E::ts('SELECT') . '-'));
    }
    $fieldNames = [
      'groupregPrefillContact',
      'groupregRelationshipType',
      'groupregRelationshipId',
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

