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
	this.elements = {};
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
   this.stack = [];
   this.elements.sections = [];
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
      this.elements.sections.push(sectionElement);
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

function InfiniteBrowserBundle (browser, options) {
   var defaultOptions = {
      'id': null,
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
   $.debug(['bundle', this.options.bundle]);
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
   browser.internalDrawBundle(this, section);
};

InfiniteBrowserBundle.prototype.drawInitialList = function(callback) {
   var self = this;
   if (Object.size(this.items) === 0) {
      var list = this.list = $("<div />", {'class': 'alert alert-danger'}).html('None found.').appendTo(this.element);
      return false;
   }
   this.element.html('');
   var list = this.list = $("<div />", {'class': 'list-group'}).appendTo(this.element);
   jQuery.each(this.items, function(index, value) {
      self.appendItem(value);
   });
};
InfiniteBrowserBundle.prototype.appendItem = function(item) {
   var self = this;
   if (this.list === null) { return false; }
   $("<a />", {'href': '#', 'class': 'object-type list-group-item'}).html('<i class="glyphicon glyphicon-chevron-right"></i>' + item.label).appendTo(self.list).click(function() {
      self.list.find('.object-type.active').removeClass('active');
      $(this).addClass('active');
   });
};
InfiniteBrowserBundle.prototype.fetch = function(callback) {

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