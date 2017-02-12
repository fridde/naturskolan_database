var Tooltip = {

    check: function(element, data){

        if(this.tooltipNeeded(data.property, data.value)){
            $(element).tooltip('show');
        } else {
            $(element).tooltip('hide');
        }
    },

    foodText: function(){
        text = 'Menade du laktos eller menade du mjölkprotein? Om du är osäker, ';
        text += '<a href="http://www.celiaki.se/mjolkallergi/vanliga-fragor-och-svar/">läs här.</a>';
        return text;
    },

    mobilText: function(){
        text = 'Du verkar ange ett fast nummer. Vi behöver ett mobilnummer ';
        text += 'för att kunna nå dig i sista sekunden.';
        return text;
    },

    tooltipNeeded: function(property, text){
        var regex;
        switch(property){
            case "Food":
            regex = new RegExp(/\bmjölk\b/, 'iu');
            break;
            case "Mobil":
            regex = new RegExp(/^\s?0|0046|\+46(?=[12345689])/ , 'iu');
            break;
        }

        return text.search(regex) != -1;
    }
};
