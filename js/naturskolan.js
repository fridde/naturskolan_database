var updateUrl = "update.php";
var recentChange = false;
var saveDelay = 10000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function(){

	setInterval(Update.setSaveTimeText, 30*1000);

	$('.modal').modal('show');
	$('#login_modal_submit').click(function(){
		var data = {
			updateType: "checkPassword", 
			"password": $("[name='password']").val()
		};
		Update.changeValue(data, Update.passwordCorrect);
	});

	$(".sortable").sortable({
		change: function(event, ui){
			var data = {updateType: "sort",	values : {id: []}};
			$(this).filter("[data-id]").each(function(){
				data.values.id.push($(this).data("id"));
			});
			setTimeout(Update.changeValue(data, Update.lastChange), saveDelay);
		}
	});

	$(".group-container .editable").change("group", Edit.change);
	$(".editable").find(":input:not(.special-column :input)").change("tableInput", Edit.change);
	$("#group-change-modal input").change("groupModal", Edit.change);
	$(".group-container .input-slider").each(function(i,element){
		Slider.set($(this), "group");
	});
	$("table .input-slider").each(function(i,element){
		Slider.set($(this), "table");
	});

	$(".date").datepicker({
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		showWeek: true
	});



	/* Buttons
	*/
	$("#add-row-btn").click(function(){
		var firstOrLast = $(this).data("first") == 1 ? "first" : "last";
		var oldRow = $(".editable tbody tr:" + firstOrLast);
		var newRow = oldRow.clone(true);
		var oldId = oldRow.data("old-id") || oldRow.data("id");
		newRow.attr("data-old-id", oldId).data("old-id", oldId);
		newRow.attr("data-id", "").data("id", "");
		newRow.hide();
		if(firstOrLast == "first"){
			oldRow.before(newRow);
		}
		else {
			oldRow.after(newRow);
		}
		newRow.show(1000);
		newRow.find(":input").val('').removeAttr('value');
	});

	$("#new-setting-btn").click(function(){
		var sibs = $(this).siblings();

		var data = {
			table : "settings",
			values : {
				id : null,
				Parent: sibs.filter("select").val(),
				Name: sibs.filter("input").val()
			}
		};
		setTimeout(Update.changeValue(data, Update.reloadPage), saveDelay);
	});

	$(".delete-btn").click(function(){
		var data = {
			values: {
				id: $(this).closest("tr").data("id")
			},
			table: $(this).closest("table").data("entity"),
			updateType : "deleteRow"
		};
		setTimeout(Update.changeValue(data, Update.removeRow), saveDelay);
	});

	$(".group-container h1").dblclick(function(){
		var changeModalString = "#group-change-modal";
		var container = $(this).closest(".group-container");
		var groupId = container.data("group-id");

		$(changeModalString).attr("data-group-id", groupId);
		$(changeModalString).data("group-id", groupId);
		$(changeModalString + " .name-field").val($(this).text());
		$(changeModalString).dialog("open");
	});

	$("#group-change-modal").dialog({
		autoOpen: false,
		title: "Ändra gruppnamn"
	});

});
/* END OF document.ready()
	#############################################################################################
	START OF Object definitions
*/

