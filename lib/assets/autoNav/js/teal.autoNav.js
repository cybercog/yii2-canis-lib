
function TealAutoNav($element, config) {
	TealComponent.call(this);
	var self = this;
	this.config = jQuery.extend(true, {}, this.defaultConfig, config);
	this.elements = {};
	this.$element = $element;
	this.$element.uniqueId();
	this.$element.addClass('teal-auto-nav');
	this.render();
	this.isInitializing = false;
	$(document).on('DOMNodeInserted', function(e) {
	    if ($(e.target).is(self.config.itemSelector) || $(e.target).find(self.config.itemSelector).length > 0) {
	    	console.log("found item!");
	    	self.safeUpdateMenu();
	    }
	});
	console.log(['auto-nav', this]);
}
TealAutoNav.prototype = jQuery.extend(true, {}, TealComponent.prototype);

TealAutoNav.prototype.objectClass = 'TealAutoNav';

TealAutoNav.prototype.render = function() {
	this.elements.$menu = $("<ul />", {'class': 'nav nav-pills nav-stacked'}).appendTo(this.$element);
	if (!this.elements.$menu.attr('id')) {
		var n = $(".teal-auto-nav").index(this.$element);
		this.elements.$menu.attr('id', 'teal-auto-nav-' + n);
	}
	this.id = this.elements.$menu.attr('id');
	if (this.config.affix) {
		this.$element.tealAffix();
	}
	$('body').scrollspy({ target: '#' + this.$element.attr('id'), 'offset': this.$element.tealNavBarHeight() + 11 })
	this.$element.on('activate.bs.scrollspy', function () {
			console.log("scrollspy fire");
		if ($(this).find('.active').length === 0) {
			$(this).find('li').first().addClass('active');
		} else {
		}
	});
	this.updateMenu();
};

TealAutoNav.prototype.safeUpdateMenu = function(callback) {
	var self = this;
	if (this.updateMenuTimer !== undefined) {
		clearTimeout(this.updateMenuTimer);
	}
	return this.updateMenuTimer = setTimeout(function() { self.updateMenu(callback); }, 500);
}

TealAutoNav.prototype.updateMenu = function() {
	var self = this;
	var menuItems = $(this.config.itemSelector);
	if (menuItems.length === 0) {
		this.$element.hide();
		console.log(['no items!', this.config.itemSelector, menuItems]);
		return;
	}
	this.$element.show();
	this.elements.$menu.html('');
	var firstId = false;
	if (this.config.topLabel) {
		if ($("#top").length === 0) {
			$('body').prepend($("<div />", {'id':  'top'}));
		}
		if (this.config.topLabel === true) {
			firstId = 'top';
		} else {
			var $topItem = $("<li />", {'class': 'active'}).appendTo(self.elements.$menu);
			var $topLink = $("<a />", {'href': '#top'}).html(this.config.topLabel).appendTo($topItem);
		}
	}
	jQuery.each(menuItems, function() {
		$(this).uniqueId(); //jquery-ui function
		var itemId = $(this).attr('id');
		var baseClass = '';
		if (firstId) {
			itemId = firstId;
			baseClass = 'active';
			firstId = false;
		}
		var title = $(this).html();
		if ($(this).data('nav-title')) {
			title = $(this).data('nav-title');
		}
		var $item = $("<li />", {'class': baseClass}).appendTo(self.elements.$menu);
		var $link = $("<a />", {'href': '#'+itemId}).html(title).appendTo($item);
	});
};

TealAutoNav.prototype.defaultConfig = {
	'topLabel': false,
	'affix': false,
	'itemSelector': '.teal-auto-nav-item'
};

$preparer.add(function(context) {	
	$("[data-auto-nav]", context).each(function() {
		var params = $(this).data('auto-nav');
		$(this).removeAttr('data-auto-nav');
		$(this).data('teal-auto-nav', new TealAutoNav($(this), params));
	});
});
