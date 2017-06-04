var baseUrl;
var updateUrl = "update";
var recentChange = false;
var saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function(){

	baseUrl = $("base").attr("href");
	$.ajaxSetup({
		url: baseUrl + updateUrl,
		type: 'POST',
		dataType: 'json'
	});

	/**
	* Defines the interval between the "Uppdaterades senast..." text
	*/
	$('.save-time').css('visibility', 'hidden'); // initially blank
	setInterval(Update.setSaveTimeText, 10*1000);

	$('.modal').modal('show');
	var $login_modal_submit = $('#login_modal_submit');
    $login_modal_submit.click(function(){
		var data = {
			updateMethod: "checkPassword",
			password: $("[name='password']").val(),
			onReturn: 'passwordCorrect'
		};
		Update.send(data);
	});
	var predefined_pw = $('#login-modal input[name="password"]').val();
	if(typeof predefined_pw === "string" && predefined_pw !== ''){
        $login_modal_submit[0].click();
	}

    /**
	* Initialize sortable lists here
	*/
	$(".sortable").sortable();

	$(Batch.listIdentifier).sortable(Batch.sortableOptions);

	/**
	* ###################
	* Button definitions
	* ###################
	*/

	/**
	* This button is used on /batch/set_visits/{grade}
	* and sends away all configurations as a big array
	*
	*/
	$("button#send").click(function(){
		lists = [];
		$(Batch.listIdentifier).each(function(i, listobj){
			rows = [];
			$(listobj).find("li").each(function(i, itemobj){
				rows.push($(itemobj).attr("data-id"));
			});
			lists.push(rows);
		});
		var data = {
			updateMethod: "setVisits",
			value: lists,
			onReturn: 'visitsSet'
		};
		console.log(lists);
		//Update.send(data);
		// TODO: complete this function
	});
	/**
	* This button is used on /batch/add_dates/{topic_id}
	* and "cleans" the textarea after inserting date rows using a method written in
	* google spreadsheets. It removes empty rows and trims and sorts the rows.
	*/
	$(".add-dates button#clean").click(function(){
		var $text = $("textarea.date-lines");
		var lines = $text.val().split(/\r|\n|;/).map(function(i){
			return i.trim();
		}).filter(function(i){
			return i.length > 0;
		}).sort();
		$text.val(lines.join("\n"));
	});

	$('.set-group-count button#clean').click(function(){
        var $text = $("textarea.group-count-lines");
        var lines = $text.val().split(/\r|\n|;/).map(function(i){
            return i.trim();
        }).filter(function(i){
            return i.length > 0;
        });
        $text.val(lines.join("\n"));
    });

	/**
	* This button is used on /batch/add_dates/{topic_id}
	* and sends the date array in the textarea as "value"
	* and the topic_id as "entity_id"
	*/
	$(".add-dates button#add").click(function(){
		var textarea = $("textarea.date-lines");
		var lines = textarea.val().split(/\r|\n/);
		var data = {
			updateMethod : "addDates",
			topic_id: $('select.date-lines').val(),
			dates: lines,
			onReturn: "datesAdded"
		};
		Update.send(data);
	});

    $('.set-group-count button#update').click(function(){
        var $text = $("textarea.group-count-lines");
        var lines = $text.val().split(/\r|\n/).map(function(i){
            return i.split(',').map(function(j){return j.trim()});
        });
        var data = {
            updateMethod : "batchSetGroupCount",
            group_numbers : lines,
            start_year : $('#start-year-selector').val(),
            onReturn: "groupCountUpdated"
        };
        Update.send(data);
    });

	$("#add-row-btn").click(function(){
		var oldRow = $(".editable tbody tr:last");
		var newRow = oldRow.clone(true);
		var newId = "new#" + (oldRow.attr("data-id") || oldRow.data("id"));
		newRow.attr("data-id", newId).data("id", newId);
		newRow.hide();
		oldRow.after(newRow);
		newRow.show(1000);
		newRow.find(":input").val('').removeAttr('value');
	});

	/**
	* ###################
	* Click definitions
	* ###################
	*/

	var toggleGroupNameField = function(event){
		var h1 = $(event.target).closest('h1');
		var dataId = h1.closest(".group-container").attr("data-entity-id");
		var inputField = h1.children('input');
		if(event.type === 'click' || event.type === 'dblclick'){
			h1.children("span, i").hide();
			inputField.val(h1.children('span').text());
			inputField.show().focus();

		} else if (event.type === 'focusout'){
			var newName = h1.children('input').hide().val();
			var data = {
				updateMethod : "changeGroupName",
				entity_id: dataId,
				value: newName,
				onReturn: "groupNameChanged"
			};
			Update.send(data);
			h1.children("span, i").show();
		} else {
			console.log('The event.type ' + event.type + ' has no implementation');
		}
	};

	$(".group-container h1 span").dblclick(toggleGroupNameField);
	$(".group-container h1 i").click(toggleGroupNameField);
	$(".group-container input.group-name-input").focusout(toggleGroupNameField);

	/**
	* Configuration of callbacks for several elements
	*/
	$editable = $(".editable");
    $('.group-container .editable').change('group', Edit.change);
    $('table.editable :input').not(".datepicker").change("tableInput", Edit.change);
    $editable.filter(".datepicker").on("changeDate", ["tableInput", "datepicker"] , Edit.change);
	$("#group-change-modal input").change("groupModal", Edit.change);

	/**
	* Initializing tooltips, sliders and datepickers
	*/
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



	$("#group-change-modal").dialog({
		autoOpen: false,
		title: "Ändra gruppnamn"
	});

	if(typeof(DataTableConfigurator) !== "undefined"){
	    var $table = $("table");
        $table.DataTable(DataTableConfigurator.options($table));
	}


});
