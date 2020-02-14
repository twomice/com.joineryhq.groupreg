(function(ts) {
  CRM.$(function($) {
    
    // Inject markup to facilitate show/hide of "(including yourself)" label.      
    var selectNoOfParticipants = $('div#noOfparticipants div.content select').detach();
    $('div#noOfparticipants div.content span.description').hide();
    $('div#noOfparticipants div.content').wrapInner('<span id="noOfParticipants-extra">');
    $('span#noOfParticipants-extra').before(selectNoOfParticipants);


    /**
     * Compile a list of all ':hidden' fields and store that list in the 'groupregHiddenPriceFields'
     * hidden field, so it will be passed to the form handler.
     *
     * @param Event e
     */
    var groupregStoreHidden = function groupregStoreHidden(e) {
      var hiddenFields = [];
      $('div.groupreg-isNonAttendeeHidden:hidden input, div.groupreg-isNonAttendeeHidden:hidden select, div.groupreg-isNonAttendeeHidden:hidden textarea').each(function (idx, el) {
        // If this is a select2 base control, it will always be hidden. We only care
        // if the select2 itself is hidden.
        if ((el.type == 'select-one' || el.type == 'select-multiple') && $(el).hasClass('crm-select2')) {
          var select2id = 's2id_' + el.id;
          if ($('#' + select2id).is(':hidden')) {
            hiddenFields.push(el.name);
          }
        // If this is a datepicker base control, it will always be hidden. We only care
        // if the datepicker itself is hidden.
        } else if (
          el.type == 'text' &&
          ($(el).hasClass('crm-hidden-date') || el.hasAttribute('data-crm-datepicker'))
        ) {
          var datepickerid = $(el).siblings('input.hasDatepicker').attr('id');
          if ($('#' + datepickerid).is(':hidden')) {
            hiddenFields.push(el.name);
          }
        } else if (el.name.length) {
          hiddenFields.push(el.name);
        }
      });
      $('#groupregHiddenPriceFields').val(JSON.stringify(hiddenFields));
    };


    // Function to show or hide price fields for non-attending registrants.
    var showNonAttendeeHiddenPriceFields = function showNonAttendeeHiddenPriceFields(isShow) {
      if (isShow == undefined) {
        isShow = true;
      }
      if (isShow) {
        $('div.groupreg-isNonAttendeeHidden').show();
      }
      else {
        $('div.groupreg-isNonAttendeeHidden').hide();
      }
    };
    
    // Add an identifiable class to all price field divs for nonAttendeeHidden fields.
    for (var i in CRM.vars.groupreg.nonAttendeeHiddenPriceFields) {
      $('#price_'+ CRM.vars.groupreg.nonAttendeeHiddenPriceFields[i]).closest('div.crm-section').addClass('groupreg-isNonAttendeeHidden');
    }
    
    // If "primary is attending" is "user select":
    if (CRM.vars.groupreg.isPrimaryAttending == 2) {
      // Define a change handler for "are you attending" radios.
      var isRegisteringSelfChange = function isRegisteringSelfChange() {
        
        $('div#noOfparticipants').show();

        if($('input[type="radio"][name="isRegisteringSelf"][value="1"]').is(':checked')) {
          // Primary is attending.
          $('span#noOfParticipants-extra').show();
          showNonAttendeeHiddenPriceFields(true);
        }
        else {
          // Primary is NOT attending.
          $('span#noOfParticipants-extra').hide();
          showNonAttendeeHiddenPriceFields(false);
        }
        
        if($('input[type="radio"][name="isRegisteringSelf"]:checked').length) {
          divIsRegisteringSelf.nextAll().show();      
        }
      };

      // BHFE elements will be created in this form, presented in a table at top of page.
      // Add ID to bhfe table so we can work with it.
      $('input[type="radio"][name="isRegisteringSelf"]').closest('table').attr('id', 'bhfe_table');

      // Move bhfe elements into main table at top.
      var divIsRegisteringSelf = $('div.additional_participants-section').clone();
      divIsRegisteringSelf.removeClass('additional_participants-section');
      divIsRegisteringSelf.attr('id', 'divIsRegisteringSelf');
      divIsRegisteringSelf.css('padding-bottom', '1em');
      divIsRegisteringSelf.find('div.label').empty();
      divIsRegisteringSelf.find('div.label').append($('input[type="radio"][name="isRegisteringSelf"]').closest('tr').find('td.label label'));
      divIsRegisteringSelf.find('div.content').empty();
      divIsRegisteringSelf.find('div.content').append($('input[type="radio"][name="isRegisteringSelf"]').siblings());
      $('div.additional_participants-section').before(divIsRegisteringSelf);
      
      // Remove bhfe table; it should be empty now anyway.
      $('table#bhfe_table').remove();
      
      // Hide all other form fields; they need to answer this question first.
      divIsRegisteringSelf.nextAll().hide();

      // Assign change handler for our radios.
      $('input[type="radio"][name="isRegisteringSelf"]').change(isRegisteringSelfChange);
      // Go ahead and run that change hanlder on page load.
      isRegisteringSelfChange();
    }
    // If "parimary is attending" is "No":
    else if (CRM.vars.groupreg.isPrimaryAttending == 0) {
      // Re-label the "(including yourself)" label. 
//      $('span#noOfParticipants-extra').html('(' + ts('FIXME') + ')');      
          // Since "parimary is attending" is hard-coded to "No", we must hide
          // all non-attendee-hidden price fields.
          showNonAttendeeHiddenPriceFields(false);
    }
    
    // Add submit handler to form, to pass compiled list of hidden fields with submission.
    $('form#' + CRM.vars.groupreg.formId).submit(groupregStoreHidden);


    
  });
}(CRM.ts('groupreg')));