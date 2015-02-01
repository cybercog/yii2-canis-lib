var $preparer = $.Callbacks("memory");
$(function() {
	$preparer.fire(document);
});


$(window).resize(function () {
    waitForFinalEvent(function(){
    	$(this).trigger("resizeDone");
    }, 250, "window-resize-event");
});

$preparer.add(function(context) {
	$("form", context).submit(function(event) {
		var e = jQuery.Event( "beforeSubmit" );
		$(this).trigger(e);
		if (e.isPropagationStopped()) {
			event.stopPropagation();
			return false;
		} else {
			return true;
		}
	});

	$("[data-height]", context).each(function() {
		var $this = $(this);
		var defaultSettings = {'min': false, 'max': false};
		var settings = jQuery.extend({}, defaultSettings, $this.data('height'));
		console.log(settings);
		jQuery.each(['max', 'min'], function(index, item) {
			var cssProperty = item + "-height";

			if (settings[item] === 'item') {
				settings[item] = $this.outerHeight();
			} else if (settings[item] === 'body') {
				settings[item] = $('body').height();
			}
			if (settings[item]) {
				$this.addClass('height-controlled');
				$this.css(cssProperty, settings[item]);
			}
		});

	});
	$("[data-parent-height-watch]", context).each(function() {
		var $this = $(this);
		var offset = 0;
		if ($this.data('parent-height-watch-offset')) {
			offset = parseInt($this.data('parent-height-watch-offset'), 10);
		}
		if ($this.data('parent-height-watch-ancestor')) {
			var $parent = $(this).parents($this.data('parent-height-watch-ancestor')).first();
		} else {
			var $parent = $this.parent();
		}
		$parent.css({display: 'block'});
		console.log($parent);
		var fixParent = function() {
			if ($parent.innerHeight() < $this.outerHeight()) {
				var newHeight = parseInt($this.outerHeight(), 10) + offset;
				$parent.height(newHeight);
			}
		};
		$(window).bind("resizeDone", fixParent);
		fixParent();
	});
	$("[data-width-match]", context).each(function() {
		if ($(this).hasClass('width-matched')) {
			return true;
		}
		var widths = [];
		$($(this).data('width-match')).each(function () {
			widths.push($(this).width());
		});
		$($(this).data('width-match')).width(Math.max(widths)).addClass('width-matched');
	});
});
