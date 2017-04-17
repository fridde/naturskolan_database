var Slider = {

	set : function(jqueryObj, optionParam){
		var data = {};
		var container;

		if(optionParam == "group"){
			container = jqueryObj.closest(".group-container");
			data.entity_id = container.data("entity-id");
		} else if(optionParam == "entity"){
			container = jqueryObj.closest("table");
			data.entity_id = jqueryObj.closest("tr").data("entity-id");
		}

		data.sliderLabelId = jqueryObj.data("slider-label-id");
		data.sliderId = jqueryObj.attr("id");

		jqueryObj.slider({
			min: jqueryObj.data("min"),
			max: jqueryObj.data("max"),
			value: jqueryObj.attr("value"),
			animate: true,
			change: function(event, ui){
				data.updateType = "sliderUpdate";
				data.entity_class = container.data("entity");
				data.property = jqueryObj.attr("name");
				data.onReturn = 'sliderChanged';
				data.value = ui.value;
				setTimeout(Update.updateProperty(data), saveDelay);
			},
			slide: function(event, ui){
				data.direct = true;
				data.newValue = ui.value;
				Update.setSliderLabel(data);
			}
		});
	}
};
