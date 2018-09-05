let Response = {

    callbackTranslator: {
        wrongPassword: Update.wrongPassword,
        lastChange: Update.lastChange,
        groupUserOptions: Update.groupUserOptions,
        removeRow: Update.removeRow,
        reloadPage: Update.reloadPage,
        sliderChanged: Update.setSliderLabel,
        datesAdded: Update.reloadPage, // TODO: Maybe exchange this for a better feedback
        groupNameChanged: Update.groupName,
        showAddedGroups: Update.showAddedGroups,
        showSentManagerMails: Update.showSentManagerMails,
        checkPasswordResponse: Update.checkPasswordResponse
    },

    handler: function (jqXHR, onReturn, status) {
        if (status === 'success') {
            let data = jqXHR.responseJSON;

            this.logErrors(data);
            if (!(this.checkData(data))){
                return false;
            }
            this.logDataToConsole(data);

            let callbackHandler = this.getCallback(onReturn);
            if(callbackHandler === false){
                return false;
            }
            return callbackHandler.call(this, data);
        }
        console.warn('Ajax request returned with errors: ' + status);
        return false;
    },

    checkData: function (data) {
        if (typeof data === 'undefined') {
            console.log('The response was empty.');
            return false;
        }
        return true;
    },

    logErrors: function (data) {
        if (data.errors.length > 0) {
            console.group('ResponseErrors');
            console.log(data.errors);
            console.groupEnd();
            return true;
        }
        return false;
    },

    logDataToConsole: function (data) {
        console.group("Response");
        console.table(data);
        console.groupEnd();
    },

    getCallback: function (onReturn) {
        if (!(onReturn in Response.callbackTranslator)) {
            console.warn("The return function <" + onReturn + "> was not defined in Response.js");
            return false;
        }
        return this.callbackTranslator[onReturn];

    },

    logErrorsToConsole: function (jqXHR, textStatus, errorThrown) {
        console.group('Errors');
        console.log(textStatus);
        console.log(errorThrown);
        console.log(jqXHR.responseText);
        console.groupEnd();
    }

};
