(function ($) { 
   $.fn.infiniteSelector = function (opts) {
		var $this = this;
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

		$this.options = jQuery.extend(true, {}, defaultOptions, opts);
      var baseQueryData = {};
      if ($this.options.context.relationship && $this.options.context.role) {
         var relationshipParts = $this.options.context.relationship.id.split($this.options.relationshipSeparator);
         if ($this.options.context.role === 'child') {
            baseQueryData['modules'] = [relationshipParts[1]];
         } else {
            baseQueryData['modules'] = [relationshipParts[0]];
         }
      }
      var selectQueryData = jQuery.extend(true, {}, baseQueryData);
      var browseQueryData = jQuery.extend(true, {}, baseQueryData);

      if ($this.options.context.object) {
         if ($this.options.context.relationship && $this.options.context.role) {
            if ($this.options.context.role === 'child') {
               selectQueryData['ignoreParents'] = [$this.options.context.object.id];
            } else {
               selectQueryData['ignoreChildren'] = [$this.options.context.object.id];
            }
         }
         selectQueryData['ignore'] = browseQueryData['ignore'] = [$this.options.context.object.id];
      }


      $this.selectorElements = {};
      $this.searchInputId = $this.attr("id") + '-search';
      if ($this.options.canvasTarget === undefined) {
         $this.selectorElements.canvas = $("<div />").addClass('object-selector-canvas').insertAfter($this);
      } else {
         $this.selectorElements.canvas = $("<div />").addClass('object-selector-canvas').prependTo($this.options.canvasTarget);
      }
      $this.selectorElements.selector = $("<div />").addClass('object-selector').appendTo($this.selectorElements.canvas);
      $this.selectorElements.label = $("<label />", {'for': $this.searchInputId}).html($this.options.inputLabel).appendTo($this.selectorElements.selector);
      $this.selectorElements.inputGroup = $("<div />", {'class': 'input-group'}).appendTo($this.selectorElements.selector);
      $this.selectorElements.input = $("<input />", {'type': 'text', 'class': 'form-control', 'id': $this.searchInputId}).appendTo($this.selectorElements.inputGroup);
      var searchSelectCallback = function(object, datum) {
         $this.options.callback($this, datum);
      };
      var browseSelectCallback = function(datum) {
         $this.options.callback($this, datum);
      };
      var searchOptions = jQuery.extend(true, {}, $this.options.search, {data: selectQueryData});
      searchOptions.callback = searchSelectCallback;
      var objectSearcher = this.options.objectSearcher;
      $this.selectorElements.input[objectSearcher](searchOptions);
      $this.selectorElements.inputAddon = $("<span />", {'class': 'input-group-btn'}).appendTo($this.selectorElements.inputGroup);
      $this.selectorElements.browseArea = $("<div />", {'class': 'object-browse-container'}).appendTo($this.selectorElements.selector);
      $this.selectorElements.browseButton = $("<button />", {'class': 'btn btn-default', 'type': 'button'}).html($this.options.browseLabel).appendTo($this.selectorElements.inputAddon);
      $this.selectorElements.browseButton.click(function() {
         var browseOptions = jQuery.extend(true, {}, $this.options.browse, {data: browseQueryData});
         browseOptions.callback = browseSelectCallback;
         var objectBrowser = this.options.objectBrowser;
         var objectBrowser = $this.selectorElements.browseArea[objectBrowser](browseOptions);
         if (objectBrowser.visible) {
            $this.selectorElements.browseButton.text($this.options.browseLabel);
            $this.selectorElements.input.attr({'disabled': false});
            $this.selectorElements.input.val($this.selectorElements.input.data('previousValue'));
            objectBrowser.hide();
         } else {
            $this.selectorElements.browseButton.text($this.options.searchLabel);
            $this.selectorElements.input.attr({'disabled': true});
            $this.selectorElements.input.data('previousValue', $this.selectorElements.input.val());
            $this.selectorElements.input.val('Browsing...');
            objectBrowser.show();
         }
      });
      
      $this.resetSelector = function() {
         $this.selectorElements.browseButton.text($this.options.browseLabel);
         $this.selectorElements.input.attr({'disabled': false});
         $this.selectorElements.input.val('');
         $this.selectorElements.input.data('previousValue', '');
      };

      $this.hideSelector = function() {
         $this.resetSelector();
         $this.selectorElements.canvas.hide();
      };

      $this.showSelector = function() {
         $this.selectorElements.canvas.show();
      };
   };
}(jQuery));