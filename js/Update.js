var Update = {

    send: function (data) {
        console.log("Request:");
        console.log(data);

        var options = {
            'data': data,
            'complete': function(jqXHR){
                Response.handler(jqXHR, data.onReturn);
            }
        };
        $.ajax(options);
    },

    lastChange: function (data) {

        if (data.success) {
            var time = moment().format();
            $(".save-time").attr("data-last-change", time).data("last-change", time);
            Update.setSaveTimeText();
            if (data.new_id) {
                var tr = $('tr[data-id="new#' + data.old_id + '"]');
                tr.attr("data-id", data.new_id).data("id", data.new_id);
            }
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
        if (status == "success" && data.status == "success") {
            var tr = $("tr").filter("[data-id='" + data.oldId + "']");
            tr.hide("slow", function () {
                tr.remove();
            });
            //$("tr").find("[data-id=" + data.oldId +  "]").remove();
        }
    },

    setSaveTimeText: function () {
        var lastChange = $(".save-time").data("last-change");
        if (lastChange !== undefined && lastChange.length > 0) {
            $(".save-time").text("Uppgifterna sparades senast " + moment(lastChange).fromNow() + '.');
            $(".save-time").css("visibility", "visible");
        }
    },

    passwordCorrect: function (data) {
        if (data.success) {
            $('.modal').modal('hide');
            var options = {
                updateMethod: "setCookie",
                school: data.school,
                url: window.location.pathname,
                onReturn: 'setAndReload'
            };
            Update.send(options);
        }
        else {
            // TODO: update modal and tell that password was incorrect
        }
    },

    showChange: function() {
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
