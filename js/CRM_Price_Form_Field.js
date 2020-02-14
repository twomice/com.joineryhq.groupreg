(function(ts) {
  CRM.$(function($) {

    // Give the bhfe elements table an id so we can handle it later.
    $('input#is_hide_non_participant').closest('table').attr('id', 'bhfe_table');

    var trLast = $('input#is_active').closest('table').find('tr').last();
    
    // remove the 'nowrap' class because it breaks the layout.
    $('table#bhfe_table td').removeClass('nowrap');
    // Move all bhfe table rows into the very last row of the settings table
    $('table#bhfe_table tr').insertAfter(trLast);

    // Remove the bhfe table, which should be empty by now.
    $('table#bhfe_table').remove();

  });
}(CRM.ts('com.joineryhq.groupreg')));