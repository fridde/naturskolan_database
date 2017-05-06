disabledClass = "sortable-disabled";

var Batch = {

    listIdentifier: 'div.set-dates ul',    
    sortableOptions: {
		items: 'li:not(.'+ disabledClass + ')',
		placeholder: "ui-state-highlight",
		forcePlaceholderSize: true,
		forceHelperSize: true,
		containment: "parent",
		scrollSensitivity: 10,
		helper: function(event, ui){
			var $clone =  $(ui).clone();
			$clone .css('position','absolute');
			return $clone.get(0);
		},
		start: function(){
			$('.'+ disabledClass, this).each(function(){
				var $this = $(this);
				$this.data('pos', $this.index());
			});
		},
		change: function(){
			$sortable = $(this);
			$statics = $('.'+ disabledClass, this).detach();
			$helper = $('<li></li>').prependTo(this);
			$statics.each(function(){
				var $this = $(this);
				var target = $this.data('pos');

				$this.insertAfter($('li', $sortable).eq(target));
			});
			$helper.remove();
		}
	}
};
