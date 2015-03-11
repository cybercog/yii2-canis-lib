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
$.fn.tealNavBarHeight = function() {
	var navOuterHeight = 0;
	$('nav.navbar-fixed-top').each(function() {
		navOuterHeight += $(this).outerHeight();
	});
	navOuterHeight += 10;
	return navOuterHeight;
};
$.fn.tealAffix = function (options) {
	var $self = $(this);
	var $parent = $(this).parent().first();
	$(this).addClass('teal-affix');
	$self.width($parent.innerWidth()-parseInt($parent.css('padding-left'), 10)-parseInt($parent.css('padding-right'), 10));
	$(window).on('resizeDone', function() {
		$self.width($parent.innerWidth()-parseInt($parent.css('padding-left'), 10)-parseInt($parent.css('padding-right'), 10));
	});
	if (options === undefined) {
		options = {};
	}
	var navOuterHeight = $(this).tealNavBarHeight();
	var calculateBottom = function() {
		this.bottom = $('.footer').outerHeight(true);
		return this.bottom;
	};
	var calculateTop = function () {
		var offsetTop = $self.offset().top;
		var margin = parseInt($self.css('margin-top'), 10);
		this.top = offsetTop - navOuterHeight - margin;
		return this.top;
	};
	if (options.offset === undefined) {
		options.offset = {};
	}
	if (options.offset.top === undefined) {
		options.offset.top = calculateTop;
	}
	if (options.offset.bottom === undefined) {
		options.offset.bottom = calculateBottom;
	}
	setTimeout(function() {
		$self.affix(options);
	}, 200);
	
	$self.on('affixed.bs.affix', function() {
		$(this).css('top', navOuterHeight);
	});
	$self.on('affixed-top.bs.affix', function() {
		$(this).css('top', null);
	});
	$self.on('affixed-bottom.bs.affix', function() {
	});
};

/**
  * Check an href for an anchor. If exists, and in document, scroll to it.
  * If href argument omitted, assumes context (this) is HTML Element,
  * which will be the case when invoked by jQuery after an event
  */
function tealAnchorScrollFix(event, href) {
    href = typeof(href) == "string" ? href : $(this).attr("href");
    if(!href) return;
    var fromTop = $(this).tealNavBarHeight() + 10;
    var $target = $(href);
    if($target.length) {
        $('html, body').animate({ scrollTop: $target.offset().top - fromTop });
        if(history && "pushState" in history) {
            // history.pushState({}, document.title, window.location.pathname + href);
        }
        if (event) {
        	event.preventDefault();
        	console.log(['fixScroll', event]);
        	return true;
        }
        return false;
    }
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
$(document).on('submit.teal-api', 'form.ajax', function(e) {
	var options = {};
	if ($(this).data('data')) {
		options.data = $(this).data('data');
	}
	$(this).ajaxSubmit(options);
	e.stopPropagation();
	return false;
});

// background link handling
$(document).on('click.teal-api', '[data-handler="background"]', function (e) {
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