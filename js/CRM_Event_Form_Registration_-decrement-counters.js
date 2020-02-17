/**
 * File should only be included when isRegisteringSelf = false, so it will decrement
 * all participant counters by one.
 */

(function(ts) {
  CRM.$(function($) {
    // Function to alter given html string, decrementing participant count by 1.
    var decrementParticipant = function decrementParticipant(html) {
      var counter = html.replace(/^Participant ([0-9]+)$/, '$1');
      if (counter == 1) {
        // If this was originally 'participant 1', change it to 'Registrant'
        html = ts('Registrant');
      }
      else {
        // Otherwise, just decrement by one.
        --counter;
        html = ts('Participant') + ' ' + counter;
      }
      return html;
    };

    $('div.event_fees-group > strong, div.participant_info-group div.header-dark').each(function(idx, el){
      var html = decrementParticipant($(el).html().trim());
      $(el).html(html);
    });
  });
}(CRM.ts('groupreg')));