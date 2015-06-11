;(function ( $ ) {
	"use strict";

	$( document ).ready(function(){
		var elState = {
			NOT_SELECTED : 1,
			SELECTED : 2
		};
		var PostsChoose = {
			namespaceSelector : '',
			$posts_selected   : '',
			$posts_to_choose  : '',
			$search           : '',
			$search_selected  : '',
			$siteChooser      : '',
			$btnSavePostList  : '',
			$section          : $(".wpcpm-section"),
			$currentSection   : '',
			$ajaxLoader       : '',
			currentNamespace  : '',
			currentGroup      : '',

			init : function() {
				/**
				 * Configures the current section: the current section is where the mouse is
				 * We need the mouseover event because if the user refreshes the page and mouse
				 * stays in the section the browser does not trigger mouseenter, so we use mouseover only once
				 */
				this.$section.on('mouseover', $.proxy(this.setNameSpace, this ) );
				this.$section.on('mouseenter', $.proxy(this.setNameSpace, this ) );

				$('.wpcpn-site-chooser').select2();
			},

			/**
			 *	Configures the liveFilter jQuery plugin
			 */
			liveFilter  : function() {
				this.$search.fastLiveFilter(this.namespaceSelector + ' .wpcpn-posts-to-choose');
				this.$search_selected.fastLiveFilter(this.namespaceSelector + ' .wpcpn-posts-selected');
			},

			/**
			 * Configures the namespace (section) for interaction
			 */
			setNameSpace : function(evt) {
				var $section          = $(evt.currentTarget); //A seção atual é a seção onde o mouse está
				this.currentNamespace = $section.attr('data-namespace');
				this.namespaceSelector = '.wpcpm-section.wpcpn-namespace-' + this.currentNamespace;

				this.$currentSection  = $( this.namespaceSelector );
				this.currentGroup     = $('.wpcpn-group').attr('id');

				this.$siteChooser     = $( this.namespaceSelector + ' .wpcpn-site-chooser');
				this.$posts_to_choose = $( this.namespaceSelector + ' .wpcpn-posts-to-choose');
				this.$posts_selected  = $( this.namespaceSelector + ' .wpcpn-posts-selected');
				this.$btnSavePostList = $( this.namespaceSelector + ' .wpcpn-save-post-list');
				this.$search          = $( this.namespaceSelector + ' .wpcpn-search-posts .wpcpn-search');
				this.$search_selected = $( this.namespaceSelector + ' .wpcpn-search-posts-selected .wpcpn-search');
				this.$ajaxLoader      = $( this.namespaceSelector + ' .wpcpn-ajax-loader-btn ');

				this.initSortableLists();

				/*
				 * If we have a previous bind, we need to unbind it
				 */
				if ( this.$posts_selected != '' )
					this.unbind();

				this.bind();
				this.liveFilter();

				/*
				 * We need mouseover event only once, so if the event is a mouseover, we need to
				 * unbind it
				 */
				if ( evt.type == 'mouseover' ) {
					this.$section.off('mouseover', $.proxy(this.setNameSpace, this ) );
				}

			},

			/**
			 *  Remove all of the event handlers of the elements, this is necessary to avoid duplicate event handlers
			 */
			unbind : function() {
				this.$siteChooser.off('select2-selecting' , $.proxy( this.handleSiteChooser, this) );
				this.$posts_to_choose.off('click','li a', $.proxy( this.addPostItem, this) );
				this.$posts_selected.off('click', 'li a', $.proxy( this.removePostItem, this) );
				this.$btnSavePostList.off('click', $.proxy( this.savePostList, this) );
			},

			/**
			 *	Associate events to appropriate elements
			 *
			 */
			bind  : function() {
				this.$siteChooser.on('select2-selecting' , $.proxy( this.handleSiteChooser, this) );
				this.$posts_to_choose.on('click','li a', $.proxy( this.addPostItem, this) );
				this.$posts_selected.on('click', 'li a', $.proxy( this.removePostItem, this) );
				this.$btnSavePostList.on('click', $.proxy( this.savePostList, this) );
			},

			/**
			 *	Sortable Widget
			 */
			initSortableLists: function( ) {
				this.$posts_selected.sortable({placeholder: "ui-state-highlight"}).disableSelection();
			},

			/**
			 * Make a site switch and delegates to load the posts of the selected site
			 */
			handleSiteChooser : function(evt) {
				var that = this;
				//var $obj = $(evt.currentTarget);

				//Invalid option
				if ( evt.id == -1 )
					return;

				var id = evt.choice.id;

				this.selectSite( id );
			},

			/**
			 * Load the posts of the selected site via ajax.
			 */
			selectSite : function( blog_id ) {
				var current_blog_id   = this.$posts_to_choose.attr('data-current-blog-id')
				var namespace         = this.$currentSection.attr('data-namespace');

				var that              = this;
				var _$posts_to_choose = this.$posts_to_choose;

				var $ajaxLoader       = this.$posts_to_choose.siblings('.wpcpn-ajax-loader');
				var $btnSavePostList  = this.$btnSavePostList;

				_$posts_to_choose.html('');

				this.loading( true, $ajaxLoader, $btnSavePostList );

				$.get(ajaxurl, {
					'action'  : 'wpcpn_get_html_posts_list',
					'blog_id' : blog_id,
					'section' : namespace,
					'group'	  : that.currentGroup
				}, function( result ) {
					_$posts_to_choose.html( result );
					that.liveFilter();
					that.loading( false, $ajaxLoader, $btnSavePostList );
				});

				//Update the id of the selected site
				this.$posts_to_choose.attr('data-current-blog-id',blog_id);
			},

			/**
			 *	Add a post item to selected posts
			 */
			addPostItem : function( evt ) {
				var $o_li = $(evt.currentTarget).parent();


				if ( (typeof $o_li.attr('data-state') == "undefined") ||
				     $o_li.attr('data-state') ==  elState.NOT_SELECTED ) {

					//Verify the max number of posts for this sections
					var nPostsSelected = this.$currentSection.attr('data-nposts');
					var maxPosts       = parseInt(this.$currentSection.attr('data-max-posts') );
					var canAdd         = false;

					if ( typeof nPostsSelected == "undefined" ) {
						this.$currentSection.attr('data-nposts', 1);
						canAdd = true;
					}
					else {
						nPostsSelected = parseInt(nPostsSelected);
						if ( nPostsSelected  < maxPosts )  {
							this.$currentSection.attr('data-nposts', (parseInt(nPostsSelected) + 1));
							canAdd = true;
						}

					}

					if ( canAdd && this.$currentSection.attr('data-on-select') == '1' ) {
						var that = this;
						$o_li.find('.wpcpn-ajax-loader').show();
						$.ajax({
							url  : ajaxurl,
							type : 'GET',
							data : {
								'action'  : 'wpcpn_before_select_' + that.currentGroup + '_' + that.currentNamespace,
								'post_id' :  $o_li.attr('data-post-id'),
								'blog_id' :  $o_li.parent().attr('data-current-blog-id')
							},
							success: function( result ) {
								$o_li.find('.wpcpn-ajax-loader').hide();
								if ( result == 1 ) {
									that.addItem($o_li);
								} else {
									alert(that.$currentSection.attr('data-on-error'));
								}
							}
						});
					}else {
						if ( canAdd ) {
							this.addItem($o_li);
						} else {
							alert("Maximum number of posts exceded!");
						}
					}


				}

				return false;

			},

			/**
			 * Adds a single post item
			 */
			addItem : function($o_li) {
				var $li = $o_li.clone();

				$li.find('a').removeClass('dashicons-plus-alt').addClass('dashicons-no');
				this.$posts_selected.append($li);

				$o_li.find('a').removeClass('dashicons-plus-alt').addClass('dashicons-yes');
				$o_li.attr('data-state', elState.SELECTED);

				this.savePostList();
			},

			/**
			 * Removes an item of the selected posts
			 */
			removePostItem : function( evt ) {
				var that = this;

				//The delete button is inside the li, so we need to catch the parent element
				var $li = $(evt.currentTarget).parent();

				var uid = $li.attr('data-uid');

				$li.fadeOut('fast', function() {
					$(this).remove();

					var namespace = that.$currentSection.attr('data-namespace');
					var selector = ".wpcpn-all-posts." + namespace +" li[data-uid=" + uid + "]";
					var $o_li = $(selector);

					$o_li.find('a').removeClass('dashicons-yes').addClass('dashicons-plus-alt');
					$o_li.attr('data-state', elState.NOT_SELECTED );

					that.savePostList();

					var nPosts = parseInt( that.$currentSection.attr('data-nposts') );
					that.$currentSection.attr('data-nposts', nPosts - 1);
				});



				return false;
			},

			/**
			 *  Shows an ajax loader
			 */
			loading : function(isLoading, $ajaxLoader, $btnSavePostList) {

				$ajaxLoader      = $ajaxLoader !== undefined ? $ajaxLoader : this.$ajaxLoader;
				$btnSavePostList = $btnSavePostList !== undefined ? $btnSavePostList : this.$btnSavePostList;

				if ( isLoading ) {
					$ajaxLoader.show();
					$btnSavePostList.attr('disabled', 'disabled');
				} else {
					$ajaxLoader.hide();
					$btnSavePostList.removeAttr('disabled');
				}


			},

			/**
			 *	Make an ajax call to save the posts list
			 *  section = namespace (currentNamespace)
			 */
			savePostList : function(evt) {

				/*
				 * Precisamos obter uma cópia para garantir que o ajaxLoader que irá ser "escondido",
				 * será sempre o ajaxLoader do namespace onde o botão foi criado.
				 * Caso contrário o ajaxLoader poderá não desaparecer visto que o usuário poderá ter "mudado" de namespace
				 * @see setNamespace and init
				 */
				var $_ajaxLoader = this.$ajaxLoader;
				var $_btnSavePostList = this.$btnSavePostList;
				this.loading( true, $_ajaxLoader, $_btnSavePostList );

				var that = this;
				$.ajax({
					url  : ajaxurl,
					type : 'POST',
					data : {
						'action'  : 'wpcpn_save_posts_list',
						'posts'   : this.$posts_selected.sortable('toArray', {'attribute' : "data-uid"}),
						'group'   : this.currentGroup,
						'section' : this.currentNamespace,
						'nonce'	  : WPCPN_Variables.nonce
					},
					success: function( result ) {
						that.loading(false, $_ajaxLoader, $_btnSavePostList );
					}
				});

			},

		};

		PostsChoose.init();
	});


}(jQuery));
