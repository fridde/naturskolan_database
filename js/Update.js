var Update = {

    send: function (data) {
        console.log("Request:");
        console.log(data);

        var options = {
            'method': 'POST',
            'data': data,
            'complete': function (jqXHR, status) {
                Response.handler(jqXHR, data.onReturn, status);
            },
            'error': Response.logErrorsToConsole
        };
        $.ajax(options);
    },

    lastChange: function (data) {

        if (data.success) {
            var tr = $('tr[data-id="' + data.old_id + '"]');
            if (data.new_id) {
                tr.attr("data-id", data.new_id).data("id", data.new_id);
                tr.removeAttr('data-properties').removeData('properties');
            } else if (data.old_properties) {
                var props = JSON.stringify(data.old_properties);
                tr.attr('data-properties', props).data('properties', props);
                Update.updateSaveTimeText('Raden sparas när alla obligatoriska uppgifter är med.');
                return;
            }
            Update.setSaveTime();
            Update.updateSaveTimeText();
        } else {
            // console.log(data);
        }
    },

    groupName: function (data) {
        if (data.success) {
            $("#group_name_" + data.groupId).text(data.newName);
            Update.lastChange(data);
        }
    },

    reloadPage: function (data, status) {
        if (data.success) {
            location.reload(true);
        }
    },

    removeRow: function (data, status) {
        if (status === "success" && data.status === "success") {
            var tr = $("tr").filter("[data-id='" + data.oldId + "']");
            tr.hide("slow", function () {
                tr.remove();
            });
            //$("tr").find("[data-id=" + data.oldId +  "]").remove();
        }
    },

    setSaveTime: function () {
        var currentTime = moment().format();
        $(".save-time").attr("data-last-change", currentTime).data("last-change", currentTime);
    },

    updateSaveTimeText: function (text) {
        var $saveTime = $('.save-time');
        if (typeof text === 'undefined') {
            var lastChange = $saveTime.data("last-change");
            if (typeof lastChange === 'undefined' || lastChange === '') {
                return;
            }
            text = "Sparades " + moment(lastChange).fromNow() + '.';
        }
        $saveTime.text(text);
        $saveTime.css("visibility", "visible");

    },

    wrongPassword: function (data) {
        // TODO: implement a feedback for a wrong password
    },

    showChange: function () {
        // TODO: implement this function to show the result of the operation
    },

    setSliderLabel: function (data) {
        if (data.success || data.direct) {
            $("#" + data.sliderId).attr("value", data.newValue);
            $("#" + data.sliderLabelId).text(data.newValue);

            Update.lastChange(data);
        }
    }
};
