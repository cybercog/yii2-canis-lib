function InfiniteBrowser (parent, options) {
	var defaultOptions = {
		'url': '/browse',
      'root': false
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
   this.handleStack(this.stack.slice(0));
};

InfiniteBrowser.prototype.handleStack = function(stack) {
   if (stack.length === 0) {
      this.reset();
   } else {
      var stackObject = new InfiniteBrowserStack(stack);
      if (this.bundles[stackObject.getId()] === undefined) {
         this.bundles[stackObject.getId()] = stackObject.getBundle(this);
      }
      this.drawBundle(this.bundles[stackObject.getId()]);
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
   $.debug("FETCH!");
};

function InfiniteBrowserStack(stack) {
   this.stack = stack;
}

InfiniteBrowserStack.prototype.getStack = function() {
   return this.stack;
};

InfiniteBrowserStack.prototype.getId = function() {
   if (this._id === undefined) {
      var idParts = [];
      $.debug(['hey there', this.getStack()]);
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
   instructions.task = 'stack';
   instructions.stack = this.getStack();
   return instructions;
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