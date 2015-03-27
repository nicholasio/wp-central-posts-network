;(function ( $ ) {
	"use strict";
	$( document ).ready(function(){
		$.widget("wpcpn.sAutocomplete", $.ui.autocomplete, {
			_renderMenu : function(ul, items) {
				 this._super(ul, items);
				 var $ul = $(ul);
				 $ul.removeClass('ui-front')
				 	.removeClass('ui-widget')
				 	.removeClass('ui-widget-content')
				 	.removeClass('ui-menu')
				 	.removeClass('ui-autocomplete')
				 	.removeClass('ui-corner-all');

				 $ul.attr('id', 'wpcpn-posts-to-choose');
				 $ul.css('width', '500px');
				 $ul.addClass('connectedSortable');
				 $ul.addClass('sortable');
				 $ul.appendTo(this.options.appendTo);

			},
			_renderItem : function(ul, item) {
				 return $( "<li class='ui-state-highlight'>" )
					.attr( "data-value", item.value )
					.append( $( "<a>" ).text( item.label ) )
					.appendTo( ul );
			}
		});
	}); 		
}(jQuery));