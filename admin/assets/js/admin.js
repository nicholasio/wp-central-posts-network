;(function ( $ ) {
	"use strict";

	$( document ).ready(function(){
		var elState = {
			NOT_SELECTED : 1,
			SELECTED : 2
		};
		var PostsChoose = {
			$posts_selected	: '',
			$posts_to_choose	: '',
			$search			: '',
			$siteChooser		: '',
			$btnSavePostList	: '',
			$section		: $(".wpcpm-section"),
			$currentSection		: '',
			$ajaxLoader		: '',
			currentNamespace	: '',
			currentGroup		: '',

			init : function() {
				//Configura a seção atual: a seção atual é a seção onde o mouse está
				this.$section.on('mouseenter', $.proxy(this.setNameSpace, this ) );
			},

			/**
			 *	Configura o plugin fastLiveFilter
			 */
			liveFilter  : function() {
				this.$search.fastLiveFilter(this.$posts_to_choose);
			},

			/**
			 * Configura um namespace (section) para interação
			 */
			setNameSpace : function(evt) {
				var $section          = $(evt.currentTarget); //A seção atual é a seção onde o mouse está
				this.currentNamespace = $section.attr('data-namespace');
				var namespaceSelector = '.wpcpm-section.wpcpn-namespace-' + this.currentNamespace;

				this.$currentSection  = $(namespaceSelector);
				this.currentGroup     = $('.wpcpn-group').attr('id');

				this.$siteChooser     = $( namespaceSelector + ' .wpcpn-site-chooser');
				this.$posts_to_choose = $( namespaceSelector + ' .wpcpn-posts-to-choose');
				this.$posts_selected  = $( namespaceSelector + ' .wpcpn-posts-selected');
				this.$btnSavePostList = $( namespaceSelector + ' .wpcpn-save-post-list');
				this.$search          = $( namespaceSelector + ' .wpcpn-search');
				this.$ajaxLoader      = $( namespaceSelector + ' > .wpcpn-ajax-loader');

				this.initSortableLists();

				/*
				 * Se algum bind foi realizado, desfaça-o
				 */
				if ( this.$posts_selected != '' )
					this.unbind();

				this.bind();
				this.liveFilter();

			},

			/**
			 * 	Remove todos os event handlers dos elementos, necessário para evitar handlers duplicados
			 */
			unbind : function() {
				this.$siteChooser.off('change' , $.proxy( this.handleSiteChooser, this) );
				this.$posts_to_choose.off('click','li a', $.proxy( this.addPostItem, this) );
				this.$posts_selected.off('click', 'li a', $.proxy( this.removePostItem, this) );
				this.$btnSavePostList.off('click', $.proxy( this.savePostList, this) );
			},

			/**
			 *	Associa os event handlers aos devidos elementos
			 */
			bind  : function() {
				this.$siteChooser.on('change' , $.proxy( this.handleSiteChooser, this) );
				this.$posts_to_choose.on('click','li a', $.proxy( this.addPostItem, this) );
				this.$posts_selected.on('click', 'li a', $.proxy( this.removePostItem, this) );
				this.$btnSavePostList.on('click', $.proxy( this.savePostList, this) );
			},

			/**
			 *	Configura o widget sortable
			 */
			initSortableLists: function( ) {
				this.$posts_selected.sortable({placeholder: "ui-state-highlight"}).disableSelection();
			},

			/**
			 * Realiza a troca de um site para carregar os posts daquele site
			 * Esse método é um manipulador para o evento 'change' do combobox
			 */
			handleSiteChooser : function(evt) {
				var that = this;
				var $obj = $(evt.currentTarget);

				//Se uma opção inválida tiver sido escolhida
				if ( $obj.val() == -1 )
					return;

				var id = $obj.val();

				this.selectSite( id );
			},

			/**
			 * Carrega os posts do site selecionado na área de seleção
			 */
			selectSite : function( blog_id ) {
				var current_blog_id = this.$posts_to_choose.attr('data-current-blog-id')
				var namespace = this.$currentSection.attr('data-namespace');

				var curSelector    = ".wpcpn-all-posts." + namespace +" .wpcpn-posts-list[data-blog_id=" + current_blog_id + "]";
				var blogIDSelector = ".wpcpn-all-posts." + namespace +" .wpcpn-posts-list[data-blog_id=" + blog_id + "]";

				//Se já tem algum site selecionado, precisamos salvar o estado da seleção.
				if ( typeof current_blog_id != "undefined" ) {
					$(curSelector).html(this.$posts_to_choose.html());
				}

				// Setando lista de posts
				var postsList = $(blogIDSelector).html();
				this.$posts_to_choose.html(postsList);



				/*var that = this;

				$.getJSON(ajaxurl, {
					'action'  : 'wpcpn_get_posts_from_blog',
					'blog_id' : blog_id
				}, function( result ) {
					that.populateBlogPostsList( blog_id, result );
					//that.liveFilter();
				});*/

				//Atualizando id do site selecionado
				this.$posts_to_choose.attr('data-current-blog-id',blog_id);

				this.liveFilter();
			},

			/**
			 *	Adiciona um item ao menu de posts selecionados
			 */
			addPostItem : function( evt ) {
				var $o_li = $(evt.currentTarget).parent();


				if ( (typeof $o_li.attr('data-state') == "undefined") ||
				     $o_li.attr('data-state') ==  elState.NOT_SELECTED ) {

					//Verificando se o limite máximo de posts não foi atingido para essa seção
					var nPostsSelected = this.$currentSection.attr('data-nposts');
					var maxPosts       = parseInt(this.$currentSection.attr('data-max-posts') );
					var canAdd = false;

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

					if ( this.$currentSection.attr('data-on-select') == '1' ) {
						var that = this;
						$o_li.find('.wpcpn-ajax-loader').show();
						$.ajax({
							url  : ajaxurl,
							type : 'GET',
							data : {
								'action'  : 'wpcpn_' + that.currentNamespace + '_on_select',
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
							alert("Limite máximo de posts atingido!");
						}
					}


				}

				return false;

			},
			addItem : function($o_li) {
				var $li = $o_li.clone();
				$li.find('a').removeClass('dashicons-plus-alt').addClass('dashicons-no');
				this.$posts_selected.append($li);
				$o_li.find('a').removeClass('dashicons-plus-alt').addClass('dashicons-yes');
				//$o_li.css({'border' : '1px solid red'});
				//$o_li.addClass('ui-state-highlight');
				$o_li.attr('data-state', elState.SELECTED);
			},
			/**
			 * Remove um item da lista de posts selecionados
			 */
			removePostItem : function( evt ) {
				var that = this; //Salva o contexto

				//O botão está dentro da li, portanto temos que pegar o elemento pai
				var $li = $(evt.currentTarget).parent();

				var uid = $li.attr('data-uid');

				//Desaparece com efeito e depois remove o elemento
				$li.fadeOut('fast', function() {
					$(this).remove();

					//Remove o item dentro do namespace atual
					var namespace = that.$currentSection.attr('data-namespace');
					var selector = ".wpcpn-all-posts." + namespace +" li[data-uid=" + uid + "]";
					var $o_li = $(selector);

					$o_li.find('a').removeClass('dashicons-yes').addClass('dashicons-plus-alt');
					//$o_li.css({'border' : '1px solid #d3d3d3'});
					//$o_li.removeClass('ui-state-highlight');
					$o_li.attr('data-state', elState.NOT_SELECTED );
				});

				//Atualizando quantidade de posts selecionados
				var nPosts = parseInt( this.$currentSection.attr('data-nposts') );
				this.$currentSection.attr('data-nposts', nPosts - 1);

				return false;
			},

			/**
			 *	Realiza uma chamada ajax para salvar a lista de posts
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
				$_ajaxLoader.show();

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
						$_ajaxLoader.hide();
					}
				});

			},

			/**
			 * Recebe uma lista de posts em formato JSON e popula a lista de post
			 * Não está sendo usado
			 */
			populateBlogPostsList : function( blog_id, posts ) {

				this.$posts_to_choose.html(''); //Remove todos os elementos

				for(var post_type in posts) {
					for(var i = 0; i < posts[post_type].length; i++ ) {
						var uuid = blog_id + '-' + posts[post_type][i].ID;

						var $o_li = $("<li class='ui-state-default' data-uid='"+ uuid +"' data-post-id='" + posts[post_type][i].ID + "'>" + posts[post_type][i].post_title + "<a href='#'>add</a></li>");
						$o_li.attr('data-state', elState.NOT_SELECTED);

						this.$posts_to_choose.append($o_li);
					}
				}
			}

		};

		PostsChoose.init();
	});


}(jQuery));
