/**
 * This file is only included on Additional Participant form when
 * a) the event is configured to prompt for related contacts; AND
 * b) the registrant actually has permissioned related contacts.
 */

(function(ts) {
  CRM.$(function($) {
    
    var onloadgrouprePrefillContact = (CRM.vars.groupreg ? CRM.vars.groupreg.grouprePrefillContact : false);
    
    /**
     * JS change handler for "select a person" entityref field.
     *
     */
    var groupregOrganizationChange = function groupregOrganizationChange(e) {
      var newVal = $('#groupregOrganization').val();
      console.log('groupregOrganizationChange', newVal);
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
          // Upon returning, add found individuals to the 
          for (var i in result.values) {
            $('#groupregPrefillContact').append('<option value="' + result.values[i].id + '">'+ result.values[i].display_name);
          }
          if (onloadgrouprePrefillContact) {
            $('#groupregPrefillContact').val(onloadgrouprePrefillContact).change();
            onloadgrouprePrefillContact = false;
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

  });
}(CRM.ts('com.joineryhq.groupreg')));