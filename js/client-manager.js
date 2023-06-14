jQuery(document).ready(function() {
    jQuery('#pinresponse').text('');
    jQuery('#client-login input,#client-login p').fadeIn();
    jQuery(document).on('click','.grid .title', function() {
        jQuery(this).find('.details').toggleClass('show');
    });
    //jQuery('#client-pin').focus();
});
if(jQuery('#client-pin').val() != '') {
    jQuery('#pinrequest').hide();
    sendPinData();
}
jQuery('#pinrequest').on('submit',function(e){
    e.preventDefault();
    sendPinData();

});

function sendPinData() {
    jQuery('#pinresponse').text('Searching...');
    var pin = jQuery('#client-pin').val();
    var data = {
        'action': 'get_client',
        'pin' : pin,
    };	
    jQuery.ajax({
        url: ajax_object.ajax_url,
        type: 'post',
        data: data
    }).done(function(response) {
        //console.log(response);
        jQuery('#pinresponse').html(response);
        if(response == 'Welcome admin' || response == 'Could not sign you in') {
            location.reload();
        }
        else if(response != 'Invalid PIN') {
            updateTimeLog('new');
        }
    });
}
function updateTimeLog(state) {
    var access = jQuery('#accountfound').attr('data-access');
    var clients = access.split(',');
    jQuery('#allhours .grid').each(function() {
        //if(!jQuery(this).hasClass('total')) {
        jQuery(this).addClass('no-access');
        //}
    });
    var rate = 0;
    jQuery.each(clients, function (index, value) {
        //console.log(value);
        jQuery('[data-category='+value+']').each(function() {
            jQuery(this).parent().addClass('has-access');
        });
        jQuery('#allhours .grid').each(function() {
            var $this = jQuery(this);
            var client = jQuery(this).find('.title').attr('data-client');
            if(client == value) {
                $this.removeClass('no-access');
                rate = jQuery(this).find('.title').attr('data-rate');
            } else {
                //alert(client + ' '+ value);
            }
        });
       // alert(value);
    });
    jQuery('.no-access').remove();
    jQuery('p.note').remove();
    var totalTime = 0;
    
    jQuery('#allhours .grid').each(function() {
        var time = parseFloat(jQuery(this).find('span.hours').text());
        totalTime += time;
    });
    var totalcost = parseInt(rate) * totalTime;
    var formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',

        // These options are needed to round to whole numbers if that's what you want.
        //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
        //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
        });

    jQuery('#allhours').append('<div class="grid total"><div>Total Time</div><div>'+totalTime+' hours, '+formatter.format(totalcost)+'<div></div>');
    jQuery('#allhours').append('<p class="note">Tip: When reviewing total hours for each client, click on the client name to get more detailed information for hours logged.</p>');
    jQuery('body').addClass('has-access');
    if(state == 'new') {
        jQuery('#client-login').fadeOut();
    }
}