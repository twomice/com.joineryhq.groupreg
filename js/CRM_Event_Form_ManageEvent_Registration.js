(function(ts) {
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
        $('select#related_contact_tag_id').closest('tr').hide();
      }
      else {
        $('select#related_contact_tag_id').closest('tr').show();
      }
    };

    // Give the bhfe elements table an id so we can handle it later.
    $('input#is_hide_not_you').closest('table').attr('id', 'bhfe_table');

    var trMaxAdditional = $('select#max_additional_participants').closest('tr');
    // remove the 'nowrap' class because it breaks the layout.
    $('table#bhfe_table td').removeClass('nowrap');
    // Move all bhfe table rows into the main table aftrer 'max additional participants'
    $('table#bhfe_table tr').insertAfter(trMaxAdditional).addClass('hideIfNotMultiple');

    // Set change hanler for 'is_primary_atending' radios.
    $('input[name="is_primary_attending"]').change(isPrimaryAttendingChange);

    // Set change hanler for 'is_multiple', and go ahead and run it to start with.
    $('input[name="is_prompt_related"]').change(isPromptRelatedChange);

    // Set change hanler for 'is_multiple', and go ahead and run it to start with.
    $('input#is_multiple_registrations').change(isMultipleRegistrationsChange);
    isMultipleRegistrationsChange();

    // Remove the bhfe table, which should be empty by now.
    $('table#bhfe_table').remove();

  });
}(CRM.ts('com.joineryhq.groupreg')));