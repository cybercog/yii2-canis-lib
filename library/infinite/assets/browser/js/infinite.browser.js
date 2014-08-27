function InfiniteBrowser (parent, options) {
	var defaultOptions = {
		'url': '/browse-hierarchy',
      'root': false,
      'data': {},
      'section': {
         'width': 300,
         'animationSpeed': 200
      },
      'callback': function(item) { }
	};
	this.options = jQuery.extend(true, {}, defaultOptions, options);
   $.debug(['browser', this.options])
   if (this.options.root && !(this.options.root instanceof InfiniteBrowserBundle)) {
      this.options.root = new InfiniteBrowserBundle(this, this.options.root);
   }
   this.parent = parent;
   this.bundles = {};
   this.elements = {};
   this.elements.sections = [];
   this.stack = [];
   this.visible = false;
   this.request = false;
   this.init();
}

InfiniteBrowser.prototype.select = function(item) {
   this.options.callback(item);
   this.hide();
   this.reset();
};

InfiniteBrowser.prototype.error = function(message) {
   this.reset(false);
   this.elements.canvas.html('<div class="alert alert-danger">'+ message +'</div>');
};

InfiniteBrowser.prototype.init = function() {
   var self = this;
   this.elements.container = $("<div />").hide().addClass('infinite-browse').appendTo(this.parent);
   this.elements.canvas = $("<div />").addClass('infinite-browse-canvas').appendTo(this.elements.container);
   if (!this.options.root) {
      this.error("No root has been defined!");
   }
   this.drawBundle();
   this.updateViewport();
   $(window).on('resizeDone', function() {
      self.updateViewport();
   });
};

InfiniteBrowser.prototype.addRequestBundle = function (bundle, callback, request) {
   if (request === undefined) {
      if (!this.request || this.request.executed) {
         this.request = new InfiniteBrowserRequest(this);
      }
      request = this.request;
   }
   request.add(bundle, callback);
};

InfiniteBrowser.prototype.drawBundle = function(bundle) {
   if (bundle instanceof InfiniteBrowserBundle) {
      bundle.draw();
   }
};

InfiniteBrowser.prototype.internalDrawBundle = function(bundle, element) {
   if (bundle instanceof InfiniteBrowserBundle) {
      if (this.elements.sections.length > 0) {
         var lastSection = this.elements.sections[this.elements.sections.length-1];
         lastSection.element.removeClass('active-section');
      }
      element.addClass('active-section');
      this.elements.sections.push({'bundle': bundle, 'element': element});
      this.elements.canvas.append(element);
      this.updateViewport();
   }
};

InfiniteBrowser.prototype.draw = function() {
   if (this.options.root) {
      this.drawBundle(this.options.root);
   }
};

InfiniteBrowser.prototype.internalUpdateMarginShift = function(left) {
   this.elements.canvas.animate({'marginLeft': left}, this.options.section.animationSpeed);
};

InfiniteBrowser.prototype.updateViewport = function() {
   var sectionWidth = this.getSectionWidth();
   this.elements.container.find('.section').width(sectionWidth);
   if (this.elements.sections.length === 0) {
      this.internalUpdateMarginShift(0);
   } else {
      var viewportWidth = Math.max(this.elements.container.innerWidth(), sectionWidth);
      var allSectionWidth = this.elements.sections.length * sectionWidth;
      if (allSectionWidth > viewportWidth) {
         var newShift = viewportWidth - allSectionWidth;
         this.internalUpdateMarginShift(newShift);
      } else {
         this.internalUpdateMarginShift(0);
      }
   }
};

InfiniteBrowser.prototype.reset = function(draw) {
   if (draw === undefined) {
      draw = true;
   }
   this.stack = [];
   jQuery.each(this.elements.sections, function(index, value) {
      value.bundle.undraw();
   });
   this.elements.sections = [];
   this.elements.canvas.find('.section').remove();
   if (draw) {
      this.draw();
   }
};

