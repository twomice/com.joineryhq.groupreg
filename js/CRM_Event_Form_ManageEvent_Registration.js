(function(ts) {
  CRM.$(function($) {
    
    var isMultipleRegistrationsChange = function isMultipleRegistrationsChange() {
      if($('input#is_multiple_registrations').is(':checked')) {
        $('tr.hideIfNotMultiple').show();
      }
      else {
        $('tr.hideIfNotMultiple').hide();
      }
    }
    
    $('input#isHideNotYou').closest('table').attr('id', 'bhfe_table');
    
    var trMaxAdditional = $('select#max_additional_participants').closest('tr');
    $('table#bhfe_table td').removeClass('nowrap');
    $('table#bhfe_table tr').insertAfter(trMaxAdditional).addClass('hideIfNotMultiple');
    
    $('input#is_multiple_registrations').change(isMultipleRegistrationsChange);
    isMultipleRegistrationsChange();
    
  });
}(CRM.ts('com.joineryhq.groupreg')));