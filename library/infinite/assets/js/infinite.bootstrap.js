// ajax form
$(document).on('submit.infinite-api', 'form.ajax', function(e) {
	$(this).ajaxSubmit();
	e.stopPropagation();
	return false;
});

// background link handling
$(document).on('click.infinite-api', '[data-handler="background"]', function (e) {
	var $this   = $(this), href;
	var $target = $this.attr('data-target') || false;
    
	var options = {
		'data': {},
		'type': 'GET',
		'dataType': 'json'
	};
	if ($this.hasClass('disabled')) {
		e.stopPropagation();
		return false;
	}
	$this.addClass('disabled');
	options.url = $this.data('url') || $this.attr('href');
	if (!options.url || options.url === '' || options.url === '#') {
		return true;
	}

	if ($this.data('data')) {
		options.data = $this.data('data');
	}

	if ($this.data('method')) {
		options.type = $this.data('method');
	}

	if ($this.data('handlerOptions')) {
		options = jQuery.extend(true, options, $this.data('handlerOptions'));
	}

	var backgroundProcess = jQuery.ajax(options).always(function() {
		setTimeout(function() { $this.removeClass('disabled')}, 500);
	});

	$this.data('background-process', backgroundProcess);
	return false;
});