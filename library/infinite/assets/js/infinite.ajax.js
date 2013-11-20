$(document).data('ajax.stack', []);

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

	var instructor = new InfiniteInstructionHandler(responseJSON, e, ajaxOptions);
	instructor.handle();
});

$(document).ajaxSuccess(function(e, xhr, ajaxOptions) {
	/* Parse success instructions, if any */
	if (xhr.responseJSON !== undefined) {
		var instructor = new InfiniteInstructionHandler(xhr.responseJSON, e, ajaxOptions);
		instructor.handle();
	}
});