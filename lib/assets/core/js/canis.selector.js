function CanisSelector(parent, options) {
   var self = this;
   this.parent = parent;
   var defaultOptions = {
      'browser': 'objectBrowse',
      'searcher': 'objectSearch',


      'inputLabel': 'Choose',
      'browseLabel': 'Browse',
      'searchLabel': 'Search',
      'relationshipSeparator': '.',
      'context': {
         'relationship': false,
         'object': false,
         'role': false
      },
      'browse': {
         'data': {}
      },
      'search': {
         'data': {}
      },
      'callback': function ($selector, item) { $.debug(item); return false; }
   };

   this.options = jQuery.extend(true, {}, defaultOptions, options);

   if (CanisLister !== undefined && this.options.callback instanceof CanisLister) {
      var lister = this.options.callback;
      this.options.callback = function ($selector, item) {
         lister.addItem(item);
      };
   }

   var baseQueryData = {};
   if (this.options.context.relationship && this.options.context.role) {
      var relationshipParts = this.options.context.relationship.id.split(this.options.relationshipSeparator);
      if (this.options.context.role === 'child') {
         baseQueryData['modules'] = [relationshipParts[1]];
      } else {
         baseQueryData['modules'] = [relationshipParts[0]];
      }
   }
   var selectQueryData = jQuery.extend(true, {}, baseQueryData);
   var browseQueryData = jQuery.extend(true, {}, baseQueryData);

   if (this.options.context.object) {
      if (this.options.context.relationship && this.options.context.role) {
         if (this.options.context.role === 'child') {
            selectQueryData['ignoreParents'] = [this.options.context.object.id];
         } else {
            selectQueryData['ignoreChildren'] = [this.options.context.object.id];
         }
      }
      selectQueryData['ignore'] = browseQueryData['ignore'] = [this.options.context.object.id];
   }

   this.selectorElements = {};
   this.searchInputId = this.parent.attr("id") + '-search';
   if (this.options.canvasTarget === undefined) {
      this.selectorElements.canvas = $("<div />").addClass('object-selector-canvas').insertAfter(this.parent);
   } else {
      this.selectorElements.canvas = $("<div />").addClass('object-selector-canvas').prependTo(this.options.canvasTarget);
   }
   this.selectorElements.selector = $("<div />").addClass('object-selector').appendTo(this.selectorElements.canvas);
   if (this.options.inputLabel) {
      this.selectorElements.label = $("<label />", {'for': this.searchInputId}).html(this.options.inputLabel).appendTo(this.selectorElements.selector);
   }
   this.selectorElements.inputGroup = $("<div />", {'class': 'input-group'}).appendTo(this.selectorElements.selector);
   this.selectorElements.input = $("<input />", {'type': 'text', 'class': 'form-control', 'id': this.searchInputId}).appendTo(this.selectorElements.inputGroup);
   var searchSelectCallback = function(object, datum) {
      self.options.callback(self, datum);
   };
   var browseSelectCallback = function(datum) {
      self.options.callback(self, datum);
   };
   var searchOptions = jQuery.extend(true, {}, this.options.search, {data: selectQueryData});
   searchOptions.callback = searchSelectCallback;
   var objectSearcher = this.options.searcher;
   this.selectorElements.input[objectSearcher](searchOptions);
   this.selectorElements.inputAddon = $("<span />", {'class': 'input-group-btn'}).appendTo(this.selectorElements.inputGroup);
   this.selectorElements.browseArea = $("<div />", {'class': 'object-browse-container'}).appendTo(this.selectorElements.selector);
   this.selectorElements.browseButton = $("<button />", {'class': 'btn btn-default', 'type': 'button'}).html(this.options.browseLabel).appendTo(this.selectorElements.inputAddon);
   this.selectorElements.browseButton.click(function() {
      var browseOptions = jQuery.extend(true, {}, self.options.browse, {data: browseQueryData});
      browseOptions.callback = browseSelectCallback;
      var objectBrowser = self.options.browser;
      var objectBrowser = self.selectorElements.browseArea[objectBrowser](browseOptions);
      if (objectBrowser.visible) {
         self.selectorElements.browseButton.text(self.options.browseLabel);
         self.selectorElements.input.attr({'disabled': false});
         self.selectorElements.input.val(self.selectorElements.input.data('previousValue'));
         objectBrowser.hide();
      } else {
         self.selectorElements.browseButton.text(self.options.searchLabel);
         self.selectorElements.input.attr({'disabled': true});
         self.selectorElements.input.data('previousValue', self.selectorElements.input.val());
         self.selectorElements.input.val('Browsing...');
         objectBrowser.show();
      }
   });
}
  
CanisSelector.prototype.resetSelector = function() {
   this.selectorElements.browseButton.text(this.options.browseLabel);
   this.selectorElements.input.attr({'disabled': false});
   this.selectorElements.input.val('');
   this.selectorElements.input.data('previousValue', '');
};

CanisSelector.prototype.hideSelector = function() {
   this.resetSelector();
   this.selectorElements.canvas.hide();
};

CanisSelector.prototype.showSelector = function() {
   this.selectorElements.canvas.show();
};


(function ($) { 
   $.fn.canisSelector = function (options) {
         var $this = this;
         if ($this.canisSelectorObject === undefined) {
            $this.canisSelectorObject = new CanisSelector($this, options);
         }
         return $this.canisSelectorObject;
   };
}(jQuery));