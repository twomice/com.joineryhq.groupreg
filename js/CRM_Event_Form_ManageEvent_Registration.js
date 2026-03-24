  CRM.$(function($) {

    // On-change handler for 'is_multiple' checkbox.
    var isMultipleRegistrationsChange = function isMultipleRegistrationsChange() {
      if($('input#is_multiple_registrations').is(':checked')) {
        $('tr.hideIfNotMultiple').show();
        $('tr.hideIfNotMultiple input').change();
      }
      else {
        $('tr.hideIfNotMultiple').hide();
      }
    };

    // On-change handler for 'is_primary_attending' radios.
    var isPrimaryAttendingChange = function isPrimaryAttendingChange() {
      var isPrimaryAttendingValue = $('input[name="is_primary_attending"]:checked').val();
      if (isPrimaryAttendingValue == 1) {
        $('select#nonattendee_role_id').closest('tr').hide();
      }
      else {
        $('select#nonattendee_role_id').closest('tr').show();
      }
    };

    // On-change handler for 'is_prompt_related' radios.
    var isPromptRelatedChange = function isPromptRelatedChange() {
      var isPromptRelatedValue = $('input[name="is_prompt_related"]:checked').val();
      if (isPromptRelatedValue == 0) {
        $('tr.hideIfNotIsPromptRelated').hide();
      }
      else {
        $('tr.hideIfNotIsPromptRelated').show();
        $('tr.hideIfNotIsPromptRelated input').change();
      }
    };

    // On-change handler for 'is_require_existing_contact' checkbox.
    var isRequireExistingContactChange = function isRequireExistingContactChange() {
      if ($('input[name="is_require_existing_contact"]').is(':checked')) {
        $('select#related_contact_tag_id').closest('tr').hide();
      }
      else {
        $('select#related_contact_tag_id').closest('tr').show();
      }
    };

    // Give the bhfe elements table a class so we can idenfify it later.
    $('input#is_hide_not_you').closest('table').addClass('groupreg-bhfe-table');

    var trMaxAdditional = $('select#max_additional_participants').closest('tr').next();
    for (var i in CRM.vars.groupreg.bhfe_fields) {
      // Move all of our bhfe fields into that table after that row.
      tr = cj('table.groupreg-bhfe-table td [for^="' + CRM.vars.groupreg.bhfe_fields[i] + '"]').closest('tr');
      if (!tr.length) {
        // No tr found? Might be a radio or otherwise make use of name="$fieldName".
        tr = cj('table.groupreg-bhfe-table td input[name="' + CRM.vars.groupreg.bhfe_fields[i] + '"]').closest('tr');
      }
      tr.find('td:eq(0)').addClass('label');
      tr.find('td').removeClass('nowrap');
      tr.insertBefore(trMaxAdditional).addClass('hideIfNotMultiple');
      
    }    
    // Remove the bhfe table, but only if it's empty.
    if (cj('table.groupreg-bhfe-table tr').length == 0) {
      cj('table.groupreg-bhfe-table').remove();
    }
    
    // Add tr classes to facilitate show/hide:
    $('input#is_require_existing_contact').closest('tr').addClass('hideIfNotIsPromptRelated');
    $('select#related_contact_tag_id').closest('tr').addClass('hideIfNotIsPromptRelated');

    // Set change handler for 'is_primary_atending' radios.
    $('input[name="is_primary_attending"]').change(isPrimaryAttendingChange);

    // Set change handler for 'is_multiple'.
    $('input[name="is_prompt_related"]').change(isPromptRelatedChange);

    // Set change handler for 'is_require_existing_contact'.
    $('input[name="is_require_existing_contact"]').change(isRequireExistingContactChange);

    // Set change handler for 'is_multiple', and go ahead and run it to start with.
    $('input#is_multiple_registrations').change(isMultipleRegistrationsChange);
    isMultipleRegistrationsChange();
  });