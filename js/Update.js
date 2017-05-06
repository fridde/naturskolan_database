var Update = {

	lastChange: function(data){

		if(data.success){
			var time = moment().format();
			$(".save-time").attr("data-last-change", time).data("last-change", time);
			Update.setSaveTimeText();
			if(data.new_id){
				var tr = $('tr[data-id="' + data.old_id + '"]');
				tr.attr("data-id", data.new_id).data("id", data.new_id);
			}
		} else {
			// console.log(data);
		}
	},

	groupName: function(data){
		if(data.success){
			$("#group_name_" + data.groupId).text(data.newName);
			Update.lastChange(data);
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
		if(lastChange !== undefined && lastChange.length > 0){
			$(".save-time").text("Uppgifterna sparades senast " + moment(lastChange).fromNow() + '.');
			$(".save-time").css("visibility", "visible");
		}
	},

	send: function(data){
		$.ajax({'data': data});
	},

	updateProperty: function(data){
		if(!data.updateMethod){ //i.e. falsy
			e = new Error("The updateMethod cannot be empty!");
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
				updateMethod: "setCookie",
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
