function SmartLine($element, options) {
	this.$element = $element;
	this.options = options;
	this.limitables = this.$element.find(options.selector);
}

SmartLine.prototype.getLimitables = function() {
	var self = this;
	var limitables = [];
	jQuery.each(this.limitables, function(index, limitable) {
		if ($(limitable).html().length > self.options.minSize) {
			limitables.push(limitable);
		}
	});
	return limitables;
};

SmartLine.prototype.getLargestLimitable = function() {
	var largest = false;
	var largestSize = 0;
	jQuery.each(this.getLimitables(), function(index, limitable) {
		var currentLimitable = $(limitable).find('.limitable-current');
		if (currentLimitable.length > 0) {
			if ($(currentLimitable).html().length > largestSize) {
				largest = limitable;
				largestSize = $(limitable).html().length;
			}
		} else {
			if ($(limitable).html().length > largestSize) {
				largest = limitable;
				largestSize = $(limitable).html().length;
			}
		}
	});
	return largest;
};

SmartLine.prototype.updateLine = function() {
	var limitables = this.getLimitables();
	var loopsLeft = this.$element.html().length; 
	while (loopsLeft > 0 && limitables.length > 0 && this.$element.overflown()) {
		loopsLeft--;
		var largest = this.getLargestLimitable();
		if (!largest) { break; }
		if (!$(largest).data('limitable-original')) {
			$(largest).data('limitable-original', $(largest).html());
			$(largest).data('limitable-current-cut', $(largest).html().length - 3);
			$(largest).data('limitable-current-cut-step', Math.min(10, Math.ceil($(largest).html().length * this.options.cutPercent)));
			$(largest).attr('title', $(largest).html());
		}
		var original = $(largest).data('limitable-original');
		var cut = Math.max(
					this.options.minSize, 
					$(largest).data('limitable-current-cut') - $(largest).data('limitable-current-cut-step')
				);

		$(largest).data('limitable-current-cut', cut);
		$(largest).html('');
		$("<span />", {'class': 'limitable-current'}).html(original.substr(0, cut)).appendTo($(largest));
		$("<span />", {'class': 'not-expanded-only'}).html("&hellip;").appendTo($(largest));
		$("<span />", {'class': 'expanded-only-inline'}).html(original.substr(cut)).appendTo($(largest));

		// prep loop
		var limitables = this.getLimitables();
	}
	this.$element.parent('li').first().checkExpandable();
};

$preparer.add(function(context) {
	$(".smart-line, [data-smart-line]", context).each(function() {
		var self = this;
		var defaultOptions = {
			'selector': '.limitable',
			'minSize': 10,
			'cutPercent': .05
		};
		var options = jQuery.extend(true, {}, defaultOptions, $(this).data('smart-line') || {});
		var smartLine = new SmartLine($(this), options);
		$(this).on('updateLine', function() {
			smartLine.updateLine();
		});
		setTimeout(function() {
			$(self).trigger('updateLine');
		}, 800);
	});
});

$(window).on("resizeDone", function() {
	$(".smart-line, [data-smart-line]").trigger('updateLine');
});