const Batch = require('./Batch');
const Update = require('./Update');

class Buttons {

    static initialize(){

        let self = this;

        /**
         * This button is used on /batch/distribute_visits/{segment}
         * and sends away all configurations as a big array
         *
         */
        $(".set-dates button#send").click(() => {
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
        $(".add-dates button#clean").click(function() {
            let $textElement = $("textarea.date-lines");
            $textElement.val(self.cleanLines($textElement.val()));
        });

        $('.set-group-count button#clean').click(function() {
            let $textElement = $("#group-count-lines");
            $textElement.val(self.cleanLines($textElement.val()));
        });


        /**
         * This button is used on /batch/add_dates/{topic_id}
         * and sends the date array in the textarea as "value"
         * and the topic_id as "entity_id"
         */
        $(".add-dates button#add").click(function() {
            let textarea = $("textarea.date-lines");
            textarea.val(self.cleanLines(textarea.val()));
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

        $('#manager-mobilization button').click(() => {
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

        $('#cron-task-activation :checkbox').click(function() {
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

        $('.add-note-to-visit button').click(function(){
            $('.add-note-to-visit :button').removeClass('active');
            $(this).addClass('active');

            let prewritten = $('.add-note-to-visit .prewritten-notes').data('notes');
            $('.add-note-to-visit .editable').val(prewritten[$(this).data('user-id')]);
        });

        $('button.copy-to-clipboard').click((e) => {
            let $id = $(e.target).data('target');
            $('#' + $id).select();
            document.execCommand('copy');
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

        $('.mail_selector').change((e) => {
            let $subject = $('input[name="subject"]:checked').val();
            let $segment = $('input[name="segment"]:checked').val();
            let $links = $('#download_all_mails, #refresh_mail_view');
            $links.each(function(i, e){
                $(e).attr('href', $(e).data('base') + $subject + '/'+ $segment);
                }
            );
        });

    }

    static cleanLines(text) {
        let lines = text.split(/\r|\n|;/).map((i) => {
            return i.trim();
        }).filter((i) => {
            return i.length > 0;
        }).sort();

        return lines.join("\n");
    }

    static toggleGroupNameField(event) {
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

    static addRow() {
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
    }


}

module.exports = Buttons;
