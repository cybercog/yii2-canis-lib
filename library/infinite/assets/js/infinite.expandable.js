function hasHiddenElements($e) {
	var checker = false;
	$e.children().each(function() {
		if( $(this)[0].scrollWidth > $(this).width() ){
			checker = true;
			return false;
		}
	});
	return checker;
}

$preparer.add(function(context) {
	$(".expandable").each(function() {
		if ($(this).find('.expanded-only').length === 0 && !hasHiddenElements($(this))) { 
			$(this).removeClass('expandable');
			return true;
		}

		$(this).click(function(e) {
			if ($(e.target).is('a') || $(e.target).is('button')) {
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
	});
});