(function(ts) {
  CRM.$(function($) {
    $('div.event_fees-group > strong').each(function(idx, el){
      var counter = $(el).html().replace(/^Participant ([0-9]+)$/, '$1');
      if (counter == 1) {
        $(el).html(ts('Registrant'));
      }
      else {
        --counter;
        $(el).html(ts('Participant') + ' ' + counter);
      }
    });
  });
}(CRM.ts('groupreg')));