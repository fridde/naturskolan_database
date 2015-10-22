$(document).ready(function() {

  var recent_update = 0;
  var saveTimeInterval = 3000; // given in milliseconds

  $("input, textarea, select").on("change keydown", function() {

    var tooSoon = false;
    var thisValue = $(this).val();
    var thisName = $(this).attr("name");
    var tagName = $(this).prop("tagName");
    var userId = $("#user").text();

    var dt = new Date();
    var nowInMilliseconds = Date.UTC(dt.getFullYear(), dt.getMonth(), dt.getDate(), dt.getHours(), dt.getMinutes(), dt.getSeconds());

    if (tagName == "INPUT" || "TEXTAREA") {
      var diff = nowInMilliseconds - recent_update;
      if (diff < saveTimeInterval) {
        tooSoon = true;
      }
    }

    if (!tooSoon) {
      var data = {
        name: thisName,
        value: thisValue,
        user: userId
      };


      $.ajax({
        data: data,
        type: 'POST',
        url: 'user_update.php',
        success: function(msg) {
          $("#saveResponse").text("Uppgifterna sparades senast kl " + dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds());
          $("#saveResponse").css('font-style', 'italic');
          var parts = msg.split("~");
          $("#h3_" + parts[0]).text(parts[1]);
          recent_update = nowInMilliseconds;
        }
      })
    }
  });

  /*


	var userUpdate = function(callerObject, callerFunction) {
		alert(callerObject);
		var thisValue = callerObject.val();
		var thisName = callerObject.attr("name");

		var data = {
			name: thisName,
			value: thisValue
		};


		$.ajax({
			data: data,
			type: 'POST',
			url: 'user_update.php',
			success: function(msg) {
				var dt = new Date();
				$("#saveResponse").text("Uppgifterna sparades senast kl " + dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds());
				$("#saveResponse").css('font-style', 'italic');
				var parts = msg.split("~");
				$("#h3_" + parts[0]).text(parts[1]);
			}
		});

	}

	$("input, textarea, select").on("change", userUpdate($(this), "bla"));

	*/

});
