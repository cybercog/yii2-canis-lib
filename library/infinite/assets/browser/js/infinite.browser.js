function InfiniteBrowser (parent, options) {
	var defaultOptions = {
		'url': '/browse',
      'root': false,
      'data': {}
	};
	this.options = jQuery.extend(true, {}, defaultOptions, options);
   if (this.options.root && !(this.options.root instanceof InfiniteBrowserBundle)) {
      this.options.root = new InfiniteBrowserBundle(this, this.options.root);
   }
   $.debug(['holla', this.options]);
   this.parent = parent;
   this.bundles = {};
   this.elements = {};
   this.elements.sections = [];
   this.stack = [];
   this.visible = false;
   this.request = false;
   this.init();
}

InfiniteBrowser.prototype.error = function(message) {
   this.reset(false);
   this.elements.canvas.html('<div class="alert alert-danger">'+ message +'</div>');
};

InfiniteBrowser.prototype.init = function() {
   var self = this;
   this.elements.canvas = $("<div />").hide().addClass('infinite-browse').appendTo(this.parent);
   if (!this.options.root) {
      this.error("No root has been defined!");
   }
   this.drawBundle();
   this.updateDimensions();
   $(window).on('resizeDone', function() {
      self.updateDimensions();
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
   } else {
      $.debug(["woops", bundle]);
   }
};

InfiniteBrowser.prototype.internalDrawBundle = function(bundle, element) {
   if (bundle instanceof InfiniteBrowserBundle) {
      var sectionElement = $("<div />", {'class': 'section'}).append(element);
      this.elements.sections.push({'bundle': bundle, 'element': sectionElement});
      this.elements.canvas.append(sectionElement);
   }
};

InfiniteBrowser.prototype.draw = function() {
   if (this.options.root) {
      this.drawBundle(this.options.root);
   }
};


InfiniteBrowser.prototype.updatePositioning = function() {
};

InfiniteBrowser.prototype.updateDimensions = function() {
   this.updatePositioning();
};

InfiniteBrowser.prototype.reset = function(draw) {
   if (draw === undefined) {
      draw = true;
   }
   this.stack = [];
   this.elements.sections = [];
   this.elements.canvas.find('.section').remove();
   if (draw) {
      this.draw();
   }
};

InfiniteBrowser.prototype.show = function() {
   var self = this;
   this.reset();
   this.elements.canvas.slideDown(function() { 
      self.visible = true; 
      self.updateDimensions();
   });
};

InfiniteBrowser.prototype.hide = function() {
   var self = this;
   this.elements.canvas.slideUp(function() { self.visible = false; });
};

InfiniteBrowser.prototype.appendStackItem = function(bundle, item) {
   // $.debug([item, ]);
   var selectedBundlePosition = bundle.getPosition();
   this.goToPositionIndex(selectedBundlePosition);
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

InfiniteBrowser.prototype.goToPositionIndex = function(index) {
   var topPosition = this.elements.sections.length - 1;
   if (index < 0) { // take off from the end [[index]] items
      $.debug(['go back', index]);
   } else { // go back until topPosition matches index
      var goBack = index - topPosition;
      if (goBack < 0) {
         this.goToPositionIndex(goBack);
      } else {
         $.debug("we're there!");
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
   this.options = jQuery.extend(true, {}, defaultOptions, options);
   if (this.options.bundle) {
      this.loadBundle(this.options.bundle);
      this.fetched = true;
      this.options.bundle = null;
   }
   $.debug(['bundle', this]);
}

InfiniteBrowserBundle.prototype.getId = function() {
   return this.options.id;
};

InfiniteBrowserBundle.prototype.loadBundle = function(bundle) {
   var self = this;
   jQuery.each(bundle.items, function(index, value) {
      self.items[index] = value;
      if (self.list !== null) {
         // append it to list
         self.appendItem(value);
      }
   });
};

InfiniteBrowserBundle.prototype.draw = function() {
   var self = this;
   var section = this.element = $("<div />", {'class': 'section-container'});
   if (this.fetched) {
      this.drawInitialList();
   } else {
      this.fetch(function() {
         self.drawInitialList();
      });
      section.html("Thinking...");
   }
   this.browser.internalDrawBundle(this, section);
};

InfiniteBrowserBundle.prototype.loadBundleResponse = function(bundleResponse) {
   var self = this;
   // update instructions
   if (bundleResponse.instructions !== undefined && bundleResponse.instructions) {
      this.options.instructions = bundleResponse.instructions;
   }

   // update total
   if (bundleResponse.total !== undefined && bundleResponse.total) {
      this.options.total = bundleResponse.total;
   }

   if (bundleResponse.bundle !== undefined && bundleResponse.bundle) {
      jQuery.each(bundleResponse.bundle.items, function(id, item) {
         if (self.items[id] === undefined) {
            self.items[id] = item;
            self.appendItem(item);
         }
      });
   }
};

InfiniteBrowserBundle.prototype.drawInitialList = function(callback) {
   var self = this;
   if (Object.size(this.items) === 0) {
      var list = this.list = $("<div />", {'class': 'alert alert-danger'}).html('None found.').appendTo(this.element);
      return false;
   }
   this.element.html('');
   var list = this.list = $("<div />", {'class': 'list-group'}).appendTo(this.element);

   if (this.browser.elements.sections.length > 0) {
      $("<a />", {'href': '#', 'class': 'browser-back list-group-item'}).html('<i class="glyphicon glyphicon-chevron-left pull-left"></i> Back').appendTo(self.list).click(function() {
         self.list.find('.object-type.active').removeClass('active');
         $(this).addClass('active');
         self.browser.goToPositionIndex();
      });
   }

   jQuery.each(this.items, function(index, value) {
      self.appendItem(value);
   });
};
InfiniteBrowserBundle.prototype.appendItem = function(item) {
   var self = this;
   if (this.list === null) { return false; }
   $("<a />", {'href': '#', 'class': 'browser-item list-group-item'}).html('<i class="glyphicon glyphicon-chevron-right pull-right"></i>' + item.label).appendTo(self.list).click(function() {
      self.list.find('.browser-item.active').removeClass('active');
      $(this).addClass('active');
      self.browser.appendStackItem(self, item);
   });
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
   this.browser.addRequestBundle(this, callback);
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
      ajaxConfig.data.requests[bundle.getId()] = bundle.options.instructions;
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
   $.debug(data);
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