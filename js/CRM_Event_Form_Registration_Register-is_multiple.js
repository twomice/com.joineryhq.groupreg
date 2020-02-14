(function(ts) {
    CRM.$(function($) {
    if (CRM.vars.groupreg.isPrimaryAttending == undefined || CRM.vars.groupreg.isPrimaryAttending == 1) {
      // This is the default civicrm behavior; just return, we'll do nothing here.
      return;
    }

    /* Page-level variable indicating whether the registering user is attending.
     * Depending on event settings,this may be forced to false, forced to true,
     * or set by user selection. Defaults to true because that's civicrm core
     * expectation.
     *
     * @type Boolean
     */
    var isThisPrimaryAttending = true;

    /**
     * Array of jquery objects containing all options in original
     * additional_participants select list.
     *
     * @type jQuery object
     */
    var originalAdditionalParticipantsOptions = $('select#additional_participants option');

    /**
     * Abriged list of options for additional_participants, to be used when
     * registering participant is not attending.
     *
     * @type jQuery object
     */
    var abridgedAdditionalParticipantsOptions = [];

    /**
     * Prime the array abridgedAdditionalParticipantsOptions, if it's not been
     * done already.
     */
    var primeAbridgedAdditionalParticipantsOptions = function primeAbridgedAdditionalParticipantsOptions() {
      if (!abridgedAdditionalParticipantsOptions.length) {
        abridgedAdditionalParticipantsOptions = originalAdditionalParticipantsOptions.clone().filter('[value!=""]');
        abridgedAdditionalParticipantsOptions.each(function(idx, el){
          if (el.value > 0) {
            $(el).text(el.value);
          }
        });
      }
    };

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

    /**
     * Function to show or hide price fields for non-attending registrants.
     */
    var toggleNonAttendeeDisplay = function toggleNonAttendeeDisplay() {
      if (isThisPrimaryAttending) {
        $('div.groupreg-isNonAttendeeHidden').show();
        $('span#noOfParticipants-extra').show();
      }
      else {
        $('div.groupreg-isNonAttendeeHidden').hide();
        $('span#noOfParticipants-extra').hide();
      }
      rebuildAdditionalParticipantsOptions();
    };

    /**
     * Adjust the options in additional_participants as appropriate, depeding on
     * whether registring participant is attendee.
     */
    var rebuildAdditionalParticipantsOptions = function rebuildAdditionalParticipantsOptions() {
      var val = $('select#additional_participants').val();
      if (isThisPrimaryAttending) {
        $('select#additional_participants').empty().append(originalAdditionalParticipantsOptions).val(val);
      }
      else {
        primeAbridgedAdditionalParticipantsOptions();
        $('select#additional_participants').empty().append(abridgedAdditionalParticipantsOptions).val(val);
      }
      if (! $('select#additional_participants').val()) {
        $('select#additional_participants').val(-1);
      }

    };

    /**
     * Define a change handler for "are you attending" radios.
     */
    var isRegisteringSelfChange = function isRegisteringSelfChange() {
      if($('input[type="radio"][name="isRegisteringSelf"][value="0"]').is(':checked')) {
        // Primary is NOT attending.
        isThisPrimaryAttending = false;
      }
      else {
        // Primary is attending.
        isThisPrimaryAttending = true;
      }
      toggleNonAttendeeDisplay();

      if($('input[type="radio"][name="isRegisteringSelf"]:checked').length) {
        $('select#additional_participants').closest('div.crm-section').show();
      }
      additionalParticipantsChange();
    };

    /**
     * Define a change handler for "additional_participants"
     */
    var additionalParticipantsChange = function additionalParticipantsChange() {
      var section = $('select#additional_participants').closest('div.crm-section');
      if($('select#additional_participants').val() != -1)  {
        section.nextAll().show();
      }
      else {
        section.nextAll().hide();
      }
    };

    // Inject markup to facilitate show/hide of "(including yourself)" label.
    var selectNoOfParticipants = $('div#noOfparticipants div.content select').detach();
    $('div#noOfparticipants div.content span.description').hide();
    $('div#noOfparticipants div.content').wrapInner('<span id="noOfParticipants-extra">');
    $('span#noOfParticipants-extra').before(selectNoOfParticipants);

    // Add an identifiable class to all price field divs for nonAttendeeHidden fields.
    for (var i in CRM.vars.groupreg.nonAttendeeHiddenPriceFields) {
      $('#price_'+ CRM.vars.groupreg.nonAttendeeHiddenPriceFields[i]).closest('div.crm-section').addClass('groupreg-isNonAttendeeHidden');
    }

    // If "primary is attending" is "user select":
    if (CRM.vars.groupreg.isPrimaryAttending == 2) {
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
      // Primary is NOT attending.
      isThisPrimaryAttending = false;

      // Re-label the "(including yourself)" label.
//      $('span#noOfParticipants-extra').html('(' + ts('This does not include yourself.') + ')');
          // Since "parimary is attending" is hard-coded to "No", we must hide
          // all non-attendee-hidden price fields.
    }

    // Assign change handler for "additional_participants".
    $('select#additional_participants').change(additionalParticipantsChange);
    additionalParticipantsChange();

    toggleNonAttendeeDisplay();

    // Add submit handler to form, to pass compiled list of hidden fields with submission.
    $('form#' + CRM.vars.groupreg.formId).submit(groupregStoreHidden);

   // Hide "not you" message if called for
   if (CRM.vars.groupreg.isHideNotYou) {
     $('div#crm-event-register-different').hide();
   }

   // Ensure the form itself is finally visible. Until this point, it's been
   // hidden by its style attribute (see buildForm hook implementation), giving
   // us time to do all the show/hide stuff above.
   $('form#Register').show();

  });
}(CRM.ts('groupreg')));