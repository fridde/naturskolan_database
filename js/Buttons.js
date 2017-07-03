$(document).ready(function () {


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
        console.log(lists);
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

    function cleanLines(text){
        var lines = text.split(/\r|\n|;/).map(function (i) {
            return i.trim();
        }).filter(function (i) {
            return i.length > 0;
        }).sort();
        return lines.join("\n");
    }

    /**
     * This button is used on /batch/add_dates/{topic_id}
     * and sends the date array in the textarea as "value"
     * and the topic_id as "entity_id"
     */
    $(".add-dates button#add").click(function () {
        var textarea = $("textarea.date-lines");
        textarea.val(cleanLines(textarea.val()));
        var lines = textarea.val().split(/\r|\n/);
        var data = {
            updateMethod: "addDates",
            topic_id: $('select.date-lines').val(),
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
            onReturn: "groupCountUpdated"
        };
        Update.send(data);
    });

    $("#add-row-btn").click(function () {
        var oldRow = $(".editable tbody tr:last");
        var newRow = oldRow.clone(true);
        var newId = "new#" + (oldRow.attr("data-id") || oldRow.data("id"));
        newRow.attr("data-id", newId).data("id", newId);
        newRow.hide();
        oldRow.after(newRow);
        newRow.show(1000);
        newRow.find(":input").val('').removeAttr('value');
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
            h1.children("span, i").hide();
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
            h1.children("span, i").show();
        } else {
            console.log('The event.type ' + event.type + ' has no implementation');
        }
    };

    $(".group-container h1 span").dblclick(toggleGroupNameField);
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

    $('#cron-task-activation :checkbox').click(function () {
        var data = {
            updateMethod: "changeTaskActivation",
            task_name: $(this).attr('name'),
            status: $(this).is(':checked'),
            onReturn: 'lastChange'
        };
        Update.send(data);
    })

});
