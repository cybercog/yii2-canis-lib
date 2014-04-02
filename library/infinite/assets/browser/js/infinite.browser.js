function InfiniteBrowser (parent, options) {
	var defaultOptions = {
		'url': '/browse',
      'root': false
	};
	this.options = jQuery.extend(true, {}, defaultOptions, options);
   if (this.options.root && !(this.options.root instanceof InfiniteBrowserBundle)) {
      this.options.root = new InfiniteBrowserBundle(this.options.root);
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
      bundle.draw(this);
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

function InfiniteBrowserBundle (options) {
   var defaultOptions = {
   };
   this.options = jQuery.extend(true, {}, defaultOptions, options);
   $.debug(['bundle', this.options]);
}

InfiniteBrowserBundle.prototype.draw = function(browser) {
   var section = $("<div />").html("holla");
   browser.internalDrawBundle(this, section);
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