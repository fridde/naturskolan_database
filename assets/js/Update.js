const $ = require('jquery');
const moment = require('moment');
//let Response = require('./Response');

module.exports = class Update {

    constructor() {
    }

    static send(data) {

        console.group("Request");
        console.table(data);
        console.groupEnd();

        let options = {
            'method': 'POST',
            'data': data,
            'complete': function (jqXHR, status) {
                Update.handler(jqXHR, data.onReturn, status);
            },
            'error': this.logErrorsToConsole
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

    static getTranslationTable() {
        return {
            //wrongPassword: Update.wrongPassword,
            lastChange: this.lastChange,
            groupUserOptions: this.groupUserOptions,
            removeRow: this.removeRow,
            reloadPage: this.reloadPage,
            sliderChanged: this.setSliderLabel,
            datesAdded: this.reloadPage, // TODO: Maybe exchange this for a better feedback
            groupNameChanged: this.groupName,
            showAddedGroups: this.showAddedGroups,
            showSentManagerMails: this.showSentManagerMails,
            checkPasswordResponse: this.checkPasswordResponse
        };
    }

    static handler(jqXHR, onReturn, status) {
        if (status === 'success') {
            let data = jqXHR.responseJSON;

            this.logErrors(data);
            if (!(this.checkData(data))) {
                return false;
            }
            this.logDataToConsole(data);

            let callbackHandler = this.getCallback(onReturn);
            if (callbackHandler === false) {
                return false;
            }
            return callbackHandler.call(this, data);
        }
        console.warn('Ajax request returned with errors: ' + status);
        return false;
    }

    static checkData(data) {
        if (typeof data === 'undefined') {
            console.log('The response was empty.');
            return false;
        }
        return true;
    }

    static logErrors(data) {
        if (data.errors.length > 0) {
            console.group('ResponseErrors');
            console.log(data.errors);
            console.groupEnd();
            return true;
        }
        return false;
    }

    static logDataToConsole(data) {
        console.group("Response");
        console.table(data);
        console.groupEnd();
    }

    static getCallback(onReturn) {
        let callbackTranslator = this.getTranslationTable();

        if (!(onReturn in callbackTranslator)) {
            console.warn("The return function <" + onReturn + "> was not defined in Response.js");
            return false;
        }
        return callbackTranslator[onReturn];

    }

    static logErrorsToConsole(jqXHR, textStatus, errorThrown) {
        console.group('Errors');
        console.log(textStatus);
        console.log(errorThrown);
        console.log(jqXHR.responseText);
        console.groupEnd();
    }


};

//module.exports = Update;
