(function(ts) {
  CRM.$(function($) {
    
    // Inject markup to facilitate show/hide of "(including yourself)" label.      
    var selectNoOfParticipants = $('div#noOfparticipants div.content select').detach();
    $('div#noOfparticipants div.content span.description').hide();
    $('div#noOfparticipants div.content').wrapInner('<span id="noOfParticipants-extra">');
    $('span#noOfParticipants-extra').before(selectNoOfParticipants);

    // If "parimary is attending" is "user select":
    if (CRM.vars.groupreg.isPrimaryAttending == 2) {
      // Change handler for "are you attending" radios.
      var isRegisteringSelfChange = function isRegisteringSelfChange() {
        $('div#noOfparticipants').show();

        if($('input[type="radio"][name="isRegisteringSelf"][value="1"]').is(':checked')) {
          $('span#noOfParticipants-extra').show();
        }
        else {
          $('span#noOfParticipants-extra').hide();
        }
        divIsRegisteringSelf.nextAll().show();      
      };

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
    }
    // If "parimary is attending" is "No":
    else if (CRM.vars.groupreg.isPrimaryAttending == 0) {
      // Re-label the "(including yourself)" label. 
//      $('span#noOfParticipants-extra').html('(' + ts('FIXME') + ')');      
    }

    
  });
}(CRM.ts('groupreg')));