var Update = {

	lastChange: function(data, status){
		console.log(data);
		if(status === "success"){
			$(".save-time").attr("data-last-change", data.LastChange);
			Update.setSaveTimeText();
			if(typeof data.newId !== 'undefined'){
				var tr = $("tr[data-old-id='" + data.oldId + "']");
				tr.attr("data-id", data.newId).data("id", data.newId);
				tr.data("old-id", "").removeAttr("data-old-id");
			}
			else if(typeof data.newName !== 'undefined'){
				Update.groupName(data, status);
			}
		}
		else {
			console.log(data);
		}
	},

	groupName: function(data, status){
		if(status === "success"){
			$("div[data-group-id='" + data.groupId + "'] h1").text(data.newName);
		}
	},

	reloadPage: function(data, status){
		if(status === "success"){
			location.reload(true);
		}
	},

	removeRow: function(data, status){
		console.log(status);
		console.log(data);
		if(status == "success" && data.status == "success"){
			var tr = $("tr").filter("[data-id='" + data.oldId +  "']");
			tr.hide("slow", function(){tr.remove(); });
			//$("tr").find("[data-id=" + data.oldId +  "]").remove();
		}
	},

	setSaveTimeText: function(){
		var lastChange = $(".save-time").data("last-change");
		if(lastChange !== undefined){
			$(".save-time").text("Uppgifterna sparades senast " + moment(lastChange).fromNow() + '.');
		}
	},

	changeValue: function(data, successFunction){

		/* data can look like: {updateType: row, table: users, values: {id: 21, firstColumn:firstValue, secondColumnv:secondValue}}
		*/
		if(typeof data.updateType === 'undefined'){
			data.updateType = "";
		}
		var postData = $.param(data);
		console.log(postData);
		$.post(updateUrl, postData, successFunction, "json");
	},

	passwordCorrect: function(data, status){
		if(status == "success" && data.status == "success"){
			$('.modal').modal('hide');
			var postData = {
				updateType: "setCookie",
				school: data.school,
				table: "sessions"
			};
			$.post(updateUrl, $.param(postData), Cookie.setAndReload, "json");
		}
		else {
			// TODO update modal and tell that password was incorrect
		}
	}
};

var Cookie = {

	set : function(name, value, exdays) {
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = name + "=" + value + "; " + expires;
	},

	setAndReload : function(data, status){
		if(status == "success"){
			Cookie.set("Hash", data.hash, 90);
			location.reload();
		}
	},

	get : function(name) {
		name += "=";
		var ca = document.cookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) === 0) {
				return c.substring(name.length,c.length);
			}
		}
		return "";
	}
};

/**
 * [Edit description]
 * @type {Object}
 */
var Edit = {

	change: function(inputData){
		var data = {values:{}};
		var column;
		if(recentChange !== false){
			clearTimeout(recentChange);
		}

		switch(inputData.data){ // will contain the "option" added to the function

			case "group":
			data.entity = "groups";
			data.values.id = $(this).closest(".group-container").data("group-id");
			column = $(this).prop("name");
			break;

			case "tableInput":
			data.entity = $(this).closest("table").data("entity");
			data.values.id = $(this).closest("tr").data("id");
			if($(this).closest("tr").data("old-id")){
				data.oldId = $(this).closest("tr").data("old-id");
			}
			break;

			case "groupModal":
			data.entity = "group";
			data.values.id = $(this).closest("#group-change-modal").data("group-id");
			data.updateType = "updateGroupName";
			column = $(this).prop("name");
			break;

		}

		if($(this).prop("type") == "checkbox"){
			column = $(this).prop("name");
			if(column.endsWith('[]')){
				column = column.slice(0,-2);
			}
			var valueArray = [];
			var checkedBoxes = $(this).closest("fieldset").find(":checked");
			checkedBoxes.each(function(index, element){
				valueArray.push($(element).val());
			});
			data.values[column] = valueArray.join();
		}
		else if(typeof column != 'undefined'){
			data.values[column] = $(this).val();
		}

		recentChange = setTimeout(Update.changeValue(data, Update.lastChange), saveDelay);
	}
};

var Slider = {

	set : function(jqueryObj, optionParam){

		var data = {values: {}}, container;

		if(optionParam == "group"){
			container = jqueryObj.closest(".group-container");
			data.values.id = container.data("group-id");
		}
		else if(optionParam == "entity"){
			container = jqueryObj.closest("table");
			data.values.id = jqueryObj.closest("tr").data("id");
		}
		data.entity = container.data("entity");

		jqueryObj.slider({
			min: jqueryObj.data("min"),
			max: jqueryObj.data("max"),
			value: jqueryObj.attr("value"),
			animate: true,
			slide: function(event, ui){
				jqueryObj.attr("value", ui.value);
				$("#"+ jqueryObj.data("slider-counter-id")).text(ui.value); // here we update the label
				data.values[jqueryObj.data("column")] = ui.value;
				setTimeout(Update.changeValue(data, Update.lastChange), saveDelay);
			}
		});
	}
};
