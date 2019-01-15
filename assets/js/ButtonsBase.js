const $ = require('jquery');
require('bootstrap');
const Update = require('./Update');

class Buttons {
    constructor(){

    }

    initialize(){

        $(".add-row-btn").click(this.addRow);
        $(".group-container h1").on('dblclick click', this.toggleGroupNameField);
        $(".group-container h1 i").click(this.toggleGroupNameField);
        $(".group-container input.group-name-input").focusout(this.toggleGroupNameField);

        /**
         * MODALS
         */

        let $login_modal_submit = $('#login_modal_submit');
        $login_modal_submit.click(() => {
            let data = {
                updateMethod: 'checkPassword',
                password: $("[name='password']").val(),
                school_id: $(".additional-information").data('default-properties').School,
                onReturn: 'checkPasswordResponse'
            };
            Update.send(data);
        });
        let predefined_pw = $('#login-modal input[name="password"]').val();
        if (typeof predefined_pw === "string" && predefined_pw !== '') {
            $login_modal_submit[0].click();
        }

        $('#forgot-pw-btn').click(() => {
            $('#login-modal').modal('hide');
            $('#password-modal').modal('show');

            $('#password-modal').show();
        });

        $('#forgotten_password_submit').click(() => {
            let modal_id = '#password-modal';
            let mail = $(modal_id + ' input[name="mailaddress"]').val();
            $.ajax({
                url: 'api/sendPasswordRecoverMail/' + mail,
                method: 'POST',
                success: function (data) {
                    if (data.status === 'success') {
                        $(modal_id + ' .modal-header').html('<h4>Vi har skickat ett mejl med en länk till din adress</h4>');
                        $(modal_id + ' .modal-body').html('<p>Kolla både inkorg och spamkorg</p>');
                        $(modal_id + ' .modal-footer').html('<button class="close" data-dismiss="modal"><span>X Stäng</span></button>');
                    } else {
                        $(modal_id + ' .modal-header').html('<h4>Vi kunde tyvärr inte hitta din adress</h4>');
                        $(modal_id + ' .modal-body').html('<p>Tyvärr finns din adress inte med bland våra aktiva medlemmar. Kolla med din administratör eller be en registrerad kollega att ge dig lösenordet.</p>');
                        $(modal_id + ' .modal-footer').html('<button class="close" data-dismiss="modal" ><span>X Stäng</span></button>');

                    }
                }
            });
        });

        $('button.go-to-groups').click(() => {
            window.location.replace($(this).data('url'));
        });

        $('div#show-school-pw button').popover({
            html: true,
            trigger: 'hover click',
            placement: 'bottom',
            content: () => {
                let content_id = "content-id-" + $.now();

                $.ajax({
                    url: 'api/getPasswordForSchool/' + $(this).data('school'),
                    method: 'POST'
                }).done( (data) => {
                    $('#' + content_id).html(data.password);
                });
                return '<div class="pw-popover" id="' + content_id + '">Laddar...</div>';
            }
        });

        $('input[name="hide-password"]').click(function(){
            let $input = $(this);
            let $password_field = $('input[name="password"]');
            let $type = 'text';
            if ($input.is(':checked')) {
                $type = 'password';
            }
            $password_field.attr('type', $type);
        });

        $('button#send-user-removal-mail').click(() => {
            let $form = $('div#remove-user-form');
            let users_ids = $('input:checkbox:checked', $form).map((i, el) => {
                return $(el).closest('tr').data('id');
            }).get();

            $.ajax({
                url: 'api/send_remove_user_mail',
                method: 'POST',
                data: {
                    users: users_ids,
                    reason: $('select#reason-selector option:selected').text(),
                    reason_text: $('textarea', $form).val()
                },
                success: (data) => {
                    // TODO: give feedback!
                }
            });
        });


    }

    toggleGroupNameField(event) {
        let h1 = $(event.target).closest('h1');
        let dataId = h1.closest(".group-container").attr("data-entity-id");
        let inputField = h1.children('input');
        if (event.type === 'click' || event.type === 'dblclick') {
            h1.children("span, i, svg").hide();
            inputField.val(h1.children('span').text());
            inputField.show().focus();

        } else if (event.type === 'focusout') {
            let newName = h1.children('input').hide().val();
            let data = {
                updateMethod: "changeGroupName",
                entity_id: dataId,
                value: newName,
                onReturn: "groupNameChanged"
            };
            Update.send(data);
            h1.children("span, i, svg").show();
        } else {
            console.log('The event.type ' + event.type + ' has no implementation');
        }
    }

    addRow() {
        let oldRow = $(".editable tbody tr:last");
        let newRow = oldRow.clone(true);
        newRow.hide();
        let tempId = '#' + Math.random().toString(36).substring(2, 6);
        newRow.attr("data-id", tempId).data("id", tempId);
        newRow.find(":input").val('').removeAttr('value').removeAttr('id');
        newRow.data('properties', undefined).removeProp('data-properties');
        //newRow.find(':input.datepicker').BSdatepicker("destroy");
        oldRow.after(newRow);
        if (!oldRow.is(':visible')) { // was only dummy row
            oldRow.remove();
        }
        newRow.removeAttr('hidden');
        newRow.show(1000);
    }

}

module.exports = new Buttons();
