var updateUrl = "update.php";
var recentChange = false;
var saveDelay = 1000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function(){
	
	$('.modal').modal('show');
	$('#login_modal_submit').click(function(){
		var password = $("[name='password']").val();
		var data = {updateType: "password", "password": password};
		Update.changeValue(data, Update.passwordCorrect);
	});
	
	$(".sortable").sortable({
		change: function(event, ui){
			var data = "updateType=sort&";
			data += $(this).sortable("serialize");
			$.post(updateUrl, data);
		}
	});
	
	$(".group .editable").change(function(){
		var $this = $(this);
		if(recentChange !== false){
			clearTimeout(recentChange);
		}
		recentChange = setTimeout(function(){
			var data = {
				updateType : "group",
				table: "users",
				groupId: $this.closest(".group").data("group-id"),
				value: $this.val(),
				column: $this.attr("name") 
			};
			Update.changeValue(data, Update.lastChange);
		}, saveDelay);		
	});
	
	$(".editable").find(":input").on("change", function(){
		var $this = $(this);
		var data = {
			rowId: $this.closest("tr").data("id"),
			table: $this.closest("table").data("table")
		}
		if($this.attr("type") == "checkbox"){
			var columnString = $this.attr("name");
			if(columnString.substr(-2) == '[]') {
				columnString = columnString.substr(0, columnString.length - 2);
			}
			data.column = columnString;
			var valueArray = [];
			var checkedBoxes = $this.closest("fieldset").find(":checked");
			checkedBoxes.each(function(index, element){
				valueArray.push($(element).val());
			});
			data.value = valueArray.join();
		}
		else {
			data.column = $this.data("column");
			data.value = $this.val();
		}
		setTimeout(Update.changeValue(data, Update.lastChange), saveDelay);
	});
	
	setInterval(Update.setSaveTimeText, 30*1000);
	
	$(".date").datepicker({
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		showWeek: true
	});
	
	$(".input-slider").each(function(index, element){
		
		var $element = $(element);
		$element.slider({
			min: $element.data("min"),
			max: $element.data("max"),
			value: $element.attr("value"),
			animate: true,
			slide: function(event, ui){
				$element.attr("value", ui.value);
				$("#"+ $element.data("slider-id")).text(ui.value);
				
				var data = {
					rowId: $element.closest("tr").data("id"),
					table: $element.closest("table").data("table"),
					column: $element.data("column"),
					value: ui.value
				};
				setTimeout(Update.changeValue(data, Update.lastChange), saveDelay);
			}
		});
	});
	
	$("#add-row-btn").click(function(){
		var lastRow = $(".editable tr:last");
		var newRow = lastRow.clone(true);
		var newRowId = "new_" + newRow.data("id");
		lastRow.after(newRow);
		newRow.find(":input").val('').removeAttr('value');
		newRow.attr("data-id", newRowId).data("id", newRowId);
	});
});

var Update = {
	lastChange: function(data, status){
		console.log(status);
		console.log(data);
		if(status === "success"){
			$(".save-time").attr("data-last-change", data.LastChange);
			Update.setSaveTimeText();
			if(typeof data.newId !== 'undefined'){
				$("tr[data-id='" + data.oldId + "']").attr("data-id", data.newId).data("id", data.newId);
			}
		}
	},
	
	setSaveTimeText: function(){
		var lastChange = $(".save-time").data("last-change");	
		if(lastChange !== undefined){
			$(".save-time").text("Uppgifterna sparades senast " + moment(lastChange).fromNow() + '.');
		}
	},
	
	changeValue: function(data, successFunction){
		
		/* data should have: {updateType: row, table: users, rowId: 21, column:Name, value:Fridde}
		*/
		if(typeof data.updateType === 'undefined'){
			data.updateType = "row";
		}
		var postData = $.param(data);
		console.log(postData);
		$.post(updateUrl, postData, successFunction, "json");	
	},
	
	passwordCorrect: function(data, status){
		if(status == "success" && data.status == "success"){
			$('.modal').modal('hide');
			$.post(updateUrl, "updateType=setCookie&school=" + data.school, Cookie.setAndReload, "json");
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
			if (c.indexOf(name) == 0) {
				return c.substring(name.length,c.length);
			}
		}
		return "";
	}
};