InfiniteBrowser.prototype.show = function() {
   var self = this;
   this.reset();
   this.elements.container.slideDown(function() { 
      self.visible = true; 
      self.updateViewport();
   });
};

InfiniteBrowser.prototype.hide = function() {
   var self = this;
   this.elements.container.slideUp(function() { self.visible = false; self.reset(); });
};

InfiniteBrowser.prototype.appendStackItem = function(bundle, item) {
   var selectedBundlePosition = bundle.getPosition();
   this.goToPositionIndex(selectedBundlePosition, false);
   this.stack.push(item);
   this.handleStack(this.stack.slice(0), true);
};

InfiniteBrowser.prototype.handleStack = function(stack, draw) {
   if (stack.length === 0) {
      this.reset();
      return false;
   } else {
      var stackObject = new InfiniteBrowserStack(stack);
      if (this.bundles[stackObject.getId()] === undefined) {
         this.bundles[stackObject.getId()] = stackObject.getBundle(this);
      }
      if (draw === true) {
         this.drawBundle(this.bundles[stackObject.getId()]);
      }
      return this.bundles[stackObject.getId()];
   }
};

InfiniteBrowser.prototype.getSectionWidth = function() {
   var containerWidth = parseInt(this.elements.container.innerWidth(), 10);
   var width = parseInt(this.options.section.width, 10);
   
   if ((width * 2) > containerWidth) {
      width = containerWidth;
   }
   return width;
};

InfiniteBrowser.prototype.goToPositionIndex = function(index, shiftViewport) {
   if (shiftViewport === undefined) {
      shiftViewport = true;
   }
   var topPosition = this.elements.sections.length - 1;
   var self = this;
   if (index < 0) { // take off from the end [[index]] items
      var lastSection = null;
      while (index < 0) {
         lastSection = this.elements.sections.pop();
         if (lastSection !== undefined) {
            lastSection.bundle.undraw();
         }
         this.stack.pop();
         index++;
      }
      if (this.elements.sections.length > 0) {
         lastSection = this.elements.sections[this.elements.sections.length-1];
         lastSection.element.find('.browser-item.active').removeClass('active');
         lastSection.element.addClass('active-section');
      }
      if (shiftViewport) {
         this.updateViewport();
      }
   } else { // go back until topPosition matches index
      var goBack = index - topPosition;
      if (goBack < 0) {
         this.goToPositionIndex(goBack, shiftViewport);
      }
   }
   if (this.elements.sections.length === 1) {
      // we are at the root level
      this.stack = [];
   }
   return true;
};

function InfiniteBrowserBundle (browser, options) {
   var defaultOptions = {
      'id': null,
      'instructions': {},
      'type': 'item',
      'typeOptions': {},
      'total': null,
      'bundle': false
   };
   this.browser = browser;
   this.element = null;
   this.fetched = false;
   this.items = {};
   this.elementItems = [];
   this.options = jQuery.extend(true, {}, defaultOptions, options);
   this.fetchTimer = null;
   this.offset = 0;
   this.list = null;
   this.listInitialized = false;
   this.rendered = false;
   this.state = 'list';
   this.filterQuery = false;
   this.searchCache = {};
   this.filterTimer = null;

   if (this.options.bundle) {
      this.loadBundleResponse(this.options);
      this.options.bundle = null;
   }
}


InfiniteBrowserBundle.prototype.getInstructions = function() {
   var instructions = this.options.instructions;
   instructions.id = this.getId();
   instructions.offset = this.offset;
   instructions.filterQuery = this.filterQuery;
   return instructions;
};

InfiniteBrowserBundle.prototype.getId = function() {
   return this.options.id;
};
InfiniteBrowserBundle.prototype.undraw = function() {
   this.element.remove();
   this.element = null;
   this.listInitialized = false;
   this.rendered = false;
};

