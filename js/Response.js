var Response = {

    handler: function(jqXHR, status){
        var data = jqXHR.responseJSON;
        console.log("Response:");
        console.log(data);
        if(typeof data == 'undefined' || data.errors.length > 0){
            console.log('The response didn\'t come back without errors.');
            console.log(data.errors);
        } else {
            var callbackHandler = Response.callbackTranslator[data.onReturn];
            response = callbackHandler.call(this, data);
        }
    },

    callbackTranslator : {
        passwordCorrect: Update.passwordCorrect,
        lastChange: Update.lastChange,
        setAndReload: Cookie.setAndReload,
        removeRow: Update.removeRow,
        reloadPage: Update.reloadPage,
        sliderChanged: Update.setSliderLabel,
        datesAdded: Update.reloadPage, // TODO: Maybe exchange this for a better feedback
        groupNameChanged: Update.groupName
    }

};
