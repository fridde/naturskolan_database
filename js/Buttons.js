

$(document).ready(function () {


    function cleanLines(text) {
        let lines = text.split(/\r|\n|;/).map(function (i) {
            return i.trim();
        }).filter(function (i) {
            return i.length > 0;
        }).sort();
        return lines.join("\n");
    }

    /**
     * ###################
     * Button definitions
     * ###################
     */

    /**
     * This button is used on /batch/distribute_visits/{segment}
     * and sends away all configurations as a big array
     *
     */
    $(".set-dates button#send").click(function () {
        let lists = [];
        $(Batch.listIdentifier).each(function (i, listobj) {
            let rows = [];
            $(listobj).find("li").each(function (i, itemobj) {
                rows.push($(itemobj).attr("data-id"));
            });
            lists.push(rows);
        });
        let data = {
            updateMethod: "distributeVisits",
            value: lists,
            onReturn: 'showStatus'
        };
        Update.send(data);
    });
    /**
     * This button is used on /batch/add_dates/{topic_id}
     * and "cleans" the textarea after inserting date rows using a method written in
     * google spreadsheets. It removes empty rows and trims and sorts the rows.
     */
    $(".add-dates button#clean").click(function () {
        let $textElement = $("textarea.date-lines");
        $textElement.val(cleanLines($textElement.val()));
    });

    $('.set-group-count button#clean').click(function () {
        let $textElement = $("#group-count-lines");
        $textElement.val(cleanLines($textElement.val()));
    });


    /**
     * This button is used on /batch/add_dates/{topic_id}
     * and sends the date array in the textarea as "value"
     * and the topic_id as "entity_id"
     */
    $(".add-dates button#add").click(function () {
        let textarea = $("textarea.date-lines");
        textarea.val(cleanLines(textarea.val()));
        let lines = textarea.val().split(/\r|\n/);
        let topic_id = $('select.date-lines').val();
        let update_method = (topic_id === 'multiple'
                ? 'addDatesForMultipleTopics'
                : 'addDates'
        );

        let data = {
            updateMethod: update_method,
            topic_id: topic_id,
            dates: lines,
            onReturn: 'datesAdded'
        };
        Update.send(data);
    });

    $('.set-group-count button#update').click(function () {
        let $textElement = $("#group-count-lines");
        $textElement.val(cleanLines($textElement.val()));

        let lines = $textElement.val().split(/\n/).map(function (line) {
            return line.split(',').map(function (item) {
                return item.trim()
            });
        });
        let data = {
            updateMethod: "batchSetGroupCount",
            group_numbers: lines,
            start_year: $('#start-year-selector').val(),
            onReturn: "showStatus"
        };
        Update.send(data);
    });


    /**
     * ###################
     * Click definitions
     * ###################
     */

    let toggleGroupNameField = function (event) {
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
    };

    let addRow = function () {
        let oldRow = $(".editable tbody tr:last");
        let newRow = oldRow.clone(true);
        newRow.hide();
        let tempId = '#' + Math.random().toString(36).substring(2, 6);
        newRow.attr("data-id", tempId).data("id", tempId);
        newRow.find(":input").val('').removeAttr('value').removeAttr('id');
        newRow.data('properties', undefined).removeProp('data-properties');
        newRow.find(':input.datepicker').BSdatepicker("destroy");
        oldRow.after(newRow);
        if (!oldRow.is(':visible')) { // was only dummy row
            oldRow.remove();
        }
        newRow.removeAttr('hidden');
        newRow.show(1000);
    };

    $(".add-row-btn").click(addRow);
    $(".group-container h1").on('dblclick click', toggleGroupNameField);
    $(".group-container h1 i").click(toggleGroupNameField);
    $(".group-container input.group-name-input").focusout(toggleGroupNameField);

    $('#missingGroups button').click(function () {
        let data = {
            updateMethod: "createMissingGroups",
            segment: $('#missingGroups select[name="Segment"]').val(),
            onReturn: 'showAddedGroups'
        };
        Update.send(data);
    });

    $('#manager-mobilization button').click(function () {
        let options = {
            'url': 'admin/batch/send_manager_mobilization_mail',
            'method': 'POST',
            'complete': function (jqXHR, status) {
                Response.handler(jqXHR, 'showSentManagerMails', status);
            },
            'error': Response.logErrorsToConsole
        };
        $.ajax(options);
    });

    $('#cron-task-activation :checkbox').click(function () {
        let data = {
            updateMethod: "changeTaskActivation",
            task_name: $(this).attr('name'),
            status: $(this).is(':checked'),
            onReturn: 'lastChange'
        };
        Update.send(data);
    });


    /**
     * MODALS
     */

    let $login_modal_submit = $('#login_modal_submit');
    $login_modal_submit.click(function () {
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

    $('#forgot-pw-btn').click(function () {
        $('#login-modal').modal('hide');
        $('#password-modal').modal('show');

        $('#password-modal').show();
    });

    $('#forgotten_password_submit').click(function () {
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

    $('button.go-to-groups').click(function () {
        window.location.replace($(this).data('url'));
    });

    $('div#show-school-pw button').popover({
        html: true,
        trigger: 'hover click',
        placement: 'bottom',
        content: function () {
            let content_id = "content-id-" + $.now();

            $.ajax({
                url: 'api/getPasswordForSchool/' + $(this).data('school'),
                method: 'POST'
            }).done(function (data) {
                $('#' + content_id).html(data.password);
            });
            return '<div class="pw-popover" id="' + content_id + '">Laddar...</div>';
        }
    });

    $('input[name="hide-password"]').click(function () {
        let $input = $(this);
        let $password_field = $('input[name="password"]');
        let $type = 'text';
        if ($input.is(':checked')) {
            $type = 'password';
        }
        $password_field.attr('type', $type);
    });

    $('div#send-question button[type="submit"]').click(function () {
        let $form = $('div#send-question');
        $.ajax({
            url: 'api/send_contact_mail',
            method: 'POST',
            data: {
                captcha: grecaptcha.getResponse(),
                input_email: $('input[name="input_email"]').val(),
                input_message: $('textarea[name="input_message"]').val()
            },
            success: function (data) {
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

    $('.add-note-to-visit button').click(function () {
       $('.add-note-to-visit :button').removeClass('active');
       $(this).addClass('active');

       let prewritten = $('.add-note-to-visit .prewritten-notes').data('notes');
       $('.add-note-to-visit .editable').val(prewritten[$(this).data('user-id')]);
    });

    $('button.copy-to-clipboard').click(function(){
        $('textarea.area-to-copy').select();
        document.execCommand('copy');
    });

    $('button#send-user-removal-mail').click(() => {
        let $form = $('div#remove-user-form');
        let users_ids = $('input:checkbox:checked', $form).map((i, el) => {
            return $(el).closest('tr').data('id');
        }).get();
        /*
        let data = [];
        data.users = $('input:checkbox:checked', $form).map((i, el) => {
            return $(el).closest('tr').data('id');
        }).get();
        data.reason = $('select#reason-selector option:selected').text();
        data.reason_text = $('textarea', $form).val();
        */
        //console.log(data);

        $.ajax({
            url: 'api/send_remove_user_mail',
            method: 'POST',
            data: {
                users: users_ids,
                reason: $('select#reason-selector option:selected').text(),
                reason_text: $('textarea', $form).val()
            },
            success: function (data) {
            }
        });
    });

});
