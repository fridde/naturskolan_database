var baseUrl;
var updateUrl = "update";
var recentChange = false;
var saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function(){

	baseUrl = $("base").attr("href");
	$.ajaxSetup({
		url: baseUrl + updateUrl,
		complete: Response.handler,
		type: 'POST',
		dataType: 'json'
	});

	setInterval(Update.setSaveTimeText, 30*1000);
	$('.modal').modal('show');
	$('#login_modal_submit').click(function(){
		var data = {
			updateType: "checkPassword",
			password: $("[name='password']").val(),
			onReturn: 'passwordCorrect'
		};
		Update.updateProperty(data);
	});

	$(".sortable").sortable({
		change: function(event, ui){
			var data = {updateType: "sort",	values : {id: []}};
			$(this).filter("[data-id]").each(function(){
				data.values.id.push($(this).data("id"));
			});
			data.onReturn = 'lastChange';
			setTimeout(Update.updateProperty(data), saveDelay);
		}
	});

	/**
	 * Configuration of callbacks for several elements
	 */
	$(".group-container .editable").change("group", Edit.change);
	$(".editable").find(":input").not(".datepicker").change("tableInput", Edit.change);
	$(".editable").find(".datepicker").on("changeDate", ["tableInput", "datepicker"] , Edit.change);
	$("#group-change-modal input").change("groupModal", Edit.change);
	$(".group-container .input-slider").each(function(i,element){
		Slider.set($(this), "group");
	});
	$("table .input-slider").each(function(i,element){
		Slider.set($(this), "table");
	});

	$('[name="Food"]').tooltip({
		title: Tooltip.foodText,
		trigger: 'manual',
		html: true
	});
	$('[name="Mobil"]').tooltip({
		title: Tooltip.mobilText,
		trigger: 'manual',
		html: true
	});

	$(".datepicker").datepicker({
		format: "yyyy-mm-dd",
		weekStart: 1,
		calendarWeeks: true,
		language: 'sv'
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
			onReturn: 'reloadPage',
			values : {
				id : null,
				Parent: sibs.filter("select").val(),
				Name: sibs.filter("input").val()
			}
		};
		setTimeout(Update.updateProperty(data), saveDelay);
	});

	$(".delete-btn").click(function(){
		var data = {
			values: {
				id: $(this).closest("tr").data("id")
			},
			table: $(this).closest("table").data("entity"),
			updateType : "deleteRow",
			onReturn: 'removeRow'
		};
		setTimeout(Update.updateProperty(data), saveDelay);
	});

	$(".group-container h1").dblclick(function(){
		var changeModalString = "#group-change-modal";
		var container = $(this).closest(".group-container");
		var groupId = container.data("entity-id");

		$(changeModalString).attr("data-entity-id", groupId);
		$(changeModalString).data("entity-id", groupId);
		$(changeModalString + " .name-field").val($(this).text());
		$(changeModalString).dialog("open");
	});

	$("#group-change-modal").dialog({
		autoOpen: false,
		title: "Ändra gruppnamn"
	});

});
