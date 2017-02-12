var Edit = {

	change: function(event){
		var data = {};
		var option, specialInfo;
		if(recentChange !== false){
			clearTimeout(recentChange);
		}
		if(typeof event.data === "object"){
			option = event.data[0];
			specialInfo = event.data[1];
		} else if (typeof event.data === "string"){
			option = event.data;
		}

		switch(option){ // will contain the "option" added to the function

		case "group":
		data.updateType = "updateProperty";
		data.entity = "Group";
		data.entity_id = $(this).closest(".group-container").data("entity-id");
		data.property = $(this).prop("name").split('#').shift();
		data.value = $(this).val();
		break;

		case "tableInput":
		data.updateType = "updateProperty";
		data.entity = $(this).closest("table").data("entity");
		data.entity_id = $(this).closest("tr").data("id");
		data.property = $(this).prop("name").split('#').shift();
		if($(this).attr("type") == "radio"){
			data.value = $(this)
			.closest("tr").find("[name='" + $(this).attr("name") + "']:checked")
			.val();
		} else if (specialInfo === "datepicker"){
			if(event.dates.length > 1){
				data.value = event.dates;
			} else {
				data.value = event.format();
			}
		} else {
			data.value = $(this).val();
		}

		if($(this).closest("tr").data("old-id")){
			data.oldId = $(this).closest("tr").data("old-id");
		}
		break;

		case "groupModal":
		data.updateType = "updateGroupName";
		data.entity = "group";
		data.entity_id = $(this).closest("#group-change-modal").data("entity-id");
		data.property = $(this).prop("name").split('#').shift();
		data.value = $(this).val();
		break;

	}

	if($(this).prop("type") == "checkbox"){
		data.property = $(this).prop("name");
		if(data.property.endsWith('[]')){
			data.property = data.property.slice(0,-2);
		}
		var valueArray = [];
		var checkedBoxes = $(this).closest("fieldset").find(":checked");
		checkedBoxes.each(function(index, element){
			valueArray.push($(element).val());
		});
		data.value = valueArray.join();
	}

	if(["Food", "Mobil"].indexOf(data.property) != -1){
		Tooltip.check(this, data);
	}

	data.onReturn = 'lastChange';
	recentChange = setTimeout(Update.updateProperty(data), saveDelay);
}
};
