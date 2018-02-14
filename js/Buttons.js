$(document).ready(function () {


    function cleanLines(text) {
        var lines = text.split(/\r|\n|;/).map(function (i) {
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
     * This button is used on /batch/set_visits/{grade}
     * and sends away all configurations as a big array
     *
     */
    $(".set-dates button#send").click(function () {
        lists = [];
        $(Batch.listIdentifier).each(function (i, listobj) {
            rows = [];
            $(listobj).find("li").each(function (i, itemobj) {
                rows.push($(itemobj).attr("data-id"));
            });
            lists.push(rows);
        });
        var data = {
            updateMethod: "setVisits",
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
        var $textElement = $("textarea.date-lines");
        $textElement.val(cleanLines($textElement.val()));
    });

    $('.set-group-count button#clean').click(function () {
        var $textElement = $("textarea.group-count-lines");
        $textElement.val(cleanLines($textElement.val()));
    });


    /**
     * This button is used on /batch/add_dates/{topic_id}
     * and sends the date array in the textarea as "value"
     * and the topic_id as "entity_id"
     */
    $(".add-dates button#add").click(function () {
        var textarea = $("textarea.date-lines");
        textarea.val(cleanLines(textarea.val()));
        var lines = textarea.val().split(/\r|\n/);
        var topic_id = $('select.date-lines').val();
        var update_method = "addDates";
        if (topic_id === "multiple") {
            update_method = 'addDatesForMultipleTopics';
        }

        var data = {
            updateMethod: update_method,
            topic_id: topic_id,
            dates: lines,
            onReturn: "datesAdded"
        };
        Update.send(data);
    });

    $('.set-group-count button#update').click(function () {
        var $text = $("textarea.group-count-lines");
        var lines = $text.val().split(/\r|\n/).map(function (i) {
            return i.split(',').map(function (j) {
                return j.trim()
            });
        });
        var data = {
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

    var toggleGroupNameField = function (event) {
        var h1 = $(event.target).closest('h1');
        var dataId = h1.closest(".group-container").attr("data-entity-id");
        var inputField = h1.children('input');
        if (event.type === 'click' || event.type === 'dblclick') {
            h1.children("span, i, svg").hide();
            inputField.val(h1.children('span').text());
            inputField.show().focus();

        } else if (event.type === 'focusout') {
            var newName = h1.children('input').hide().val();
            var data = {
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

    var addRow = function () {
        var oldRow = $(".editable tbody tr:last");
        var newRow = oldRow.clone(true);
        newRow.hide();
        var tempId = '#' + Math.random().toString(36).substring(2, 6);
        newRow.attr("data-id", tempId).data("id", tempId);
        newRow.find(":input").val('').removeAttr('value').removeAttr('id');
        newRow.data('properties', undefined).removeProp('data-properties');
        newRow.find(':input.datepicker').datepicker("destroy").datepicker(Settings.datepickerOptions);
        oldRow.after(newRow);
        if (!oldRow.is(':visible')) { // was only dummy row
            oldRow.remove();
        }
        newRow.removeAttr('hidden');
        newRow.show(1000);
    };

    $("#add-row-btn").click(addRow);
    $(".group-container h1").on('dblclick click', toggleGroupNameField);
    $(".group-container h1 i").click(toggleGroupNameField);
    $(".group-container input.group-name-input").focusout(toggleGroupNameField);

    $('#missingGroups button').click(function () {
        var data = {
            updateMethod: "createMissingGroups",
            grade: $('#missingGroups select[name="grade"]').val(),
            onReturn: 'showStatus'
        };
        Update.send(data);
    });

    $('#fill-empty-group-names button').click(function () {
        var data = {
            updateMethod: "fillEmptyGroupNames",
            grade: $('#fill-empty-group-names select[name="grade"]').val(),
            onReturn: 'showStatus'
        };
        Update.send(data);
    });

    $('#cron-task-activation :checkbox').click(function () {
        var data = {
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

    var $login_modal_submit = $('#login_modal_submit');
    $login_modal_submit.click(function () {
        var data = {
            updateMethod: "checkPassword",
            password: $("[name='password']").val(),
            onReturn: 'passwordCorrect'
        };
        Update.send(data);
    });
    var predefined_pw = $('#login-modal input[name="password"]').val();
    if (typeof predefined_pw === "string" && predefined_pw !== '') {
        $login_modal_submit[0].click();
    }

    $('#forgot-pw-btn').click(function () {
        $('#login-modal').modal('hide');
        $pw_modal = $('#password-modal').modal('show');

        $('#password-modal').show();
    });

    $('#forgotten_password_submit').click(function () {
        var modal_id = '#password-modal';
        var mail = $(modal_id + ' input[name="mailadress"]').val();
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

    $('div#show-entry-code button').popover({
        html: true,
        trigger: 'hover click',
        placement: 'bottom',
        content: function () {
            var content_id = "content-id-" + $.now();

            $.ajax({
                url: 'api/getPassword/' + $(this).data('school'),
                method: 'POST'
            }).done(function (data) {
                $('#' + content_id).html(data.password);
            });
            return '<div class="pw-popover" id="' + content_id + '">Laddar...</div>';
        }
    });

    $('input[name="hide-password"]').click(function () {
        var $input = $(this);
        var $password_field = $('input[name="password"]');
        var $type = 'text';
        if ($input.is(':checked')) {
            $type = 'password';
        }
        $password_field.attr('type', $type);
    });

});
