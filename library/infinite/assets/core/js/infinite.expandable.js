function hasHiddenElements($e) {
	var checker = false;
	$e.children().each(function() {
		if( $(this)[0].scrollWidth > $(this).width() ){
			checker = true;
			return false;
		}
	});
	if (!checker) {
		$e.find('.expandable-child').each(function() {
			if( $(this)[0].scrollWidth > $(this).width() ){
				checker = true;
				return false;
			}
		});
	}
	return checker;
}

jQuery.fn.checkExpandable = function() {
	if ($(this).find('.expanded-only, .expanded-only-inline').length === 0 && !hasHiddenElements($(this))) { 
		$(this).removeClass('expandable');
		return true;
	}
	if ($(this).hasClass('expanded-checked')) {
		return true;
	}
	$(this).addClass('expanded-checked');
	$(this).click(function(e) {
		if ($(e.target).is('a') || $(e.target).is('input') || $(e.target).is('button')) {
			return;
		}
		if ($(this).is(':animated')) {
			return false;
		}
		if ($(this).data('expanded')) {
			$(this).data('expanded', false);
			$(this).removeClass('expanded');
		} else {
			$(this).data('expanded', true);
			$(this).addClass('expanded');

		}
	});
	return $(this);
}

$preparer.add(function(context) {
	$(".expandable:not(.expandable-delayed)", context).each(function() {
		$(this).checkExpandable();
	});
});