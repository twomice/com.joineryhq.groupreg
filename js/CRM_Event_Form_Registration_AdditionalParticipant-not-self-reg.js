/**
 * This file is only included on Additional Participant form when the reistrant
 * has said they are not attending.
 *
 */

(function(ts) {
  CRM.$(function($) {

    var updatedContactFields = [];

    /**
     * JS change handler for "select a person" entityref field.
     *
     */
    var groupregPrefillContactChange = function groupregPrefillContactChange() {
      var newVal = $('#groupregPrefillContact').val();
      // Unfreeze any frozen fields, to start with a clean state.
      freezeContactFields(false);
      if (!newVal) {
        // If no contact selected, just return;
        return;
      }
      else {
        // Otherwise, fetch data for the contact.
        CRM.api3('Contact', 'get', {
          "sequential": 1,
          "id": newVal,
          "api.CustomValue.get": {}
        }).then(function(result) {
          // Upon returning api fetch, update fields as possible, and freeze some fields.
          populateContactFields(result.values[0]);
          freezeContactFields(true);
        }, function(error) {
        });
      }
    };

    /**
     * For a given contact record returned by api contact.get, populate fields
     * as much as possible.
     */
    var populateContactFields = function populateContactFields(contact) {
      var i;
      var selector;
      // First handle the native contact fields.
      for(i in contact) {
        // If a corresponding form field exists, update its value.
        selector = '#' + i;
        if ($(selector).length) {
          $(selector).val(contact[i]).change();
        }
      }

      // Also handle any custom fields that were returned.
      if (
        contact['api.CustomValue.get'] &&
        contact['api.CustomValue.get'].values
      )
      for (i in contact['api.CustomValue.get'].values) {
        // If a corresponding form field exists, update its value.
        var value = contact['api.CustomValue.get'].values[i];
        sselector = '#custom_' + value.id;
        if ($(selector).length) {
          $(selector).val(value.latest).change();
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
      if (doFreeze == undefined) {
        doFreeze = true;
      }

      // Freeze.
      if (doFreeze) {
        for (i in frozenTextFields) {
          el = $('#' + frozenTextFields[i]);
          if (el.val()) {
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
      var datePicker;
      var clone;

      // Set default value of doFreeze.
      if (doFreeze == undefined) {
        doFreeze = true;
      }
      // Freeze.
      if (doFreeze) {
        // Hard to actually freeze a datepicker field. Instead, clone it, then
        // hide it, and make the clone read-only.
        datePickerField = $('#birth_date').siblings('.hasDatepicker');
        clone = datePickerField.clone();
        clone.attr('readonly', true);
        clone.attr('id', 'groupregDatePickerClone');
        clone.removeClass('hasDatePicker');
        datePickerField.after(clone);
        datePickerField.hide();
      }
      // Un-freeze.
      else {
        // Remove the clone, if any, and display the original datepicker field.
        datePickerField = $('#birth_date').siblings('.hasDatepicker');
        clone = $('#groupregDatePickerClone');
        datePickerField.show();
        clone.remove();
      }
    };

    // Define change handler for the "select a person" field.
    $('#groupregPrefillContact').on('change', groupregPrefillContactChange);
    // Go ahead and run that change handler -- sometimes the field has a value
    // on page load (as in page reload after form validation failure).
    groupregPrefillContactChange();

    // Strip entityref filters so that they dont' confuse user.
    CRM.config.entityRef.filters.Contact = [];
  });
}(CRM.ts('com.joineryhq.groupreg')));