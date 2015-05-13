(function ( $ ) {
	"use strict";

	$( document ).ready(function(){

		    var $info = $("#wpcpn-modal-content");
		    if ( $info ) {

		    	$(".wpcpn-open-modal").click(function(event) {
			        event.preventDefault();
			        var $this = $(this);
			        var post_title 	= $this.attr("data-wpcpn-post-title");
			        var post_id 	= $this.attr("data-wpcpn-post-id");
			        var blog_id	 	= $this.attr("data-wpcpn-blog-id");

			        $info.find('input[name=wpcpn-post-id]').val(post_id);
			        $info.find('input[name=wpcpn-blog-id]').val(blog_id);
			        $info.find('textarea').val('');
			        $info.find(".wpcpn-post-title span").html(post_title);
			        $info.dialog('open');
			    });

			    $info.dialog({
			        'dialogClass'   : 'wp-dialog',
			        'modal'         : true,
			        'autoOpen'      : false,
			        'closeOnEscape' : true,
			        'width'			: 400,
			        'title' 		: EditPosts.dialog_title,
			       	'buttons' : [
			       					{'text' : EditPosts.btn_send, 'class' : 'button-primary',
				       					'click' : function() {
				       						var post_id = $info.find('input[name=wpcpn-post-id]').val();
			        						var blog_id = $info.find('input[name=wpcpn-blog-id]').val();
			        						var reason  = $info.find('.wpcpn-request-reason-text').val();
			        						var self = this;
				       						$.ajax({
												url  : ajaxurl,
												type : 'GET',
												data : {
													'action'  : 'wpcpn_send_featured_request',
													'post_id' :  post_id,
													'blog_id' :  blog_id,
													'message' :  reason,
												},
												success: function( result ) {
													if ( result == 1)
														alert(EditPosts.request_duplicate);
													else if ( result == 2)
														alert(EditPosts.request_success);
													else
														alert(EditPosts.request_error);

				       								$(self).dialog('close');
				       								window.location.reload()
												},
												error: function( result ) {
													alert(EditPosts.request_error);
												}
											});

				       					}
			       					},
			       					{'text' : EditPosts.btn_close, 'class' : '', 'click' : function() { $(this).dialog('close');} }
			       				]
			    });


		    }

	});

}(jQuery));
