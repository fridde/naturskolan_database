

class Batch {

    constructor() {
        this.disabledClass = "sortable-disabled";
        this.listIdentifier = 'div.set-dates ul';
        this.sortableOptions = {
            items: 'li:not(.' + this.disabledClass + ')',
            placeholder: "ui-state-highlight",
            forcePlaceholderSize: true,
            forceHelperSize: true,
            containment: "parent",
            scrollSensitivity: 10,
            helper: function (event, ui) {
                let $clone = $(ui).clone();
                $clone.css('position', 'absolute');
                return $clone.get(0);
            }
        }
    }

    start() {
        $('.' + this.disabledClass, this).each(function () {
            let $this = $(this);
            $this.data('pos', $this.index());
        });
    }

    change() {
        let $sortable = $(this);
        let $statics = $('.' + this.disabledClass, this).detach();
        let $helper = $('<li></li>').prependTo(this);
        $statics.each(function () {
            let $this = $(this);
            let target = $this.data('pos');

            $this.insertAfter($('li', $sortable).eq(target));
        });
        $helper.remove();
    }

}

module.exports = new Batch();
