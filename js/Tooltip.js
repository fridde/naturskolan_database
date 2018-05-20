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
        let is_there = text.search(regex);
        return text.search(regex) !== -1;
    }


};
