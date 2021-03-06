function CanisLister($element, config) {
	var self = this;
	this.isInitializing = true;
	this.$element = $element;
	this.config = jQuery.extend(true, {}, this.defaultConfig, config);
	this.elements = {};
	this.items = {};
	this.init();
	if (this.config.items !== undefined && !_.isEmpty(this.config.items)) {
		jQuery.each(this.config.items, function (index, item) {
			self.addItem(item);
		});
	}
	this.isInitializing = false;
}

CanisLister.prototype.defaultConfig = {
	emptyMessage: false
};

CanisLister.prototype.isInitializing = true;
CanisLister.prototype.init = function() {
	this.elements.$canvas = $("<div />", {'class': 'list-group'}).appendTo(this.$element);
	this.elements.$emptyMessage = $("<div />", {'class': 'list-group-item list-group-item-warning'}).html(this.config.emptyMessage).appendTo(this.elements.$canvas);
	if (!this.config.emptyMessage) {
		this.elements.$emptyMessage.hide();
	}
};

CanisLister.prototype.addItem = function(item) {
	if (item.id === undefined) { return false; }
	if (this.items[item.id] === undefined) {
		this.elements.$emptyMessage.hide();
		this.items[item.id] = {};
		this.items[item.id].$element = this.getItemElement(item);
		this.items[item.id].meta = item;
		this.items[item.id].$element.appendTo(this.elements.$canvas);
		if (!this.isInitializing) {
			this.$element.trigger('addItem.canisLister');
			this.$element.trigger('changeItems.canisLister');
		}
	}
};

CanisLister.prototype.deleteItem = function(itemId) {
	if (this.items[itemId] !== undefined) {
		this.elements.$emptyMessage.hide();
		this.items[itemId].$element.remove();
		delete this.items[itemId];
		if (this.config.emptyMessage && _.size(this.items) === 0) {
			this.elements.$emptyMessage.show();
		}
		this.$element.trigger('deleteItem.canisLister');
		this.$element.trigger('changeItems.canisLister');
	}
};

CanisLister.prototype.getItems = function() {
	var items = {};
	jQuery.each(this.items, function(i, item) {
		items[item.meta.id] = item.meta;
	});
	return items;
};

CanisLister.prototype.getItemElement = function(item) {
	var self = this;
	var $item = $("<div />", {'class': 'list-group-item'});
	var $closeButton = $("<button />", {'class': 'close', 'type': 'button'}).appendTo($item);
	$("<span />", {'aria-hidden': 'true'}).html('&times;').appendTo($closeButton);
	$("<span />", {'class': 'sr-only'}).html('Close').appendTo($closeButton);
	$closeButton.click(function() {
		self.deleteItem(item.id);
	});

	var $heading = $("<h4 />", {'class': 'list-group-item-heading'}).html(item.descriptor).appendTo($item);
	if (item.subdescriptor) {
		var $subdescriptor = $("<div/>", {'class': 'list-group-item-text'}).html(item.subdescriptor).appendTo($item);
	}
	return $item;
};

(function ($) { 
   $.fn.canisLister = function (opts) {
   		var $this = this;
      	if ($this.canisListerObject === undefined) {
      		$this.canisListerObject = new CanisLister($this, opts);
      	}

         return $this.canisListerObject;
   };
}(jQuery));