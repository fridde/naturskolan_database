$(document).ready(function() {
	
	$("input, textarea, select").change(function(){
		
		var thisValue = $(this).val();
		var thisName = $(this).attr("name");
		
		var data = {name: thisName , value : thisValue};
		 
		
		$.ajax({
			data: data,
			type: 'POST',
			url: 'user_update.php',
			success: function(msg){
				var dt = new Date();
				$("#saveResponse").text("Sparades senast " + dt.getHours() + ":" +  dt.getMinutes() + ":" + dt.getSeconds());
				var parts = msg.split("~");
				$("#h3_" + parts[0]).text(parts[1]);
				
			}
		})
	});
	
	
});