InfiniteBrowserBundle.prototype.draw = function() {
   var self = this;
   this.rendered = true;
   this.state = 'list';
   var section = this.element = $("<div />", {'class': 'section'}).width(this.browser.getSectionWidth());

   var container = this.container = $("<div />", {'class': 'section-container'}).appendTo(section);
   var list = this.list = $("<div />", {'class': 'list-group'}).appendTo(this.container);
   var loadElement = this.loadElement = $("<div />", {'class': 'glyphicon glyphicon-chevron-down infinite-browse-load-element'}).hide().appendTo(this.element);

   this.browser.internalDrawBundle(this, section);
   if (this.fetched) {
      this.drawItems();
   } else {
      this.fetch(function() {
      });
      $("<div />", {'class': 'list-group-item'}).append($("<div />", {'class': 'alert alert-warning'}).html('Loading...')).appendTo(self.list);
   }
};

InfiniteBrowserBundle.prototype.drawItems = function(items) {
   var self = this;
   if (items === undefined) {
      items = this.items;
   }
   if (Object.size(items) === 0) {
      self.emptyListNotice();
      return false;
   }
   jQuery.each(items, function(index, item) {
      self.appendItem(item);
   });
   this.checkLoader();
};

InfiniteBrowserBundle.prototype.checkLoader = function() {
   var self = this;
   if (!this.rendered) { return true; }
   var sectionElement = this.element;
   if (this.isLoaded() || this.state !== 'list') {
      this.loadElement.hide();
      $(sectionElement).unbind('scroll');
      clearTimeout(this.fetchTimer);
   } else {
      this.loadElement.show();
      $(sectionElement).scroll(function(e) {
         clearTimeout(self.fetchTimer);
         var element = $(this);
         self.fetchTimer = setTimeout(function() {
            var height = parseInt(element.height(), 10);
            var scrollHeight = parseInt(element[0].scrollHeight, 10);
            var scrollBottom = parseInt(element.scrollTop(), 10) + height;
            var scrollRemaining = scrollHeight - scrollBottom;
            if (scrollRemaining < (height * 3)) {
               $(sectionElement).unbind('scroll');
               clearTimeout(self.fetchTimer);
               self.fetchMore();
            }
         }, 100);
      });
   }
};

InfiniteBrowserBundle.prototype.isLoaded = function() {
   if (this.options.total !== null && (this.options.total === false || Object.size(this.items) >= this.options.total)) {
      return true;
   }
   return false;
};

InfiniteBrowserBundle.prototype.loadBundleResponse = function(bundleResponse, request) {
   var self = this;

   this.fetched = true;
   if (bundleResponse.filterQuery === false) {
      // update instructions
      if (bundleResponse.instructions !== undefined && bundleResponse.instructions) {
         this.options.instructions = bundleResponse.instructions;
      }

      // update total
      if (bundleResponse.total !== undefined && bundleResponse.total) {
         this.options.total = parseInt(bundleResponse.total, 10);
      } else {
         this.options.total = 0;
      }
      var itemDestination = self.items;
      var render = self.rendered;
   } else {
      var itemDestination = self.searchCache[bundleResponse.filterQuery] = {};
      var render = bundleResponse.filterQuery === this.filterQuery;
   }
   var defaultItem = {
      'id': null,
      'descriptor': null,
      'subdescriptor': null,
      'hasChildren': false,
      'isSelectable': false
   };
   if (bundleResponse.bundle !== undefined && bundleResponse.bundle) {
      this.offset = this.offset + parseInt(bundleResponse.bundle.size, 10);
      jQuery.each(bundleResponse.bundle.items, function(id, item) {
         item = jQuery.extend(true, {}, defaultItem, item);
         if (itemDestination[id] === undefined) {
            itemDestination[id] = item;
            if (render) {
               self.appendItem(item);
            }
         }
      });
   }
   if (Object.size(itemDestination) === 0) {
      self.emptyListNotice();
   }
   this.checkLoader();
};

InfiniteBrowserBundle.prototype.emptyListNotice = function() {
   this.initializeList(false);
   $("<div />", {'class': 'list-group-item browser-none-message'}).append($("<div />", {'class': 'alert alert-danger'}).html('None found!')).appendTo(this.list);
};

