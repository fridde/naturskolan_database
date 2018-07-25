disabledClass = "sortable-disabled";

let Batch = {

    listIdentifier: 'div.set-dates ul',    
    sortableOptions: {
		items: 'li:not(.'+ disabledClass + ')',
		placeholder: "ui-state-highlight",
		forcePlaceholderSize: true,
		forceHelperSize: true,
		containment: "parent",
		scrollSensitivity: 10,
		helper: function(event, ui){
			let $clone =  $(ui).clone();
			$clone .css('position','absolute');
			return $clone.get(0);
		},
		start: function(){
			$('.'+ disabledClass, this).each(function(){
				let $this = $(this);
				$this.data('pos', $this.index());
			});
		},
		change: function(){
			let $sortable = $(this);
            let $statics = $('.'+ disabledClass, this).detach();
            let $helper = $('<li></li>').prependTo(this);
			$statics.each(function(){
				let $this = $(this);
				let target = $this.data('pos');

				$this.insertAfter($('li', $sortable).eq(target));
			});
			$helper.remove();
		}
	}
};
