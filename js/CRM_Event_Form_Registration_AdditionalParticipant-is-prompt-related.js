/**
 * This file is only included on Additional Participant form when
 * a) the event is configured to prompt for related contacts; AND
 * b) the registrant actually has permissioned related contacts.
 */

// Single-use variable storing the contact ID of any individual who was
// selected if and when this form was submitted earlier in the workflow.
var onloadgroupregPrefillContactId = (CRM.vars.groupreg ? CRM.vars.groupreg.groupregPrefillContactId : false);

(function(ts) {
  CRM.$(function($) {

    var updatedContactFields = [];
    var onloadRelationshipId = (CRM.vars.groupreg ? CRM.vars.groupreg.groupregRelationshipId : false);
    var onloadRelationshipType = (CRM.vars.groupreg ? CRM.vars.groupreg.groupregRelationshipType : false);
    var isRequireExistingContact = (CRM.vars.groupreg ? CRM.vars.groupreg.isRequreExistingContact : false);
    var relationship_type;
    var relationship_id;

    /**
     * JS change handler for "select a person" entityref field.
     *
     */
    var groupregPrefillContactChange = function groupregPrefillContactChange(e) {
      var newVal = $('#groupregPrefillContact').val();
      // Unfreeze any frozen fields, to start with a clean state.
      freezeContactFields(false);
      if (!newVal) {
        // If no contact selected, just return;
        $('#groupregRelationshipType').val('').change();
        $('#groupregRelationshipId').val('').change();
        // If isRequireExistingContact, hide the rest of the form until one is selected.
        if (isRequireExistingContact) {
          $('div#groupreg_wrapper').hide();
        }
        return;
      }
      else {
        // Otherwise, we've selected somebody.
        // Update hidden groupregPrefillContactId with this contact_id.
        $('#groupregPrefillContactId').val(newVal);

        if (!onloadgroupregPrefillContactId) {
        // We'll update the contact form fields with live data on this contact,
        // but only if we don't have an onloadgroupregPrefillContactId. (If we have
        // that, it means we're coming to this Additional Participant form through
        // "next" or "continue" buttons, so we expect the contact fields to be already
        // edited by the user; therefore we would not want to update them with live
        // contact field values).


          // Update relationship type field based on selected data. Every selected
          // option should have an existing relationship ID and relationship type.
          if (e != undefined && e.added != undefined && e.added.extra != undefined) {
            relationship_type = e.added.extra.relationship_type_id + '_' + e.added.extra.rtype;
            relationship_id = e.added.extra.relationship_id;
            $('#groupregRelationshipType').val(relationship_type).change();
            $('#groupregRelationshipId').val(relationship_id).change();
          }

          // Fetch full data for the contact.
          CRM.api3('Contact', 'get', {
            "sequential": 1,
            "id": newVal,
            "isGroupregRelated": 1,
            "contact_type": 'Individual',
            "groupregRelatedOrgId": $('#groupregOrganization').val(),
            "api.CustomValue.get": {},
            "api.Phone.get": {},
            "api.Email.get": {},
            "api.Website.get": {},
            "api.Address.get": {}
          }).then(function(result) {
            // Upon returning api fetch, update fields as possible, and freeze some fields.
            populateContactFields(result.values[0]);
            freezeContactFields(true);

            if (!relationship_type) {
              relationship_type = result.values[0].relationship_type_id + '_' + result.values[0].rtype;
              relationship_id = result.values[0].relationship_id;
              $('#groupregRelationshipType').val(relationship_type).change();
  //            if (!$('#groupregRelationshipType').val()) {
  //              $('#groupregRelationshipType option[value^="' + result.values[0].relationship_type_id + '_"]').attr('selected', true).change();
  //            }
              $('#groupregRelationshipId').val(relationship_id).change();
            }
            if (onloadRelationshipId) {
              $('#groupregRelationshipId').val(onloadRelationshipId).change();
              onloadRelationshipId = false;
            }
          }, function(error) {
          });
        }

        // We're sure we have a contact, so we can show the rest of the form if it was hidden earlier.
        $('div#groupreg_wrapper').show();
      }
    };

    /**
     * For a given contact record returned by api contact.get, populate fields
     * as much as possible.
     */
    var populateContactFields = function populateContactFields(contact) {
      var i;
      var selector;
      var value;
      // First handle the native contact fields.
      for(i in contact) {
        // If a corresponding form field exists, update its value.
        selector = '#' + i;
        if ($(selector).length) {
          $(selector).val(contact[i]).change();
        }
      }

      // Also handle any custom fields that were returned.
      for (i in contact['api.CustomValue.get'].values) {
        // If a corresponding form field exists, update its value.
        value = contact['api.CustomValue.get'].values[i];
        selector = '#custom_' + value.id;
        if ($(selector).length) {
          $(selector).val(value.latest).change();
        }
      }

      // Also handle any phone fields that were returned.
      for (i in contact['api.Phone.get'].values) {
        // If a corresponding form field exists, update its value.
        value = contact['api.Phone.get'].values[i];
        // Phone:
        if (value.is_primary == '1') {
          selector = '#phone-Primary-' + value.phone_type_id;
        }
        else {
          selector = '#phone-' + value.location_type_id + '-' + value.phone_type_id;
        }
        if ($(selector).length) {
          $(selector).val(value.phone).change();
        }
        // Phone extension:
        if (value.is_primary == '1') {
          selector = '#phone_ext-Primary-' + value.phone_type_id;
        }
        else {
          selector = '#phone_ext-' + value.location_type_id + '-' + value.phone_type_id;
        }
        if ($(selector).length) {
          $(selector).val(value.phone_ext).change();
        }
      }

      // Also handle any Email fields that were returned.
      for (i in contact['api.Email.get'].values) {
        // If a corresponding form field exists, update its value.
        value = contact['api.Email.get'].values[i];
        if (value.is_primary == '1') {
          selector = '#email-Primary';
        }
        else {
          selector = '#email-' + value.location_type_id;
        }
        if ($(selector).length) {
          $(selector).val(value.email).change();
        }
      }

      // Also handle any Website fields that were returned.
      for (i in contact['api.Website.get'].values) {
        // If a corresponding form field exists, update its value.
        value = contact['api.Website.get'].values[i];
        selector = '#url-' + value.website_type_id;
        if ($(selector).length) {
          $(selector).val(value.url).change();
        }
      }

      // Also handle any Address fields that were returned.
      for (i in contact['api.Address.get'].values) {
        // If a corresponding form field exists, update its value.
        value = contact['api.Address.get'].values[i];
        var selectorSuffix;
        if (value.is_primary) {
          selectorSuffix = '-Primary';
        }
        else {
          selectorSuffix = '-' + value.location_type_id;
        }
        var textFieldNames = [
          'street_address',
          'supplemental_address_1',
          'supplemental_address_2',
          'supplemental_address_3',
          'city',
          'postal_code'
        ];
        var idFieldNames = [
          // TODO: find a way to do country first and then state; this breaks
          // at the moment, because of chainselect. So instead, we just hope
          // that country is already set by default.
          // 'country'
          'state_province'
        ];
        var f;
        var fieldName;
        for (f in textFieldNames) {
          fieldName = textFieldNames[f];
          selector = '#' + fieldName + selectorSuffix;
          if ($(selector).length) {
            $(selector).val(value[fieldName]).change();
          }
        }
        for (f in idFieldNames) {
          var selectorBase = idFieldNames[f];
          fieldName = selectorBase + '_id';
          selector = '#' + selectorBase + selectorSuffix;
          if ($(selector).length) {
            $(selector).val(value[fieldName]).change();
          }
        }
      }
    };

    /**
     * Freeze (or un-freeze) certain fields so they're not editable.
     *
     * @param Boolean doFreeze True for freeze, false  for unfreeze.
     */
    var freezeContactFields = function freezeContactFields(doFreeze) {
      var i;
      var el;
      // First we'll do the simple text fields; date fields need special handling.
      var frozenTextFields = [
        'first_name',
        'last_name',
      ];

      // Set default value of doFreeze.
      if (isRequireExistingContact) {
        // Always freeze fields if we're requiring additional contact.
        doFreeze = true;
      }
      else if (doFreeze == undefined) {
        doFreeze = true;
      }

      // Freeze.
      if (doFreeze) {
        for (i in frozenTextFields) {
          el = $('#' + frozenTextFields[i]);
          if (el.val() || isRequireExistingContact) {
            // Even if the field has no value, always freeze it if we're requiring additional contact.
            el.attr('readonly', true);
          }
        }
      }
      // Unfreeze.
      else {
        for (i in frozenTextFields) {
          el = $('#' + frozenTextFields[i]);
          if (el.val()) {
            el.attr('readonly', false);
          }
        }
      }

      freezeBirthDateField(doFreeze);
    };

    /**
     * Freeze (or un-freeze)birth date field.
     *
     * @param Boolean doFreeze True for freeze, false  for unfreeze.
     */
    var freezeBirthDateField = function freezeBirthDateField(doFreeze) {
      var datePickerField = $('#birth_date').siblings('.hasDatepicker');
      var datePickerClearLink = datePickerField.siblings('a.crm-clear-link');
      var clone;

      // Set default value of doFreeze.
      if (doFreeze == undefined) {
        doFreeze = true;
      }
      // Freeze.
      if (doFreeze) {
        // Hard to actually freeze a datepicker field. Instead, clone it, then
        // hide it, and make the clone read-only.
        clone = datePickerField.clone();
        clone.attr('readonly', true);
        clone.attr('id', 'groupregDatePickerClone');
        clone.removeClass('hasDatePicker');
        datePickerField.after(clone);
        datePickerField.hide();
        datePickerClearLink.hide();
      }
      // Un-freeze.
      else {
        // Remove the clone, if any, and display the original datepicker field.
        clone = $('#groupregDatePickerClone');
        datePickerField.show();
        datePickerClearLink.show();
        clone.remove();
      }
    };

    // BHFE elements will be created in this form, presented in a table at top of page.
    // Add ID to bhfe table so we can work with it.
    $('#groupregPrefillContact').closest('table').attr('id', 'bhfe_table');
    // Move everything after bhfe_table into a wrapper div.
    $('table#bhfe_table').after('<div id="groupreg_wrapper"></div>');
    $('div#groupreg_wrapper').nextAll().appendTo($('div#groupreg_wrapper'));
    // But move the form buttons outside of that wrapper, so the user can 
    // still navigate with buttons like 'skip participant' and 'previous'.
    $('div#groupreg_wrapper').after($('div#crm-submit-buttons'));

    // Define change handler for the "select a person" field.
    $('#groupregPrefillContact').on('change', groupregPrefillContactChange);
    // Go ahead and run that change handler -- sometimes the field has a value
    // on page load (as in page reload after form validation failure).
    groupregPrefillContactChange();

    // Strip entityref filters so that they dont' confuse user.
    CRM.config.entityRef.filters.Contact = [];

    if (onloadRelationshipType) {
      $('#groupregRelationshipType').val(onloadRelationshipType).change();
      onloadRelationshipType = false;
    }

  });
}(CRM.ts('com.joineryhq.groupreg')));