InfiniteBrowserBundle.prototype.updateState = function (state, filterQuery) {
   if (filterQuery === undefined) {
      filterQuery = false;
   } else {
      filterQuery = filterQuery.trim();
   }
   var currentState = this.state;
   this.state = state;
   this.filterQuery = filterQuery;
   if (state === 'list' && currentState !== 'list') {
      this.list.find('.browser-item, .browser-none-message').remove();
      this.drawItems(this.items);
   } else if (state === 'search') {
      this.list.find('.browser-item, .browser-none-message').remove();
      this.handleSearch(filterQuery);
   }
};

InfiniteBrowserBundle.prototype.handleSearch = function() {
   var self = this;
   if (!this.filterQuery) { return false; }
   if (this.searchCache[this.filterQuery] !== undefined) {
      this.drawItems(this.searchCache[this.filterQuery]);
   } else {
      clearTimeout(self.filterTimer);
      self.filterTimer = setTimeout(function() {         
         self.fetch(function(bundle) {

         });
      }, 250);
   }
};


InfiniteBrowserBundle.prototype.initializeList = function(search) {
   var self = this;
   if (this.listInitialized) { return true; }
   if (search === undefined) {
      search = true;
   }
   this.listInitialized = true;
   this.list.html('');
   if (self.browser.stack[self.getPosition()-1] !== undefined) {
      var previousStackItem = self.browser.stack[self.getPosition()-1];
      $("<a />", {'href': '#', 'class': 'infinite-browse-back list-group-item'}).html('<i class="glyphicon glyphicon-chevron-left pull-left"></i> Back to <em>'+previousStackItem.descriptor+'</em>').appendTo(self.list).click(function() {
         self.browser.goToPositionIndex(self.getPosition()-1);
      });
      if (previousStackItem.isSelectable) {
         $("<a />", {'href': '#', 'class': 'infinite-browse-select list-group-item'}).html('<i class="glyphicon glyphicon-check pull-right"></i> Select <em>'+previousStackItem.descriptor+'</em>').appendTo(self.list).click(function() {
            self.browser.select(previousStackItem);
         });
      }
   }
   if (search) {
      var searchInput = $("<input />", {'type': 'text', 'class': 'infinite-browse-filter form-control', 'placeholder': 'Filter...'});
      searchInput.on('change keydown keyup', function(e) {
         if (searchInput.val() === '') {
            self.updateState('list');
         } else {
            self.updateState('search', searchInput.val());
         }
      });
      var searchInputContainer = $("<div />", {'class': 'list-group-item'}).appendTo(self.list).append(searchInput);
   }

   if (self.rendered) {
      setTimeout(function() {
         if (self.element) {
            self.element.scrollTop(0);
         }
      }, 500);
   }
};

InfiniteBrowserBundle.prototype.appendItem = function(item) {
   var self = this;
   this.initializeList();
   if (this.list === null) { return false; }
   var element = $("<a />", {'href': '#', 'class': 'browser-item list-group-item'}).html(item.descriptor).appendTo(self.list);
   var clickable = false;
   if (item.hasChildren) {
      $("<i />", {'class': 'glyphicon glyphicon-chevron-right pull-right'}).prependTo(element);
      element.click(function() {
         self.browser.appendStackItem(self, item);
         self.list.find('.browser-item.active').removeClass('active');
         $(this).addClass('active');
      });
      clickable = true;
   }
   if (item.isSelectable) {
      var selectIcon = $("<a />", {'href': '#', 'class': 'glyphicon glyphicon-check pull-right'}).prependTo(element);
      var selectFunction = function() {
         self.browser.select(item);
      };
      selectIcon.click(selectFunction);
      if (!clickable) {
         element.click(selectFunction);
         clickable = true;
      }
   }
   if (!clickable) {
      element.addClass('disabled');
   }
   this.elementItems.push(element);
};

