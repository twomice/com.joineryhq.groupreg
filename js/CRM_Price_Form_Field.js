CRM.$(function($) {
  // Give the bhfe elements table an id so we can handle it later.
  $('input#is_hide_non_participant').closest('table').addClass('groupreg-bhfe-table');

  // Note our target table (or tbody).
  var trParent = $('input#is_active').closest('table').find('tr').parent();

  var tr;
  for (var i in CRM.vars.groupreg.bhfe_fields) {
    // Move all of our bhfe fields into that table after that row.
    tr = cj('table.groupreg-bhfe-table td [for^="' + CRM.vars.groupreg.bhfe_fields[i] + '"]').closest('tr');
    tr.attr('id', 'tr-' + tr.find('input').attr('name').split('[')[0]);
    tr.find('td:eq(0)').addClass('label');
    tr.find('td').removeClass('nowrap');
    trParent.append(tr);
  }

  // Remove the bhfe table, but only if it's empty.
  if (cj('table.groupreg-bhfe-table tr').length == 0) {
    cj('table.groupreg-bhfe-table').remove();
  }
});