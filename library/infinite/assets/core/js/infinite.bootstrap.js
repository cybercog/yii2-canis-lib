// match position and size
jQuery.fn.outerHTML = function() {
  return jQuery('<div />').append(this.eq(0).clone()).html();
};
jQuery.fn.overflown = function(){
	var e=this[0];
	return e.scrollHeight>e.clientHeight||e.scrollWidth>e.clientWidth;
}
jQuery.fn.isElementInViewport = function() {
	if (!this.is(':visible')) { return false; }
    var rect = this[0].getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
        rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
    );
}

jQuery.fn.extend({
	matchPositionSize: function($base, minimums) {
		return this.each(function() {
			var basePosition = $base.position();
			var baseHeight = $base.outerHeight();
			var baseWidth = $base.outerWidth();
			var baseTopMargin = parseInt($base.css('margin-top'), 10);
			var baseLeftMargin = parseInt($base.css('margin-left'), 10);
			var baseBottomMargin = parseInt($base.css('margin-bottom'), 10);
			var baseRightMargin = parseInt($base.css('margin-right'), 10);
			baseWidth = baseWidth + baseLeftMargin + baseRightMargin;
			baseHeight = baseHeight + baseTopMargin + baseBottomMargin;
			if (minimums !== undefined && minimums.height !== undefined && baseHeight < minimums.height) {
				var heightDiff = minimums.height - baseHeight;
				basePosition.top = basePosition.top - (heightDiff / 2);
				baseHeight = minimums.height;
			}
			if (minimums !== undefined && minimums.width !== undefined && baseWidth < minimums.width) {
				var widthDiff = minimums.width - baseWidth;
				basePosition.left = basePosition.left - (widthDiff / 2);
				baseWidth = minimums.width;
			}
			$(this).height(baseHeight);
			$(this).width(baseWidth);
			$(this).css(basePosition);
		});
	}
});

// simple effects
jQuery.fn.slideLeftHide = function(speed, callback) { 
  this.animate({ 
    width: "hide", 
    paddingLeft: "hide", 
    paddingRight: "hide", 
    marginLeft: "hide", 
    marginRight: "hide" 
  }, speed, callback);
}

jQuery.fn.slideLeftShow = function(speed, callback) { 
  this.animate({ 
    width: "show", 
    paddingLeft: "show", 
    paddingRight: "show", 
    marginLeft: "show", 
    marginRight: "show" 
  }, speed, callback);
}

// ajax form
$(document).on('submit.infinite-api', 'form.ajax', function(e) {
	var options = {};
	if ($(this).data('data')) {
		options.data = $(this).data('data');
	}
	$(this).ajaxSubmit(options);
	e.stopPropagation();
	return false;
});

// background link handling
$(document).on('click.infinite-api', '[data-handler="background"]', function (e) {
	var $this   = $(this), href;
	var $target = $this.attr('data-target') || false;
	var $dropdown = $this.parents('.dropdown-menu').first();
	if ($dropdown.length > 0) {
		$dropdownParent = $dropdown.parent();
		$dropdownParent.trigger(e = $.Event('hide.bs.dropdown'));
		if (!e.isDefaultPrevented()) {
			$dropdownParent.removeClass('open').trigger('hidden.bs.dropdown');
		}
	}

	var options = {
		'data': {},
		'type': 'GET',
		'dataType': 'json'
	};

	if ($this.hasClass('disabled')) {
		e.stopPropagation();
		return false;
	}
	options.url = $this.data('url') || $this.attr('href');
	if (!options.url || options.url === '' || options.url === '#') {
		return true;
	}

	$this.addClass('disabled');
	if ($this.data('data')) {
		options.data = $this.data('data');
	}

	if ($this.data('method')) {
		options.type = $this.data('method');
	}

	if ($this.data('handlerOptions')) {
		options = jQuery.extend(true, options, $this.data('handlerOptions'));
	}

	options.context = $this;

	var backgroundProcess = jQuery.ajax(options).always(function() {
		setTimeout(function() { $this.removeClass('disabled')}, 500);
	});

	$this.data('background-process', backgroundProcess);
	return false;
});