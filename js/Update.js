var Update = {

	lastChange: function(data){

		if(data.success){
			$(".save-time").attr("data-last-change", moment().format());
			Update.setSaveTimeText();
			if(data.new_id){
				var tr = $('tr[data-id="' + data.old_id + '"]');
				tr.attr("data-id", data.new_id).data("id", data.new_id);
			}
			else if(typeof data.newName !== 'undefined'){
				Update.groupName(data, status);
			}
		} else {
			console.log(data);
		}
	},

	groupName: function(data){
		if(data.success){
			$("div[data-entity-id='" + data.groupId + "'] h1").text(data.newName);
		}
	},

	reloadPage: function(data, status){
		if(data.success){
			location.reload(true);
		}
	},

	removeRow: function(data, status){
		if(status == "success" && data.status == "success"){
			var tr = $("tr").filter("[data-id='" + data.oldId +  "']");
			tr.hide("slow", function(){tr.remove(); });
			//$("tr").find("[data-id=" + data.oldId +  "]").remove();
		}
	},

	setSaveTimeText: function(){
		var lastChange = $(".save-time").data("last-change");
		if(lastChange !== undefined){
			$(".save-time").text("Uppgifterna sparades senast " + moment(lastChange).fromNow() + '.')
			.css("visibility", "visible");
		}
	},

	updateProperty: function(data){
		if(!data.updateType){ //i.e. falsy
			e = new Error("The updateType cannot be empty!");
			console.log(e.message);
		}
		console.log("Request:");
		console.log(data);
		var options = {'data': data};
		$.ajax(options);
	},

	passwordCorrect: function(data){
		if(data.success){
			$('.modal').modal('hide');
			var newData = {
				updateType: "setCookie",
				school: data.school,
				onReturn: 'setAndReload'
			};
			var options = {'data': newData};
			$.ajax(options);
		}
		else {
			// TODO: update modal and tell that password was incorrect
		}
	},

	setSliderLabel: function(data){
		if(data.success || data.direct){
			$("#" + data.sliderId).attr("value", data.newValue);
			$("#" + data.sliderLabelId).text(data.newValue);

			Update.lastChange(data);
		}
	}
};
