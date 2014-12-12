var SearchItemResult =  function(context) {
   var $item = $("<a />", {'class': 'infinite-search-result', 'href': '#'}).click(function(e) { e.preventDefault(); });
   if (context.icon !== undefined) {
      var $icon = $("<span />", context.icon).appendTo($item).addClass('infinite-search-icon');
   };
   if (context.descriptor !== undefined) {
      var $descriptor = $("<h4 />", {class: 'infinite-search-header'}).html(context.descriptor).appendTo($item);
   }
   if (context.subdescriptor !== undefined) {

      if (typeof context.subdescriptor === 'object' && Array.isArray(context.subdescriptor)) {
         context.subdescriptor = context.subdescriptor.join("<br />");
      }
      var $subdescriptor = $("<div />", {class: 'infinite-search-text'}).html(context.subdescriptor).appendTo($item);
   }
   return $item;
};

(function ($) { 
   $.fn.infiniteSearch = function (opts) {
   		var $this = this;
   		var defaultOptions = {
      		'remote': {
      			'url': '/search',
      		},
            'resultsBox': {
               'maxWidth': 200,
               'oriented': 'right',
            },
            'data': {},
      		'maxParallelRequests': 2,
            'templates': {
               'empty': '<div class="infinite-search-result infinite-search-empty"><h4 class="infinite-search-header">No objects matched your query.</h4></div>',
               'suggestion': SearchItemResult
            },
            'callback': function (object, datum) { $.debug(object); return false; }
   		};

   		$this.options = jQuery.extend(true, {}, defaultOptions, opts);
   		if ($this.options.name === undefined) {
   			$this.options.name = $this.attr('id');
   		}

         var engineOptions = {
            'name': 'objects',
            'queryTokenizer': Bloodhound.tokenizers.whitespace,
            'datumTokenizer': Bloodhound.tokenizers.obj.whitespace('descriptor'),
            'limit': 10,
            'remote': {
               'url': $this.options.remote.url,
               'ajax': {
                  'data': $this.options.data
               }
            }
         };
         var typeOptions = {};
         var typeSource = {};

         var data = {'term': '--QUERY--'};
         engineOptions.remote.url += '?' + jQuery.param(data);
         engineOptions.remote.url = engineOptions.remote.url.replace('--QUERY--', '%QUERY');
         var engine = new Bloodhound(engineOptions);
         engine.initialize();
         typeSource.name = 'objects';
         typeSource.displayKey = 'label',
         typeSource.source = engine.ttAdapter()
         typeSource.templates = {
            'empty':  $this.options.templates.empty,
            'suggestion': $this.options.templates.suggestion
         };
      	var $typeaheadInput = $this
            .typeahead(typeOptions, typeSource)
            .on('typeahead:autocompleted', function(event) { event.stopPropagation(); return false; })
            .on('typeahead:selected', $this.options.callback);
         var typeahead = $typeaheadInput.data('ttTypeahead');
         $typeaheadInput.on('typeahead:opened', function() {
            var typeaheadWidth = Math.max($this.options.resultsBox.maxWidth, parseInt($typeaheadInput.outerWidth(), 10));
            if ($this.options.resultsBox.oriented === 'left') {
               typeahead.dropdown.$menu.css({'left': 'auto', 'right': 0});
            }
            var marginTop = parseInt($typeaheadInput.css('padding-bottom'), 10) / 2;
            if ($typeaheadInput.parents('.input-group').length > 0) {
               marginTop += parseInt($typeaheadInput.outerHeight(), 10);
            }
            typeahead.dropdown.$menu.css({'min-width': typeaheadWidth});
            typeahead.dropdown.$menu.css({'margin-top': marginTop});
         });
   };
}(jQuery));