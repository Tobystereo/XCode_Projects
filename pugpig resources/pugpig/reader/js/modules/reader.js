/*

Licence:
==============================================================================

(c) 2011, Kaldor Holdings Ltd. All rights reserved.

Use in source and binary forms for commercially licensed Pugpig customers is governed by the Pugpig Software Licence Agreement at http://pugpig.com/download/licences/pugpig_licence_agreement.txt

For all other parties, use in source and binary forms is governed by the Pugpig Software Evaluation Agreement at http://pugpig.com/download/licences/pugpig_evaluation_agreement.txt

By downloading, reading and/or using this source, you agree to become a licensee of the Pugpig Software Suite and you are bound by the terms of the the licence agreement.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

define([
  'jquery',
  'underscore',
  'modules/utilities',
  'modules/add-this',
  'modules/device',
  'text!templates/table-of-contents.html',
  'text!templates/thumbnails.html',
  'text!templates/display-mode-picker.html',
  'text!templates/reading-progress.html',
  'text!templates/feed-prompt.html',
  'text!templates/editions.html',
  'text!templates/currently-viewing.html',
  'plugins/jquery-colorbox.min'
], function( $, _, Utilities, AddThis, Device, tocTmpl, thumbnailsTmpl, displayModePickerTmpl, readingProgressTmpl, feedPromptTmpl, editionSelectorTmpl, currentlyViewingTmpl ) {

  "use strict";

  var Reader = function() {

    var self = this;

    this.config = null;

    this.animating = false;
    this.activePage = 0;
    this.numPages = 0;
    this.hashedPage = this.getPageNumber();

    this.bookPage1 = null;
    this.bookPage2 = null;
    this.bookPages = null;
    this.bookFrame = null;

    this.el = null;

    this.prevPageArrow = null;
    this.nextPageArrow = null;

    this.displayModePicker = null;
    this.thumbnailDraw = null;
    this.toc = null;
    this.visiblePage = null;
    this.readerBar = null;
    this.displayEditionSelector = null;
    this.displayTableOfContents = null;
    this.closeIcons = null;
    this.editionSelector = null;
    this.fontResize = null;
    this.toggleFullScreenButton = null;

    this.tocList = null;
    this.thumbnailList = null;
    this.contentLists = null;
    this.editionsList = null;

    this.bookInfo = undefined;
    this.opds_url = null;
    this.atom_url = null;
    this.atom_chosen = false;
    this.editions = null;

    this.getConfig(function() {
      self.disableAjaxCache();
      self.getFeedPaths();
    });

  };

  Reader.fn = Reader.prototype;

  Reader.fn.configJSONUrl = 'config.json';

  Reader.fn.body = $('body');

  Reader.fn.win = $(window);

  Reader.fn.displayModes = null;

  Reader.fn.displayMode = null;

  Reader.fn.fontScale = '100%';

  Reader.fn.frames = ['reader-window-one','reader-window-two'];

  Reader.fn.FRAME_CLASS = 'reader-frame';

  Reader.fn.EL_CLASS = 'reader';

  Reader.fn.PREV_PAGE_CLASS = 'prev-page';

  Reader.fn.NEXT_PAGE_CLASS = 'next-page';

  Reader.fn.THUMBNAIL_DRAW_CLASS = 'thumbnail-draw';

  Reader.fn.EXPANDED_CLASS = 'is-expanded';

  Reader.fn.BAR_CLASS = 'reader-bar';

  Reader.fn.IS_ACTIVE_CLASS = 'is-active';

  Reader.fn.PRELOAD_CLASS = 'preload';

  Reader.fn.SIDEBAR_EXPANDED_CLASS = 'sidebar-is-expanded';

  Reader.fn.TOC_CLASS = 'table-of-contents';

  Reader.fn.DISPLAY_TOC_CLASS = 'display-toc';

  Reader.fn.TOC_EXPANDED_CLASS = 'toc-is-expanded';

  Reader.fn.EDITION_SELECTOR_CLASS = 'edition-selector';

  Reader.fn.DISPLAY_EDITION_SELECTOR_CLASS = 'display-edition-selector';

  Reader.fn.EDITION_SELECTOR_EXPANDED_CLASS = 'edition-selector-is-expanded';

  Reader.fn.DISPLAY_MODE_PICKER_CLASS = 'display-mode-picker';

  Reader.fn.CLOSE_SIDEBAR_CLASS = 'close-sidebar';

  Reader.fn.FONT_RESIZE_CLASS = 'font-resize';

  Reader.fn.TOGGLE_FULLSCREEN_CLASS = 'toggle-fullscreen';

  Reader.fn.CURRENTLY_VIEWING_CLASS = 'currently-viewing';

  Reader.fn.HIDDEN_CLASS = 'hidden';

  Reader.fn.CUSTOM_DIMENSION_CONTROL_CLASS = 'custom-dimensions-control';

  Reader.fn.CUSTOM_DIMENSION_WIDTH_ID = 'custom-width';

  Reader.fn.CUSTOM_DIMENSION_HEIGHT_ID = 'custom-height';

  Reader.fn.VIEWPORT_RESIZING_CONTROL_CLASS = 'resizing-control';

  Reader.fn.NO_CONTENT_STRING = 'This edition has no content.';

  Reader.fn.templates = {
    toc: _.template( tocTmpl ),
    thumbnails: _.template( thumbnailsTmpl ),
    displayModePicker: _.template( displayModePickerTmpl ),
    readingProgress: _.template( readingProgressTmpl ),
    feedPrompt: _.template( feedPromptTmpl ),
    editionSelector: _.template( editionSelectorTmpl ),
    currentlyViewing: _.template( currentlyViewingTmpl )
  };

  Reader.fn.startReader = function() {

    if ( !this.bookInfo.pages.length ) {
      alert( this.NO_CONTENT_STRING );
      return;
    }

    this.cacheDom();

    this.setDisplayMode( this.config.defaultDisplayModeIndex );

    this.render();

    this.setIframeStartPositions();

    this.resizeInterface();

    this.showInterface();
    
    this.setInitialPageNumber();

    this.displayPage( this.activePage );

    this.bindEvents();

    this.createAddThis();

  };

  Reader.fn.createAddThis = function() {
    if ( this.config.displaySocial ) {
      var addThis = new AddThis({
          el: this.body,
          id: 'addthis'
        });
    }
  };

  Reader.fn.getPageNumber = function() {
    var pageNum = parseInt( window.location.hash.substring(1), 10 );
    return !isNaN( pageNum ) ? pageNum : 0;
  };

  Reader.fn.updateAddThis = function() {
    if ( this.config.displaySocial ) {
      this.win.trigger('updateAddThisUrl', { url: this.visiblePage[0].src });
    }
  };

  Reader.fn.cacheDom = function() {
    this.bookPage1 = $("#" + this.frames[0]);
    this.bookPage2 = $("#" + this.frames[1]);
    this.bookPages = this.bookPage1.add( this.bookPage2 );
    this.bookFrame = $("." + this.FRAME_CLASS);
    this.el = $("." + this.EL_CLASS);
    this.prevPageArrow = $('.' + this.PREV_PAGE_CLASS);
    this.nextPageArrow = $('.' + this.NEXT_PAGE_CLASS);
    this.displayModePicker = $('.' + this.DISPLAY_MODE_PICKER_CLASS);
    this.thumbnailDraw = $('.' + this.THUMBNAIL_DRAW_CLASS);
    this.toc = $('.' + this.TOC_CLASS);
    this.editionSelector = $('.' + this.EDITION_SELECTOR_CLASS);
    this.editionsList = this.editionSelector.find('ul');
    this.readerBar = $('.' + this.BAR_CLASS);
    this.tocList = this.toc.find('ol');
    this.thumbnailList = this.thumbnailDraw.find('ul');
    this.contentLists = this.thumbnailList.add( this.tocList );
    this.displayEditionSelector = $('.' + this.DISPLAY_EDITION_SELECTOR_CLASS);
    this.displayTableOfContents = $('.' + this.DISPLAY_TOC_CLASS);
    this.closeIcons = $('.' + this.CLOSE_SIDEBAR_CLASS);
    this.fontResize = $('.' + this.FONT_RESIZE_CLASS);
    this.toggleFullScreenButton = $('.' + this.TOGGLE_FULLSCREEN_CLASS);
    this.currentlyViewing = $('.' + this.CURRENTLY_VIEWING_CLASS);
    this.customDimensionControls = $('.' + this.CUSTOM_DIMENSION_CONTROL_CLASS);
    this.customDimensionInputs = $('#' + this.CUSTOM_DIMENSION_WIDTH_ID).add('#' + this.CUSTOM_DIMENSION_HEIGHT_ID);
  };

  Reader.fn.bindEvents = function() {

    var self = this,
      toggleThumbnailDraw = $.proxy( this.toggleThumbnailDraw, this ),
      applyCustomDimensions = _.debounce( $.proxy( this.applyCustomDimensions, this ), 250 );

    this.win.on('keydown', $.proxy( this.pageKeyDown, this ));

    $('.' + this.PREV_PAGE_CLASS).on('click', $.proxy( this.prevPage, this ));

    $('.' + this.NEXT_PAGE_CLASS).on('click', $.proxy( this.nextPage, this ));

    this.displayModePicker.on('change', $.proxy( this.changeDisplayMode, this ));

    this.contentLists.on('click', 'a', function( e ) {
      e.preventDefault();
      self.changePageHash( $(this).parents('ul,ol').find('a').index( $(this) ) );
    });

    this.displayTableOfContents.on('click', $.proxy( this.toggleTableOfContents, this ));

    this.displayEditionSelector.on('click', $.proxy( this.toggleEditionSelector, this ));

    this.el.on('dblclick', toggleThumbnailDraw);

    this.closeIcons.on('click', $.proxy( this.closeSidebar, this ));

    this.fontResize.on('change', $.proxy( this.resizeFonts, this ));

    this.toggleFullScreenButton.on('click', $.proxy( this.toggleFullScreen, this ));

    this.customDimensionInputs.on('keyup', applyCustomDimensions);

    this.bookPage1.add( this.bookPage2 ).load(function() {
      var iframe = $(this);
      self.executeDelayedScript( iframe );
      self.bindIframeLinkEvents( iframe );
    });
    
    this.win.on('hashchange', function() {
       self.previousPageNumber = self.activePage;
       self.setActivePageNumber( self.getPageNumber() );
       self.goToPage( self.activePage );
    });

    if ( Device.isIos ) {
      this.win.on('orientationchange', $.proxy( this.resizeFramesToContentHeight, this ));
    }

  };

  Reader.fn.getConfig = function( callback ) {
    var self = this;
    $.ajax({
      dataType: "json",
      url: this.configJSONUrl,
      cache: false,
      success: function( json ) {
        self.config = json;
        self.displayModes = self.config.devices;
        callback();
      }
    });
  };

  Reader.fn.executeDelayedScript = function( iframe ) {

    var metaTag = iframe.contents().find('meta[name="callbackWhenSnapshotFinished"]'),
      iframeWindow = iframe[0].contentWindow,
      method = iframeWindow, // use the iframes global Window context as the base for the method
      methodParts;

    if( metaTag.length ) {
      methodParts = metaTag.attr('content').split('.');
      $.each( methodParts, function( i, item ) {
        if( method.hasOwnProperty( item ) ) {
          method = method[ item ];
        }
      });
      try {
        method();
      } catch( error ) {
        log( error );
      }
    }

  };

  Reader.fn.setDisplayMode = function( index ) {
    this.displayMode = this.displayModes[ index ];
  };

  Reader.fn.disableAjaxCache = function() {
    $.ajaxSetup({
      cache: false
    });
  };

  Reader.fn.getFeed = function() {
    if ( !_.isNull(this.opds_url) && this.urlExists(this.opds_url) ) {
      this.getOPDS();
    } else {
      if ( _.isNull(this.atom_url) ) {
        this.atom_url = '../atom.xml';
      }
      this.getAtom();
      this.disableEditionSelector = true;
    }
  };

  Reader.fn.setArrowVisibility = function( newPageNumber ) {

    var nextPageArrowVisible = (newPageNumber === this.bookInfo.pages.length-1) ? false : true,
      prevPageArrowVisible = (newPageNumber === 0) ? false : true;

    if ( prevPageArrowVisible ) {
      this.prevPageArrow.removeClass( this.HIDDEN_CLASS );
    } else {
      this.prevPageArrow.addClass( this.HIDDEN_CLASS );
    }

    if ( nextPageArrowVisible ) {
      this.nextPageArrow.removeClass( this.HIDDEN_CLASS );
    } else {
      this.nextPageArrow.addClass( this.HIDDEN_CLASS );
    }

  };

  Reader.fn.changePageHash = function( pageNumber ) {
    window.location.hash = "#" + pageNumber;
  };

  Reader.fn.pageKeyDown = function( e ) {
    
    if ( !e ) {
      e = event;
    }

    if( e.originalEvent.target.tagName.toLowerCase() !== 'input' ) {

      if ( e.keyCode === 37 ) {
        this.prevPage();
      } else if (e.keyCode === 39) {
        this.nextPage();
      }

    }

  };

  Reader.fn.prevPage = function() {
    if ( !this.animating && this.activePage > 0 ) {
      this.changePageHash( this.activePage - 1 );
    }
  };

  Reader.fn.nextPage = function() {
    if ( !this.animating && this.activePage < this.bookInfo.pages.length - 1 ) {
      this.changePageHash( parseInt( this.activePage, 10 ) + 1 );
    }
  };

  Reader.fn.goToPage = function( index ) {
    this.displayPage( parseInt( index, 10 ), false );
  };

  Reader.fn.getPageToDisplay = function() {
    return (this.visiblePage === this.bookPage1) ? this.bookPage2 : this.bookPage1;
  };

  Reader.fn.toggleVisiblePage = function() {
    
    var self = this;

    this.visiblePage = this.getPageToDisplay();

  };

  Reader.fn.resizeFrames = function( w, h ) {
    this.bookFrame.add( this.visiblePage ).width( w ).height( h );
  };

  Reader.fn.resizeFramesToContentHeight = function( e ) {

    var self = this,
      resize = $.proxy( this.resizeFrames, this ),
      w,
      h;

    if ( !this.visiblePage.data('iframe-load-event-bound') ) {

      this.visiblePage.load(function() {
        self.visiblePage.data('iframe-load-event-bound', true);
        w = self.win.width();
        h = $(this).contents().height();
        resize( w, h );
      });

    }

    if ( !_.isUndefined( e ) && e.type === 'orientationchange' ) {
      w = this.win.width();
      h = this.visiblePage.contents().height();
      resize( w, h );
    }
    
  };

  Reader.fn.displayPage = function( newPageNumber ) {

    var self = this,
      delta = (newPageNumber > this.previousPageNumber) ? 1 : -1,
      makeVisible,
      rand;

    makeVisible = this.getPageToDisplay();
    makeVisible.attr('src', this.bookInfo.pages[ newPageNumber ].url);

    if( !this.animating ) {

      this.setAnimatingState( true );

      if ( !_.isNull(this.visiblePage) ) {
        this.resetIframePosition( makeVisible, delta );
      }
      
      setTimeout(function() {
        self.changeVisiblePage( makeVisible, newPageNumber, delta );
      }, this.config.slideTransitionDuration);

    }

  };

  Reader.fn.resetIframePosition = function( makeVisible, direction ) {

    var leftOffset = this.displayMode.width,
      position;

    if (direction > 0) {
      position = {
        transform: 'translate3d(' + leftOffset + 'px,0,0)'
      };
    } else {
      position = {
        transform: 'translate3d(-' + leftOffset + 'px,0,0)'
      };
    }

    makeVisible.detach().css(position);
    this.bookFrame.append(makeVisible);

  };

  Reader.fn.moveIframes = function( visible, direction ) {

    var leftOffset = this.displayMode.width,
      currXPositions = $.map( this.bookPages, function( el ) {
        return parseInt( Utilities.matrixToArray( $(el).css('transform') )[4], 10 );
      }),
      newXPositions = [],
      operator;

    if( visible === null || direction > 0 ) {
      $.each( currXPositions, function() {
        newXPositions.push( this - leftOffset );
      });
    } else {
      $.each( currXPositions, function() {
        newXPositions.push( this + leftOffset );
      });
    }

    $.each(this.bookPages, function( i ) {
      $(this).css({
        transform: 'translate3d(' + newXPositions[ i ] + 'px,0,0)'
      });
    });

    this.toggleVisiblePage();

  };

  Reader.fn.changeVisiblePage = function( makeVisible, newPageNumber, direction ) {

    this.setArrowVisibility( newPageNumber );

    this.setActivePageNumber( newPageNumber );

    this.setAnimatingState( false );

    this.moveIframes( this.visiblePage, direction );

    this.onPageTurn();

  };

  Reader.fn.isFullscreenImageLink = function( link ) {
    return link.toLowerCase().indexOf('pugpig://onimageclick') !== -1 ? 1 : 0;
  };

  Reader.fn.launchFullscreenImage = function( clicked ) {

    var self = this,
      tag = clicked[ 0 ].tagName.toLowerCase(),
      gallery,
      figure,
      imgGroup,
      src;

    if ( tag === 'a' ) {
      figure = clicked.parents('figure');
      imgGroup = figure.data( 'image-group' );

      src = Utilities.rebaseUrl( figure.find('img').attr( 'src' ), this.visiblePage.attr( 'src' ) );

    }

    $.colorbox({
      href: src,
      opacity: 1,
      maxWidth: '100%',
      maxHeight: '100%',
      title: figure.find('figcaption').text(),
      fixed: true,
      onOpen: function() {
        self.win.css({
          overflowY: 'hidden'
        });
      },
      onClosed: function() {
        self.win.css({
          overflowY: 'auto'
        });
      }
    });

    $('#cboxWrapper').on('click','img', $.colorbox.close );

  };

  Reader.fn.displayIframeLink = function( e ) {

    var clicked = $(e.currentTarget),
      link = Utilities.rebaseUrl( clicked.attr('href'), this.visiblePage.attr('src') ),
      tocLink,
      tocIndex;

    if( link && this.isFullscreenImageLink( link ) || clicked[ 0 ].tagName.toLowerCase() === 'figure' ) {
      this.launchFullscreenImage( clicked );
      return false;
    } else {

      tocLink = this.tocList.find('a[href$="' + link + '"]');
      tocIndex = this.tocList.find('a').index( tocLink );

      if(tocIndex > -1) {
        e.preventDefault();
        this.changePageHash( tocIndex );
      } else {
        window.parent.document.location = link;
      }

    }

  };

  Reader.fn.bindIframeLinkEvents = function( iframe ) {

    var iframeLinks = iframe.contents().find('a,figure');

    iframeLinks.on('click', $.proxy( this.displayIframeLink, this ));

  };

  Reader.fn.setActivePageNumber = function( pageNumber ) {
    this.activePage = parseInt( pageNumber, 10 );
  };

  Reader.fn.setAnimatingState = function( animating ) {
    this.animating = animating ? true : false;
  };

  Reader.fn.hideCustomDimensionControls = function() {
    this.customDimensionControls.addClass( this.HIDDEN_CLASS );
  };

  Reader.fn.displayCustomDimensionControls = function() {

    this.customDimensionControls.removeClass( this.HIDDEN_CLASS );
    this.customDimensionInputs.eq(0).val( this.displayMode.width );
    this.customDimensionInputs.eq(1).val( this.displayMode.height );

  };

  Reader.fn.getCustomWidth = function() {
    return parseInt( this.customDimensionInputs.eq(0).val(), 10 );
  };

  Reader.fn.getCustomHeight = function() {
    return parseInt( this.customDimensionInputs.eq(1).val(), 10 );
  };

  Reader.fn.changeCustomDimensionsOption = function( w, h ) {

    var currVal = this.displayModePicker.find('option:last').val(),
      currValParts = currVal.split('-'),
      newVal = currValParts[0] + ' - ' + '(' + w + 'x' + h + ')';

    this.displayModePicker.find('option:last').text( newVal );

  };

  Reader.fn.applyCustomDimensions = function() {

    var displayMode = this.displayModes[ this.displayModes.length - 1 ],
      w = this.getCustomWidth(),
      h = this.getCustomHeight();

    displayMode.width = w;
    displayMode.height = h;

    this.changeCustomDimensionsOption( w, h );
    this.resizeInterface();

  };

  Reader.fn.changeDisplayMode = function( e ) {

    var clicked = $(e.currentTarget),
      index = clicked[0].selectedIndex;

    this.setDisplayMode( index );
    this.resizeInterface();

    if ( clicked.val().toLowerCase().indexOf('custom') > -1 ) {
      this.displayCustomDimensionControls();
    } else {
      this.hideCustomDimensionControls();
    }

  };

  Reader.fn.setInitialPageNumber = function() {

    if ( _.isNumber( this.hashedPage ) ) {
      this.activePage = parseInt( this.hashedPage, 10 );
    }

    if ( isNaN(this.activePage) || this.activePage < 0 ) {
      this.activePage = 0;
    } else if (this.activePage >= this.bookInfo.pages.length) {
      this.activePage = this.bookInfo.pages.length - 1;
    }

    this.previousPageNumber = this.activePage;

  };

  Reader.fn.resizeInterface = function() {

    var uiElements = this.bookFrame.add( this.bookPages ),
      dimensions = {
        width: this.displayMode.width,
        height: this.displayMode.height
      },
      thumbnailItems = this.thumbnailList.children('li'),
      thumbnailListWidth = 0;

    this.bookPages.innerWidth( dimensions.width );
    this.bookFrame.innerWidth( dimensions.width );

    this.bookPages.innerHeight( dimensions.height );
    this.bookFrame.innerHeight( dimensions.height );

    $.each(thumbnailItems, function() {
      thumbnailListWidth += $(this).innerWidth();
    });

    this.thumbnailList.width( thumbnailListWidth );

  };

  Reader.fn.formatCategoryString = function( str ) {
    return str.replace('[','').replace(']','').replace(/'/g,'');
  };

  Reader.fn.renderReadingProgress = function() {
    $('.reading-progress').html( this.templates.readingProgress({
      current: this.activePage + 1,
      total: this.bookInfo.pages.length
    }));
  };

  Reader.fn.renderEditionSelector = function() {
    this.editionsList.html(this.templates.editionSelector({
      editions: this.editions
    }));
  };

  Reader.fn.renderCurrentlyViewing = function() {

    var self = this,
      currentArticle = this.tocList.find('li').eq( this.activePage ),
      categories = $.map( currentArticle.data('categories').split('|'), function( categoryStr ) {

        var categoryParts = self.formatCategoryString( categoryStr ).split(',');

        return {
          scheme: categoryParts[0],
          term: categoryParts[1]
        };

      });

    this.currentlyViewing.html( this.templates.currentlyViewing({
      title: currentArticle.children('a').text(),
      summary: currentArticle.children('p').text(),
      categories: categories
    }));

  };

  Reader.fn.render = function() {

    if ( this.config.displayToolbar ) {
      this.body.removeClass('disable-navbars');
    }

    if ( !Device.isIos ) {
      this.body.removeClass('is-ios');
    }

    if ( !Device.supportsFullScreen && !Device.isIos ) {
      this.body.removeClass('disable-fullscreen');
    }

    this.renderDisplayModePicker();
    this.renderTableOfContents();
    this.renderEditionSelector();
    
    if ( this.disableEditionSelector ) {
      this.hideEditionSelector();
    }

  };

  Reader.fn.renderDisplayModePicker = function() {

    $('.' + this.VIEWPORT_RESIZING_CONTROL_CLASS).removeClass( this.HIDDEN_CLASS );

    this.displayModePicker.html(this.templates.displayModePicker({
      displayModes: this.displayModes
    }));
    this.displayModePicker.children('option').eq( this.config.defaultDisplayModeIndex ).prop('selected','selected');

  };

  Reader.fn.renderTableOfContents = function() {

    this.tocList.html( this.templates.toc({
      pages: this.bookInfo.pages
    }));

  };

  Reader.fn.renderThumbnailDraw = function() {

    this.thumbnailList.html( this.templates.thumbnails({
      pages: this.bookInfo.pages
    }));

  };

  Reader.fn.closeSidebar = function() {
    this.body.removeClass( this.SIDEBAR_EXPANDED_CLASS + ' ' + this.EDITION_SELECTOR_EXPANDED_CLASS + ' ' + this.TOC_EXPANDED_CLASS );
  };

  // following two methods need to be refactored, do the same thing

  Reader.fn.toggleTableOfContents = function( e ) {

    if ( e ) {
      e.preventDefault();
    }

    this.toggleSidebar();

    if ( $('.' + this.EDITION_SELECTOR_EXPANDED_CLASS ).length ) {
      this.toggleEditionSelector();
    }

    this.body.toggleClass( this.TOC_EXPANDED_CLASS );
    
  };

  Reader.fn.toggleEditionSelector = function( e ) {

    if ( e ) {
      e.preventDefault();
    }

    this.toggleSidebar();

    if ( $('.' + this.TOC_EXPANDED_CLASS ).length ) {
      this.toggleTableOfContents();
    }

    this.body.toggleClass( this.EDITION_SELECTOR_EXPANDED_CLASS );
    
  };

  Reader.fn.toggleSidebar = function() {
    this.body.toggleClass( this.SIDEBAR_EXPANDED_CLASS );
  };

  Reader.fn.hideEditionSelector = function() {
    this.displayEditionSelector.hide();
  };

  Reader.fn.toggleThumbnailDraw = function() {
    this.thumbnailDraw.toggleClass( this.EXPANDED_CLASS );
  };

  Reader.fn.showInterface = function() {
    this.body.removeClass( this.PRELOAD_CLASS );
  };

  Reader.fn.setIframeStartPositions = function() {

    var pageWidth = this.displayMode.width;

    this.bookPage1.css({
      transform: 'translate3d(' + pageWidth + 'px,0,0)'
    });
    this.bookPage2.css({
      transform: 'translate3d(' + (pageWidth * 2) + 'px,0,0)'
    });

  };

  Reader.fn.resizeFonts = function( e ) {
    this.fontScale = this.fontResize.val();
    this.visiblePage.contents().find('body').css({
      fontSize: (this.fontScale * 100) + '%'
    });
  };

  Reader.fn.cancelFullScreen = function() {
    if(document.cancelFullScreen) {
      document.cancelFullScreen();
    } else if(document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if(document.webkitCancelFullScreen) {
      document.webkitCancelFullScreen();
    }
  };

  Reader.fn.enableFullScreen = function( el ) {
    if (el.requestFullscreen) {
      el.requestFullscreen();
    } else if (el.mozRequestFullScreen) {
      el.mozRequestFullScreen();
    } else if (el.webkitRequestFullscreen) {
      el.webkitRequestFullscreen();
    }
  };

  Reader.fn.toggleFullScreen = function( e ) {

    e.preventDefault();

    var elem = this.body[0];

    if(this.isFullscreen) {

      this.cancelFullScreen();
      this.isFullscreen = false;

    } else {

      this.enableFullScreen( elem );
      this.isFullscreen = true;

    }
    
  };

  Reader.fn.onPageTurn = function() {
    
    var activePageIndex = window.location.hash.replace('#',''),
      self = this;

    $.each(this.contentLists, function() {
      var list = $(this);
      list.find('.' + self.IS_ACTIVE_CLASS).removeClass();
      list.children('li').eq( activePageIndex ).addClass( self.IS_ACTIVE_CLASS );
    });

    this.renderReadingProgress();
    this.renderCurrentlyViewing();
    this.resizeFonts();
    this.updateAddThis();

    if( Device.isIos ) {
      this.resizeFramesToContentHeight();
    }

  };

  Reader.fn.promptForFeed = function() {
    
    this.body.append( this.templates.feedPrompt() );

    $('#feed_url').on('keyup', function( e ) {
      if ( e.keyCode === 13 ) {
        window.location.search = '?opds=' + encodeURIComponent( $(this).val() );
      }
    });

  };

  // needs refactoring

  Reader.fn.getFeedPaths = function() {

    var query = window.location.search,
      rand = Utilities.generateRandomNumber();
    
    if ( !_.isNull(query) && query !== "" && query !== "?" ) {

      query = query.substring(1); // strip initial ?

      var parts = query.split('&');

      for (var i=0; i<parts.length; i++) {
          
        var pair = parts[i].split('=');

        if (pair[0] == 'atom') {
          this.atom_url = decodeURIComponent(pair[1]); /* + '?' + rand*/
        } else if (pair[0] == 'opds') {
          this.opds_url = decodeURIComponent(pair[1]); /* + '?' + rand*/
        }

      }

    } else {
      this.promptForFeed();
      return false;
    }

    if ( !_.isNull(this.atom_url) ) {
      this.atom_chosen = true;
    }

    if ( _.isNull(this.opds_url) && _.isNull(this.atom_url) ) {
      this.opds_url = '../editions.xml' + '?' + rand;
    }

    this.getFeed();

  };

  // needs refactoring

  Reader.fn.getAtom = function() {

    var self = this;

    if ( !_.isNull(this.atom_url) ) {

      $.get(this.atom_url, function( data ) {

        var xml = $(data),
          pageCount = 0,
          baseUrl = Utilities.rebaseUrl( self.atom_url, self.opds_url ),
          pages = xml.find("feed>entry").map(function() {

            var node = $(this),
              title = node.children('title:first'),
              image = node.children('link[rel="icon"]'),
              url = node.children('link[rel="alternate"][type="text/html"]:first'),
              summary = node.children('summary').text(),
              categoryNodes = node.children('category'),
              categories = $.map( categoryNodes, function( node ) {

                var el = $(node),
                  schemeParts = el.attr('scheme').split('/');

                return {
                  scheme: schemeParts[ schemeParts.length - 1 ],
                  term: el.attr('term')
                };

              }),
              css;

            title = (title.length === 0 ? '' : $(title).text());
            url = url.length === 0 ? '' : Utilities.rebaseUrl( url.attr('href'), baseUrl );
            image = image.length === 0 ? '' : Utilities.rebaseUrl( image.attr('href'), baseUrl );

            css = pageCount === 0 ? ' class="chosen"' : '';

            if ( pageCount === 0 && !_.isNull(self.thumbnail_service) ) {
              $('#pages').css('display', 'block');
              $('#pages').append('<ul></ul>');
            }

            $('#pages>ul').append('<li><a href="#page-' + pageCount + '" alt="' + title + '"><img' + css + ' src="' + image + '" /></a></li>');
            $('#pages>ul>li:last>a').on('click', function() {
              self.changePageHash( $(this).attr('href').substring(6) );
              return false;
            });

            pageCount++;

            return {
              url: url,
              title: title,
              summary: summary,
              categories: categories,
              articleIcon: image
            };

          });
        


        self.bookInfo = {
          title: xml.find('feed>title:first').text(),
          pages: pages
        };

        document.title = self.bookInfo.title;
        
        self.startReader();

      }, "xml");

    }

  };

  // needs refactoring

  Reader.fn.getOPDS = function() {

    var self = this;

    $.get(this.opds_url, function( data ) {

        var xml = $(data),
          baseUrl = Utilities.rebaseUrl( self.opds_url ),
          covers = xml.find("feed>entry").map(function() {

            var node = $(this),
              title = node.children('title:first'),
              image = node.children('link[rel="http://opds-spec.org/image"][type="image/jpg"]:first'),
              atom = node.children('link[rel="alternate"][type="application/atom+xml"]:first'),
              summary = node.children('summary').text(),
              aquisitionParts = node.children('link[rel^="http://opds-spec.org/acquisition"]').attr('rel').split('/'),
              aquisition = aquisitionParts[ aquisitionParts.length - 1 ],
              draft = node.children('app\\:control').children('app\\:draft').text() === 'yes' ? true : false,
              pubDate = node.children('dcterms\\:issued').text(),
              updated = node.children('updated').text(),
              updatedParts = updated.split('T'),
              updateDate = updatedParts[0].split("-").reverse().join("-"),
              updateTime = updatedParts[1].split('+')[0],
              paid = aquisition === 'buy' ? true : false,
              free = paid ? false : true,
              css;

            title = (title.length === 0 ? '' : $(title).text());
            image = (image.length === 0 ? '' : $(image).attr('href'));
            atom = (atom.length === 0 ? '' : $(atom).attr('href'));

            if ( _.isNull(image) || image === '') {
              image = 'images/nocover.jpg'; // relative to the reader
            } else {
              image = Utilities.rebaseUrl( image, baseUrl );
            }

            atom = Utilities.rebaseUrl( atom, baseUrl );

            css = ( !_.isNull(self.atom_url) && self.atom_url === atom) ? ' class="chosen"' : '';

            if ( _.isNull(self.atom_url) ) {
              self.atom_url = atom;
              css = ' class="chosen"';
            }

            return {
              opdsUrl: encodeURIComponent( self.opds_url ),
              atom: encodeURIComponent( atom ),
              title: title,
              pubDate: pubDate,
              updateDate: updateDate,
              updateTime: updateTime,
              css: css,
              image: image,
              summary: summary,
              draft: draft,
              paid: paid,
              free: free
            };

          });

        self.editions = covers;

        self.getAtom();

    }, "xml");

  };

  Reader.fn.urlExists = function( url ) {

    var exists = false;

    try {
      var http = new XMLHttpRequest();
      http.open('HEAD', url, false);
      http.send();
      exists = http.status!=404;
    } catch( err ) {
      log(err);
    }
    
    return exists;

  };

  return Reader;

});
