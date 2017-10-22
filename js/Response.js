var Response = {

    handler: function (jqXHR, onReturn, status) {
        console.log('Ajax request status: ' + status);
        if (status === 'success') {
            var data = jqXHR.responseJSON;

            if (!(this.checkData(data)) || this.hasErrors(data)){
                return false;
            }
            this.logDataToConsole(data);

            var callbackHandler = this.getCallback(onReturn);
            if(callbackHandler === false){
                return false;
            }
            return callbackHandler.call(this, data);
        }
        return false;
    },

    checkData: function (data) {
        if (typeof data === 'undefined') {
            console.log('The response was empty.');
            return false;
        }
        return true;
    },

    hasErrors: function (data) {
        if (data.errors.length > 0) {
            console.log('The response didn\'t come back without errors.');
            console.log(data.errors);
            return true;
        }
        return false;
    },

    logDataToConsole: function (data) {
        console.log("Response:");
        console.log(data);
    },

    getCallback: function (onReturn) {
        if (!(onReturn in Response.callbackTranslator)) {
            console.log("The return function <" + onReturn + "> was not defined in Response.js");
            return false;
        }
        return this.callbackTranslator[onReturn];

    },

    logErrorsToConsole: function (jqXHR, textStatus, errorThrown) {
        console.log(textStatus);
        console.log(errorThrown);
        console.log(jqXHR.responseText);
    },

    callbackTranslator: {
        passwordCorrect: Update.passwordCorrect,
        lastChange: Update.lastChange,
        setAndReload: Cookie.setAndReload,
        removeRow: Update.removeRow,
        reloadPage: Update.reloadPage,
        sliderChanged: Update.setSliderLabel,
        datesAdded: Update.reloadPage, // TODO: Maybe exchange this for a better feedback
        groupNameChanged: Update.groupName,
        removeCookie: Cookie.removeHashAndReload,
        showStatus: Update.showChange
    }

};
