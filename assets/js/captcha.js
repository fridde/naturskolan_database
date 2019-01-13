const $ = require('jquery');
const grecaptcha = require('grecaptcha');

$(document).ready(()=>{

    $('div#send-question button[type="submit"]').click(() => {
        let $form = $('div#send-question');
        $.ajax({
            url: 'api/send_contact_mail',
            method: 'POST',
            data: {
                captcha: grecaptcha.getResponse(),
                input_email: $('input[name="input_email"]').val(),
                input_message: $('textarea[name="input_message"]').val()
            },
            success: (data) => {
                if(!data.captcha_success){
                    $form.prepend('<div class="alert alert-warning">Din CAPTCHA blev tyvärr inte godkänt. Testa igen!</div>');
                    grecaptcha.reset();
                    return;
                } else if (!data.address_success){
                    $form.prepend('<div class="alert alert-warning">Du har glömt att ange en mejladress.</div>');
                    return;
                } else if(!data.mail_success){
                    $form.prepend('<div class="alert alert-danger">Det blev nåt fel. Ditt meddelande har tyvärr inte kunnat skickas. Testa igen eller kontakta oss via kontaktsidan ovan.</div>');
                    return;
                }
                $form.html('<div class="alert alert-success">Ditt meddelande har skickats!</div>');
            }
        });
    });

});
