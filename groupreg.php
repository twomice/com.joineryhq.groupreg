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
    && CRM_Utils_Array::value('isGroupregRelated', $apiRequest['params'])
  ) {
    $contact_type = CRM_Utils_Array::value('contact_type', $apiRequest['params']);
    if ($contact_type == 'Individual') {
      $wrappers[] = new CRM_Groupreg_APIWrappers_Contact_IsGroupregRelated();
    }
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

      // Ensure 'related_contact_tag_id' is specfied if:
      //  'is_prompt_related' is anything but "no"; AND
      //  'is_require_existing_contact'  is not checked.
      if (
        CRM_Utils_Array::value('is_prompt_related', $form->_submitValues)
        && !CRM_Utils_Array::value('is_require_existing_contact', $form->_submitValues, 0)
      ) {
        if (empty(CRM_Utils_Array::value('related_contact_tag_id', $form->_submitValues))) {
          $errors['related_contact_tag_id'] = E::ts('The field "Prompt for Additional Participant through relationships?" is not set to "No"; you must specify a value in the "Tag for relationship review on related contacts" field.');
        }
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
    $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_id);
    // If existing record wasn't found, we'll create.
    if (empty($groupregEventSettings)) {
      $groupregEvent = \Civi\Api4\GroupregEvent::create()
        ->addValue('event_id', $form->_id);
    }
    // If it was found, we'll just update it.
    else {
      $groupregEvent = \Civi\Api4\GroupregEvent::update()
        ->addWhere('id', '=', $groupregEventSettings['id']);
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
      ->execute();
  }
  elseif ($formName == 'CRM_Price_Form_Field') {
    // Here we need to save all of our injected fields on this form.

    // Get a list of the injected fields for this form.
    $fieldNames = _groupreg_buildForm_fields($formName);

    // Determine the field ID from given values
    $fieldId = $form->getVar('_fid');
    if (empty($fieldId)) {
      // _fid will be empty on 'create' forms; in that case, the field already
      // exists by this point in the execution, but the ID is not available in
      // this scope. We have to get it based on priceField.name, which CiviCRM
      // requires to be unique system-wide, and which is munged from label, which
      // is in scope.
      $fieldLabel = $form->_submitValues['label'];
      // Use CiviCRM's method of munging field label to field name
      $fieldName = strtolower(CRM_Utils_String::munge($fieldLabel, '_', 242));
      // CiviCRM says name must be unique for all prie set fields in the systme, regardless
      // of price set, so get the price field with this name.
      $priceFieldGet = civicrm_api3('PriceField', 'get', [
        'name' => $fieldName,
      ]);
      $fieldId = $priceFieldGet['id'];
    }

    // Get the existing settings record for this field, if any.
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
    $groupregPriceField
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
    $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($eventId);
    if (empty($groupregEventSettings)) {
      return;
    }

    $primaryPid = $form->getVar('_participantId');
    $formValues = $form->getVar('_values');
    if (!CRM_Utils_Array::value('isRegisteringSelf', $formValues['params'][$primaryPid], $groupregEventSettings['is_primary_attending'])) {

      // get nonattendee_role_id
      $nonAttendeeRoleId = CRM_Utils_Array::value('nonattendee_role_id', $groupregEventSettings);
      if ($nonAttendeeRoleId && $primaryPid) {
        $participantUpdate = \Civi\Api4\Participant::update()
          ->addWhere('id', '=', $primaryPid)
          ->addValue('role_id', $nonAttendeeRoleId)
          // We've just created this participant, and we're forcing the role assignment; skipping permission checks is probably needed and safe.
          ->setCheckPermissions(FALSE)
          ->execute();
      }
    }
    // Create relationships if needed, from the list of created participant IDs
    // in $form->__participantIDS.
    if ($isPromptRelated = CRM_Utils_Array::value('is_prompt_related', $groupregEventSettings)) {
      foreach ($form->getVar('_participantIDS') as $participantId) {
        // If it's the primary participant, just get the contact_id from the participant record.
        if ($participantId == $primaryPid) {
          $participant = \Civi\Api4\Participant::get()
            ->addWhere('id', '=', $participantId)
            // We need to get the contact ID for this participant, which may be denied if we don't have 'access civicrm'; thus, skip perm checks.
            ->setCheckPermissions(FALSE)
            ->execute()
            ->first();
          $primaryParticipantCid = $participant['contact_id'];
          continue;
        }
        // For all other participants, we'll create/update a relationship to the appropriate
        // contact (if $isPromptRelated is 'individual', then relate to primary participant contact; if
        // it's 'organization, then relate to the given organization).
        $participantParams = CRM_Utils_Array::value($participantId, $formValues['params']);
        // Relationship type was submitted as 'N_a_b' or 'N_b_a' in the form.
        // We can split it up and use those parts.
        $relationshipType = CRM_Utils_Array::value('groupregRelationshipType', $participantParams);
        if ($relationshipType) {
          if (
            ($participantGroupregOrganizationId = CRM_Utils_Array::value('groupregOrganization', $participantParams))
            && $isPromptRelated == CRM_Groupreg_Util::promptRelatedOrganization
          ) {
            $relatedCid = $participantGroupregOrganizationId;
          }
          else {
            $relatedCid = $primaryParticipantCid;
          }
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
              // We need to get the contact ID for this participant, which may be denied if we don't have 'access civicrm'; thus, skip perm checks.
              ->setCheckPermissions(FALSE)
              ->execute()
              ->first();
            $participantCid = $participant['contact_id'];
            // Use the 'create' api and populate known values.
            $relationship = \Civi\Api4\Relationship::create()
              ->addValue('contact_id_' . $rpos1, $relatedCid)
              ->addValue('contact_id_' . $rpos2, $participantCid)
              ->addValue('description', E::ts('Relationship created by Group Registration'))
              ->addValue($permission_column, 1);
            // For security, unless specified otherwise, we make all new permissioned relationships inactive,
            // pending staff review. Contact will be tagged for review.
            if ($groupregEventSettings['related_contact_tag_id']) {
              $relationship->addValue('is_active', 0);
              // This would also mean we're configured to tag such additional
              // participant contacts for review; do so now.
              if ($tagId = $groupregEventSettings['related_contact_tag_id']) {
                $entityTag = \Civi\Api4\EntityTag::create()
                  ->addValue('tag_id', $tagId)
                  ->addValue('entity_table', 'civicrm_contact')
                  ->addValue('entity_id', $participantCid)
                  // We need to tag this contact, regardless of our write access to the contact; thus, skip perm checks.
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
              // We need to save this relationship, regardless of our write access to the contact; thus, skip perm checks.
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
}

/**
 * Implements hook_civicrm_alterTemplateFile().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterTemplateFile/
 */
function groupreg_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  // When registering for an event, if the event is configured for related contacts
  // through organizations, and the user has no permissioned relationships with
  // organizations, prevent registering at all.
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    // Is event set for multiple participant registration?
    $event = \Civi\Api4\Event::get()
      ->addWhere('id', '=', $form->_eventId)
      // Viewing event config settings usually requires permission "access CiviCRM" or similar; therefore skip perm checks.
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();
    if (CRM_Utils_Array::value('is_multiple_registrations', $event)) {
      $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);
      $isPrimaryAttending = CRM_Utils_Array::value('is_primary_attending', $groupregEventSettings, CRM_Groupreg_Util::primaryIsAteendeeYes);
      $isPromptRelated = CRM_Utils_Array::value('is_prompt_related', $groupregEventSettings);

      if (
        $isPromptRelated == CRM_Groupreg_Util::promptRelatedOrganization
      ) {
        $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
        if (!CRM_Groupreg_Util::hasPermissionedRelatedContact($userCid, 'Organization')) {
          $message = E::ts('This event is for contacts in your related organizations, but you do not appear to have any related organizations. To continue with registration, please report this error message to our support team.');
          CRM_Core_Session::singleton()->setStatus($message, '', 'error');
          $tplName = 'CRM/Groupreg/Blank.tpl';
        }
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
      // Viewing event config settings usually requires permission "access CiviCRM" or similar; therefore skip perm checks.
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
        ], NULL, '<br />');

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
        if ($form->_flagSubmitted) {
          // Take specific action when form has been submitted, if the primary
          // registrant is not attending; namely, we need to avoid 'required'
          // validation on price fields that were hidden for this reason.
          //
          // To do this, we need to remove them from the list of 'required' elements
          // now; we can't do it in our validateForm hook implementation because
          // the core form validation runs first. Instead we do it here, so that the
          // core form validation won't enforce that required setting. We'll add
          // them back to the 'required' list in our own validateForm hook implementation,
          // so that they will properly default to being required when they aren't
          // hidden, eg. when the form reloads.
          $isRegisteringSelf = CRM_Utils_Array::value('isRegisteringSelf', $form->_submitValues, $groupregEventSettings['is_primary_attending']);
          // Only bother with this if primary registrant is not attending.
          if (!$isRegisteringSelf) {
            // Note any price fields which we've hidden because primary registrant is not attending.
            $hiddenPriceFieldNames = [];
            foreach (_groupreg_getNonAttendeeHiddenPriceFields($form->_eventId) as $hiddenPriceFieldId) {
              $hiddenPriceFieldNames[] = 'price_' . $hiddenPriceFieldId;
            }
          }
          $groupregTemporarilyUnrequiredFields = [];
          foreach ($hiddenPriceFieldNames as $hiddenFieldName) {
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
    $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0
      || (CRM_Utils_Array::value('is_primary_attending', $groupregEventSettings) == CRM_Groupreg_Util::primaryIsAteendeeNo)
    ) {
      // Change page title and status messages to reflect decremented
      // participant counts.
      $total = CRM_Utils_Array::value('additional_participants', $params[0]);
      $participantNo = substr($form->getVar('_name'), 12);
      CRM_Utils_System::setTitle(E::ts('Register Participant %1 of %2', array(1 => $participantNo, 2 => $total)));
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
        // Temporarily strip the 'groupregRelationshipType' field from the
        // "required" array (if it's required at all).
        // (We'll add it back later in hook_civicrm_validateForm().)
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
    $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);
    if (CRM_Utils_Array::value('isRegisteringSelf', $params[0], 1) == 0
      || (CRM_Utils_Array::value('is_primary_attending', $groupregEventSettings) == CRM_Groupreg_Util::primaryIsAteendeeNo)
    ) {
      _groupreg_correct_status_messages();
      CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_-decrement-counters.js');
    }
  }
}

function _groupreg_add_bhfe(array $elementNames, CRM_Core_Form &$form) {
  $bhfe = $form->getTemplateVars('beginHookFormElements');
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
      $form->addRadio('is_prompt_related', E::ts('Prompt for Additional Participant through relationships?'), [
        0 => E::ts("No"),
        CRM_Groupreg_Util::promptRelatedIndividual => E::ts("Yes, through direct relationships to primary participant"),
        CRM_Groupreg_Util::promptRelatedOrganization => E::ts("Yes, through relationships to related organizations"),
      ], NULL, '<BR />');

      $form->addElement('checkbox', 'is_require_existing_contact', E::ts('Require selection of existing contact for Additional Participants?'));

      $tagIdLabel = E::ts('Tag for relationship review on related contacts');
      $form->addElement(
        'select',
        'related_contact_tag_id',
        $tagIdLabel,
         ['' => E::ts('- select -')] + CRM_Core_BAO_EntityTag::buildOptions('tag_id') + ['-1' => E::ts('- NONE: ENABLE PERMISSIONED RELATIONSHIPS -')],
        ['class' => 'crm-select2']
      );
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
      'is_require_existing_contact',
      'related_contact_tag_id',
      'is_primary_attending',
      'nonattendee_role_id',
    ];
  }
  elseif ($formName == 'CRM_Event_Form_Registration_AdditionalParticipant') {
    if ($form !== NULL) {

      $groupregEventSettings = CRM_Groupreg_Util::getEventSettings($form->_eventId);

      if (!($groupregEventSettings['is_require_existing_contact'] ?? FALSE)) {
        $groupregPrefillContactRequired = FALSE;
        $groupregPrefillContactLabelOptional = E::ts('Optional') . ': ';
      }
      else {
        $groupregPrefillContactRequired = TRUE;
        $groupregPrefillContactLabelOptional = '';
      }

      $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();
      if (CRM_Utils_Array::value('is_prompt_related', $groupregEventSettings) == CRM_Groupreg_Util::promptRelatedIndividual) {
        if (CRM_Groupreg_Util::hasPermissionedRelatedContact($userCid, 'Individual')) {
          // EntityRef field for related contacts.
          $entityRefParams = [
            'create' => FALSE,
            'api' => [
              'params' => [
                // This param is watched for in CRM_Groupreg_APIWrappers_Contact::fromApiInput();
                'isGroupregRelated' => TRUE,
                'contact_type' => 'Individual',
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

        // Select2 list of relationship types.
        $relationshipTypeOptions = CRM_Groupreg_Util::getRelationshipTypeOptions('Individual');
        $form->add('select', 'groupregRelationshipType', E::ts('My relationship to this person'), $relationshipTypeOptions, TRUE, array('class' => 'crm-select2', 'style' => 'width: 100%;', 'placeholder' => '- ' . E::ts('SELECT') . '-'));
        $fieldNames = [
          'groupregPrefillContact',
          'groupregRelationshipType',
          'groupregRelationshipId',
        ];
      }
      elseif (CRM_Utils_Array::value('is_prompt_related', $groupregEventSettings) == CRM_Groupreg_Util::promptRelatedOrganization) {
        if (CRM_Groupreg_Util::hasPermissionedRelatedContact($userCid, 'Organization')) {
          $relatedOrgs = CRM_Groupreg_Util::getPermissionedContacts($userCid, 'Organization');
          $groupregOrganizationOptions = [];
          foreach ($relatedOrgs as $relatedOrgCid => $relatedOrg) {
            $groupregOrganizationOptions[$relatedOrgCid] = $relatedOrg['name'];
          }
          $form->add('select', 'groupregOrganization', E::ts('Select an organization'), $groupregOrganizationOptions, TRUE, [
            'class' => 'crm-select2',
            'style' => 'width: 100%;',
            'placeholder' => '- ' . E::ts('select') . '-',
          ]);

          $groupregPrefillContactLabel = $groupregPrefillContactLabelOptional . E::ts('Select an existing individual in this organization');
          $form->add('select', 'groupregPrefillContact', $groupregPrefillContactLabel, [], $groupregPrefillContactRequired, [
            'class' => 'crm-select2',
            'style' => 'width: 100%;',
            'placeholder' => '- ' . E::ts('SELECT ORGANIZATION FIRST') . '-',
          ]);

          // Hidden field to hold id of an existing relationship.
          $form->addElement('hidden', 'groupregRelationshipId', '', ['id' => 'groupregRelationshipId']);
          // Hidden field to hold id of any selected existing individual related to the organization; we need this
          // because the groupregPrefillContact <select> field has no options in buildForm, so therefore civicrm
          // will not store ANY value submitted for that form (remember, options are added to this <select> field
          // dynamically in JS). But we still need a way to recall which individual was actually selected, so we
          // need to store that contact_id in form params; therefore, we use this hidden field as storage for that ID.
          $form->addElement('hidden', 'groupregPrefillContactId', '', ['id' => 'groupregPrefillContactId']);

          CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_AdditionalParticipant-is-prompt-related.js');
          CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.groupreg', 'js/CRM_Event_Form_Registration_AdditionalParticipant-is-prompt-related-organization.js');
        }

        // Select2 list of relationship types.
        $relationshipTypeOptions = CRM_Groupreg_Util::getRelationshipTypeOptions('Organization');
        $form->add('select', 'groupregRelationshipType', E::ts("Organization's relationship to this person"), $relationshipTypeOptions, TRUE, array('class' => 'crm-select2', 'style' => 'width: 100%;', 'placeholder' => '- ' . E::ts('SELECT') . '-'));
        $fieldNames = [
          'groupregOrganization',
          'groupregPrefillContact',
          'groupregRelationshipType',
          'groupregRelationshipId',
        ];
      }
      $jsVars = [];
      // Either way (individual or org-based) we need to refresh the values in the dynamically built select field(s),
      // if the form is being reloaded, as in "continue"/"go back" buttons.
      $params = $form->getVar('_params');
      foreach ($params as $paramKey => $param) {
        if ($form->getVar('_name') == "Participant_{$paramKey}") {
          $jsVars = [
            'groupregOrganization' => CRM_Utils_Array::value('groupregOrganization', $param),
            'groupregRelationshipId' => CRM_Utils_Array::value('groupregRelationshipId', $param),
            'groupregRelationshipType' => CRM_Utils_Array::value('groupregRelationshipType', $param),
            'groupregPrefillContactId' => CRM_Utils_Array::value('groupregPrefillContactId', $param),
          ];
        }
      }
      $jsVars['isRequreExistingContact'] = (bool) CRM_Utils_Array::value('is_require_existing_contact', $groupregEventSettings);
      CRM_Core_Resources::singleton()->addVars('groupreg', $jsVars);
    }
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
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function groupreg_civicrm_install() {
  _groupreg_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function groupreg_civicrm_enable() {
  _groupreg_civix_civicrm_enable();
}
