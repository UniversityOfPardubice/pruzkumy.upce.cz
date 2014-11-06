$(document).ready(function(){
  $('#movesubmitbtn').val('Odeslat přihlášku');
  var nextbtn = $('#movenextbtn');
  nextbtn.val('Přihlásit se');
  var items = $('.list-dropdown');
  var submit = false;
  $.each(items, function(i, item){
    if ($(item).text().indexOf('autosubmit')>=0) {
      $(item).hide();
      var select = $(item).find('select');
      var options = $(select).find('option');
      select.val($(options[1]).val());
      nextbtn.click();
    }
  });
});