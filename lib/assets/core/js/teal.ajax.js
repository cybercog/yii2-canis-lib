$(document).data('ajax.stack', []);

jQuery.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
	if (options.type === 'POST' && $('body').data('post')) {
		if (options.data === undefined) {
			options.data = {};
		}
		options.data = $.param($.extend(originalOptions.data, $('body').data('post')));
	}
});

$(document).ajaxComplete(function(e, xhr, ajaxOptions) {
	/* What should be called at the end of EVERY SINGLE request, regardless of result? */
	if (ajaxOptions.notice !== undefined) {
		console.log("Handle notice!");
	}
});

$(document).ajaxError(function(e, xhr, ajaxOptions) {
	/* Parse error instructions, if any */
	if (xhr.responseJSON !== undefined) {
		var responseJSON = xhr.responseJSON;
	} else {
		var responseJSON = {'error': 'An unknown error has occurred.', 'object': xhr};
	}

	var instructor = new TealInstructionHandler(responseJSON, e, ajaxOptions);
	instructor.handle();
});

$(document).ajaxSuccess(function(e, xhr, ajaxOptions) {
	/* Parse success instructions, if any */
	if (xhr.responseJSON !== undefined) {
		var instructor = new TealInstructionHandler(xhr.responseJSON, e, ajaxOptions);
		instructor.handle();
	}
});