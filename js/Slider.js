let Slider = {

	set : function(jqueryObj, optionParam){
		let data = {};
		let container, entity_id;

		if(optionParam == "group"){
			container = jqueryObj.closest(".group-container");
            entity_id = container.data("entity-id");
		} else if(optionParam == "entity"){
			container = jqueryObj.closest("table");
            entity_id = jqueryObj.closest("tr").data("entity-id");
		}

		data.sliderLabelId = jqueryObj.data("slider-label-id");
		data.sliderId = jqueryObj.attr("id");

		jqueryObj.slider({
			min: jqueryObj.data("min"),
			max: jqueryObj.data("max"),
			value: jqueryObj.attr("value"),
			animate: true,
			change: function(event, ui){
				data.updateMethod = "sliderUpdate";
				data.entity_class = container.data("entity");
				data.entity_id = entity_id;
				data.property = jqueryObj.attr("name");
				data.value = ui.value;
                data.onReturn = 'sliderChanged';
				setTimeout(Update.send(data), saveDelay);
			},
			slide: function(event, ui){
				data.direct = true;
				data.newValue = ui.value;
				Update.setSliderLabel(data);
			}
		});
	}
};
