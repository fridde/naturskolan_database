let Tooltip = {

    check: function(element, data){

        if(this.tooltipNeeded(data.property, data.value)){
            $(element).tooltip('show');
        } else {
            $(element).tooltip('hide');
        }
    },

    getContent: function(name){
        let text = '';
        switch(name){
            case 'Food':
                text += 'Menade du laktos eller menade du mjölkprotein? Om du är osäker, ';
                text += '<a target="_blank" href="http://www.celiaki.se/mjolkallergi/vanliga-fragor-och-svar/">läs här.</a>';
                break;
            case 'Mobil':
                text += 'Du verkar ange ett fast nummer. Vi behöver ett mobilnummer ';
                text += 'för att kunna nå dig i sista sekunden.';
                break;
            case 'user-question':
                text += 'Om du saknar någon person här, gå till <a href="skola/';
				text += $("div.additional-information").data('default-properties').School;
				text += '/staff">Personal</a> i menyn och lägg till den du saknar.';
                break;
        }
        return text;
    },

    tooltipNeeded: function(property, text){
        let regex;
        switch(property){
            case "Food":
            regex = new RegExp(/\bmjölk\b/, 'iu');
            break;
            case "Mobil":
            regex = new RegExp(/^\s?(0|0046|\+46)(?=[12345689])/ , 'iu');
            break;
        }
        return text.search(regex) !== -1;
    }


};
