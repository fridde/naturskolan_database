const $ = require('jquery');
const moment = require('moment');
const Response = require('./Response');

class Update {
    constructor() {

    }

    send(data) {

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
    }

    static lastChange(data) {

        if (data.success) {
            let tr = $('tr[data-id="#' + data.old_id + '"]');
            if (data.new_id) {
                tr.attr("data-id", data.new_id).data("id", data.new_id);
                tr.removeAttr('data-properties').removeData('properties');
            } else if (data.old_properties) {
                let props = JSON.stringify(data.old_properties);
                tr.attr('data-properties', props).data('properties', props);
                this.updateSaveTimeText('Raden sparas när alla obligatoriska uppgifter är med.');
                return;
            }
            this.setSaveTime();
            this.updateSaveTimeText();
        } else {
            // console.log(data);
        }
    }

    static groupName(data) {
        if (data.success) {
            $("#group_name_" + data.groupId).text(data.newName);
            this.lastChange(data);
        }
    }

    static groupUserOptions(data) {
        if (data.success && data.new_id) {
            let tr = $('tr[data-id="#' + data.old_id + '"]');
            let name = tr.find('input[name="FirstName"]').val();
            name += ' ' + tr.find('input[name="LastName"]').val();
            $('select[name="User"]').append(new Option(name, data.new_id));
        }
        this.lastChange(data);
    }

    static reloadPage() {
        location.reload(true);
    }

    static removeRow(data, status) {
        if (status === "success" && data.status === "success") {
            let tr = $("tr").filter("[data-id='" + data.oldId + "']");
            tr.hide("slow", () => tr.remove());
            //$("tr").find("[data-id=" + data.oldId +  "]").remove();
        }
    }

    static setSaveTime() {
        let currentTime = moment().format();
        $(".save-time").attr("data-last-change", currentTime).data("last-change", currentTime);
    }

    static updateSaveTimeText(text) {
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

    }

    static checkPasswordResponse(data) {
        let $rsp = $('#login-modal div.response-text');
        if (data.success === true) {
            $rsp.html('<div class="alert alert-success">Lösenordet är korrekt! <i class="far fa-grin fa-lg"></i></div>');
            setTimeout(() => {
                location.reload(true);
            }, 2000);
        } else {
            $rsp.html('<div class="alert alert-danger">Lösenordet godkändes ej <i class="far fa-sad-tear fa-lg"></i></div>');
            console.log('Bad password');
        }
    }

    static showAddedGroups(data) {
        let text = '<h5>Tillagda grupper:</h5>';
        if (data.added_groups.length === 0) {
            text += '<p>Inga grupper har lagts till.</p>';
        } else {
            text += '<ul><li>';
            text += data.added_groups.join('</li><li>');
            text += '</li></ul>';
        }
        $('#missingGroups div.result-box').html(text);
    }

    static showSentManagerMails(data) {
        let text = '<h5>Skickade mejl:</h5>';
        text += '<ul><li>';
        text += data.sent_mails.join('</li><li>');
        text += '</li></ul>';

        $('#manager-mobilization div.result-box').html(text);
    }

    static setSliderLabel(data) {
        if (data.success || data.direct) {
            $("#" + data.sliderId).attr("value", data.newValue);
            $("#" + data.sliderLabelId).text(data.newValue);

            this.lastChange(data);
        }
    }


}

module.exports = new Update();