InfiniteBrowserBundle.prototype.getPosition = function() {
   var self = this;
   var position = false;
   var positionTest = 0;
   jQuery.each(this.browser.elements.sections, function(index, value) {
      if (value.bundle === self) {
         position = positionTest;
         return false;
      }
      positionTest++;
   });
   return position;
};

InfiniteBrowserBundle.prototype.fetch = function(callback) {
   if (this.state === 'list' && this.isLoaded()) { return true; }
   this.browser.addRequestBundle(this, callback);
};


InfiniteBrowserBundle.prototype.fetchMore = function() {
   clearTimeout(this.fetchTimer);
   this.fetch(function() {

   });
};

function InfiniteBrowserStack(stack) {
   this.stack = stack;
}

InfiniteBrowserStack.prototype.getStack = function() {
   return this.stack;
};

InfiniteBrowserStack.prototype.getId = function() {
   if (this._id === undefined) {
      var idParts = ['stack'];
      jQuery.each(this.getStack(), function(index, value) {
         var subPart = [];
         subPart.push(value.type);
         subPart.push(value.id);
         idParts.push(subPart.join('.'));
      });
      this._id = idParts.join(';');
   }
   return this._id;
};


InfiniteBrowserStack.prototype.getBundle = function(browser) {
   return new InfiniteBrowserBundle(browser, {
      'id': this.getId(),
      'instructions': this.getInstructions()
   });
};

InfiniteBrowserStack.prototype.getInstructions = function() {
   var instructions = {};
   instructions.id = this.getId();
   instructions.task = 'stack';
   instructions.stack = this.getStack();
   return instructions;
};


function InfiniteBrowserRequest(browser, options) {
   var defaultOptions = {
      'autoexecute': true,
      'ajax': {
         'dataType': 'json'
      }
   };
   if (options === undefined) {
      options = {};
   }
   this.options = jQuery.extend(true, {}, defaultOptions, options);
   this.browser = browser;
   this.bundles = {};
   this.executed = false;
   this.jxhr = false;
}

InfiniteBrowserRequest.prototype.add = function(bundle, callback) {
   if (this.executed) {
      return false;
   }
   var id = bundle.getId();
   if (callback === undefined) {
      callback = false;
   }
   this.bundles[id] = {'bundle': bundle, 'callback': callback};
   if (this.options.autoexecute) {
      this.execute();
   }
   return true;
};

InfiniteBrowserRequest.prototype.execute = function() {
   if (this.executed) { return false; }
   if (Object.size(this.bundles) === 0) { return true; }
   var self = this;
   this.executed = true;
   var self = this;
   var ajaxConfig = this.options.ajax;
   ajaxConfig.success = function(data) { self.callback(data); };
   ajaxConfig.url = this.browser.options.url;
   ajaxConfig.data = this.browser.options.data;
   ajaxConfig.data.requests = {};
   jQuery.each(this.bundles, function (index, bundleSet) {
      var bundle = bundleSet.bundle;
      if (!bundle.options.instructions) { return true; }
      ajaxConfig.data.requests[bundle.getId()] = bundle.getInstructions();
   });
   this.jxhr = jQuery.ajax(ajaxConfig);
};


InfiniteBrowserRequest.prototype.callback = function(data) {
   var self = this;
   if (data.responses === undefined) { return false; }
   jQuery.each(data.responses, function(id, bundleResponse) {
      if (self.bundles[id] === undefined) { return true; }
      var bundleSet = self.bundles[id];
      var bundle = bundleSet.bundle;
      bundle.loadBundleResponse(bundleResponse);
      if (bundleSet.callback) {
         bundleSet.callback(bundle);
      }
   });
};


(function ($) { 
   $.fn.infiniteBrowser = function (opts) {
   		var $this = this;
      	if ($this.objectBrowser === undefined) {
      		$this.objectBrowser = new InfiniteBrowser($this, opts);
      	}

         return $this.objectBrowser;
   };
}(jQuery));