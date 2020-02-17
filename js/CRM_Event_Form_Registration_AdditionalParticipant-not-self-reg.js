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
      // Unfreeze any frozen fields, to start with a clean state.
      freezeContactFields(false);
      
      var cid = $('#groupregPrefillContact').val();
      if (!cid) {
        // If no contact selected, just return.
        return;
      }
      // Otherwise, fetch data for the contact.
      CRM.api3('Contact', 'get', {
        "sequential": 1,
        "id": cid,
        "api.CustomValue.get": {}
      }).then(function(result) {
        // Upon returning api fetch, update fiels as possible, and freeze some fields.
        populateContactFields(result.values[0]);
        freezeContactFields(true);
      }, function(error) {
        console.log('API error: ', error);
      });
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

    // Define change handler foe rhw "select a person" field.
    $('#groupregPrefillContact').change(groupregPrefillContactChange);
    
    // Strip entityref filters so that they dont' confuse user.
    CRM.config.entityRef.filters.Contact = [];
  });
}(CRM.ts('com.joineryhq.groupreg')));