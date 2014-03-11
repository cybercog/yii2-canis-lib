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
	$(this).ajaxSubmit();
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