function RestDraw($element, requestOptions) {
	this.request = null;
	this.$element = $element;
	this.$element.html('');
	this.requestOptions = jQuery.extend(true, {}, this.defaultRequest, requestOptions);
	this.destroy();
	this.success(JSON.stringify(this.requestOptions));
	this.requestPage(1);
}

RestDraw.prototype.defaultRequest = {
	'type': 'GET',
	'dataType': 'json',
};

RestDraw.prototype.requestPage = function(page) {
	if (this.request) {
		this.request.abort();
	}
	var requestOptions = jQuery.extend(true, {}, this.requestOptions);
	requestOptions.success = this.handleSuccess;
	requestOptions.error = this.handleError;
	requestOptions.data['page'] = page;

	this.$element.html('');
	this.$canvas = $("<div />", {'class': 'panel panel-default'}).appendTo(this.$element);
	this.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.$canvas);
	this.$footer = $("<div />", {'class': 'panel-footer'}).appendTo(this.$canvas).hide();
	for(i =0; i < 10; i++) {
		$("<div />", {'class': 'widget-lazy-placeholder'}).appendTo(this.$body);
	}
	this.request = jQuery.ajax(requestOptions);
	this.request.restDraw = this;
};

RestDraw.prototype.handleSuccess = function (data, textStatus, jqXHR) {
	var self = jqXHR.restDraw;
	var headerDeferred = jqXHR.getResponseHeader('X-Deferred');
	var headerCurrentPage = parseInt(jqXHR.getResponseHeader('X-Pagination-Current-Page'), 10);
	var headerPageCount = parseInt(jqXHR.getResponseHeader('X-Pagination-Page-Count'), 10);
	if (data.length === 0) {
		self.notice("No items were found.");
		self.$canvas.remove();
	}
	self.drawItems(self.$body, data);
	self.drawPager(self.$footer, headerCurrentPage, headerPageCount);
};

RestDraw.prototype.drawItems = function($itemContainer, items) {
	var self = this;
	$itemContainer.html('');
	var $itemList = $("<div />").addClass('teal-rest-draw-list list-group').appendTo($itemContainer);
	jQuery.each(items, function(index, item) {
		self.drawItem($itemList, item);
	});
};

RestDraw.prototype.drawItem = function($itemContainer, item) {
	var $itemObject = $("<a />", {'href': '#', 'class': 'teal-rest-draw-item list-group-item'}).appendTo($itemContainer);
	if (item._links.self.href !== undefined) {
		$itemObject.attr('href', item._links.self.href);
	}
	$("<h4 />", {'class': 'list-group-item-heading'}).html(item.descriptor).appendTo($itemObject);
	if (item.subdescriptor[0] !== undefined) {
		$("<p />", {'class': 'list-group-item-text'}).html(item.subdescriptor[0].rich).appendTo($itemObject);
	}
	return $itemObject;
};

RestDraw.prototype.drawPager = function($pagerContainer, currentPage, pageCount) {
	var self = this;
	var $pagerList = $("<ul />", {'class': 'pagination'}).appendTo($pagerContainer);
	$pagerContainer.show();
	var minPage = Math.max(1, currentPage - 5);
	var maxPage = Math.min(pageCount, minPage + 10);
	minPage = Math.max(1, Math.min(minPage, maxPage-10));
	var previousPage = nextPage = false;
	if (currentPage-1 > 0) {
		previousPage = currentPage-1;
	}
	if (currentPage+1 <= pageCount) {
		nextPage = currentPage+1;
	}
	var previousItem = $("<li />").appendTo($pagerList);
	if (!previousPage) {
		previousItem.addClass('disabled');
	}
	$("<a />", {'href': '#'}).data('page', previousPage).appendTo(previousItem).click(function() {
		self.requestPage($(this).data('page'));
		return false;
	}).html("&laquo;");

	for (i = minPage; i < maxPage+1; i++) {
    	var listItem = $("<li />").appendTo($pagerList);
    	if (i === currentPage) {
    		listItem.addClass('active');
    	}
    	$("<a />", {'href': '#'}).data('page', i).appendTo(listItem).click(function() {
    		self.requestPage($(this).data('page'));
    		return false;
    	}).html(i);
	}


	var nextItem = $("<li />").appendTo($pagerList);
	if (!nextPage) {
		nextItem.addClass('disabled');
	}
	$("<a />", {'href': '#'}).data('page', nextPage).appendTo(nextItem).click(function() {
		self.requestPage($(this).data('page'));
		return false;
	}).html("&raquo;");
};

RestDraw.prototype.error = function(message) {
	return this.message(message, 'alert-danger');
}

RestDraw.prototype.notice = function(message) {
	return this.message(message, 'alert-warning');
}

RestDraw.prototype.info = function(message) {
	return this.message(message, 'alert-info');
}

RestDraw.prototype.success = function(message) {
	return this.message(message, 'alert-success');
}


RestDraw.prototype.destroy = function() {
	this.$element.html('');
	if (this.request) {
		this.request.abort();
	}
}

RestDraw.prototype.message = function(message, cssClass) {
	if (this.$message) {
		this.$message.remove();
	}
	this.$message = $("<div />", {'class': 'alert ' + cssClass}).html(message).prependTo(this.$element);
}