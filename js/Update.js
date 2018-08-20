let Update = {

    send: function (data) {
        console.group("Request");
        console.table(data);
        console.groupEnd();

        let options = {
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
            let tr = $('tr[data-id="#' + data.old_id + '"]');
            if (data.new_id) {
                tr.attr("data-id", data.new_id).data("id", data.new_id);
                tr.removeAttr('data-properties').removeData('properties');
            } else if (data.old_properties) {
                let props = JSON.stringify(data.old_properties);
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

    reloadPage: function () {
       location.reload(true);
    },

    removeRow: function (data, status) {
        if (status === "success" && data.status === "success") {
            let tr = $("tr").filter("[data-id='" + data.oldId + "']");
            tr.hide("slow", function () {
                tr.remove();
            });
            //$("tr").find("[data-id=" + data.oldId +  "]").remove();
        }
    },

    setSaveTime: function () {
        let currentTime = moment().format();
        $(".save-time").attr("data-last-change", currentTime).data("last-change", currentTime);
    },

    updateSaveTimeText: function (text) {
        let $saveTime = $('.save-time');
        if (typeof text === 'undefined') {
            let lastChange = $saveTime.data("last-change");
            if (typeof lastChange === 'undefined' || lastChange === '') {
                return;
            }
            text = "Sparades " + moment(lastChange).fromNow() + '.';
        }
        $saveTime.text(text);
        $saveTime.css("visibility", "visible");

    },

    checkPasswordResponse: function(data){
        let $rsp = $('#login-modal div.response-text');
        if (data.success === true) {
            $rsp.html('<div class="alert alert-success">Lösenordet är korrekt!</div>');
            setTimeout(function(){location.reload(true);}, 2000);
        } else {
            $rsp.html('<div class="alert alert-danger">Lösenordet godkändes ej <i class="fas fa-sad-tear fa-2x"></i></div>');
            console.log('Bad password');
        }
    },

    showChange: function (data) {
        switch (data.method){
            case 'addMissingGroups':
                let text = '<h5>Tillagda grupper:</h5>';
                if(data.added_groups.length === 0){
                    text += '<p>Inga grupper har lagts till.</p>';
                } else {
                    text += '<ul><li>';
                    text += data.added_groups.join('</li><li>');
                    text += '</li></ul>';
                }
                $('#missingGroups div.result-box').html(text);
                break;

        }

    },

    setSliderLabel: function (data) {
        if (data.success || data.direct) {
            $("#" + data.sliderId).attr("value", data.newValue);
            $("#" + data.sliderLabelId).text(data.newValue);

            Update.lastChange(data);
        }
    }
};
