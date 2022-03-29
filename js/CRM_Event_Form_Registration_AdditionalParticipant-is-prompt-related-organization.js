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

    /**
     * JS change handler for "select a person" entityref field.
     *
     */
    var groupregOrganizationChange = function groupregOrganizationChange(e) {
      var newVal = $('#groupregOrganization').val();
      $('#groupregPrefillContact option').filter(function() {
        return this.value || $.trim(this.value).length > 0;
      }).remove();
      $('#groupregPrefillContact').val('').change();

      if (!newVal) {
        // If no contact selected, just return;
        $('#groupregRelationshipType').val('').change();
        $('#groupregRelationshipId').val('').change();
        return;
      }
      else {
        // Otherwise, we've selected an organization.
        // Fetch list of contacts for this org.
        CRM.api3('Contact', 'get', {
          "sequential": 1,
          "isGroupregRelated": 1,
          "contact_type": 'Individual',
          "groupregRelatedOrgId": newVal,
          "options": {"sort":"sort_name"},
          "return": ['id', 'display_name'],
        }).then(function(result) {
          // Upon returning, process the returned individuals:
          // Add found individuals to the list.
          for (var i in result.values) {
            $('#groupregPrefillContact').append('<option value="' + result.values[i].id + '">'+ result.values[i].display_name);
          }
          // Select the correct person in the list, if one is available onload.
          // We only do this one time per page load. The rationale here is that,
          // while page load usually means "coming to a blank page from the previous
          // page in the workflow", sometimes it means "I've hit civicrm's 'go back'
          // button one or more times, and I'm coming to this page after already
          // having completed it earlier, and it therefore has values in it already."
          // In those cases, if an individual was already selected, we use this
          // step here to select person in the list. And the only time we can do
          // that is AFTER the list of individuals has been populated by this
          // AJAX.then() function. But we only want to do it once per page load:
          // if I've changed the organization selected, and therefore the list of
          // individuals has been rebuilt, we don't want to force the previous
          // individual selection.
          if (onloadgroupregPrefillContactId) {
            $('#groupregPrefillContact').val(onloadgroupregPrefillContactId).change();
            // Clear this variable so we don't repeat this again on this page load.
            onloadgroupregPrefillContactId = false;
          }
          if (result.values.length) {
            $('#groupregPrefillContact').select2("container").show();
            $('#groupreg-message-no-individuals-found').hide();
          }
          else {
            $('#groupregPrefillContact').select2("container").hide();
            $('#groupreg-message-no-individuals-found').show();
          }
        }, function(error) {
        });
      }
    };

    // Define change handler for the "select an organization" field.
    $('#groupregOrganization').on('change', groupregOrganizationChange);
    // Go ahead and run that change handler -- sometimes the field has a value
    // on page load (as in page reload after form validation failure).
    groupregOrganizationChange();

    // Add "no individuals found" message, hidden, after "select a person" field
    $('#groupregPrefillContact').after('<span id="groupreg-message-no-individuals-found" style="display:none">' + ts('No individuals were found for this organization.') + '</span>');
    // Fix height of containing td, so it doesn't change when we show/hide 'select individual' control.
    CRM.$('#groupregPrefillContact').closest('td').css({
      'height': CRM.$('#groupregPrefillContact').closest('td').height(),
      'vertical-align': 'middle'
    });
  });
}(CRM.ts('com.joineryhq.groupreg')));