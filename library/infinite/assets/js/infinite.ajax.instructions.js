function InfiniteInstructionHandler (instructions, ajaxEvent, ajaxOptions) {

	if (typeof(instructions) !== 'object') {
		instructions = {};
	}
	this.instructions = instructions;

	if (typeof(ajaxOptions) !== 'object') {
		ajaxOptions = {};
	}
	this.ajaxOptions = ajaxOptions;

	if (typeof(ajaxEvent) !== 'object') {
		ajaxEvent = {};
	}
	this.ajaxEvent = ajaxEvent;

	this.task = false;
	if (instructions.task !== undefined) {
		this.task = instructions.task;
	}

	this.taskOptions = {};
	if (instructions.taskOptions !== undefined) {
		this.options = instructions.taskOptions;
	}

	this.content = false;
	if (instructions.content !== undefined) {
		this.content = instructions.content;
	}

	this.data = false;
	if (instructions.data !== undefined) {
		this.data = instructions.data;
	}
}

InfiniteInstructionHandler.prototype.staticPostTasks = ['status', 'trigger'];

InfiniteInstructionHandler.prototype.handle = function() {
	var self = this;
	if (self.task && jQuery.inArray(self.task, this.staticPostTasks) === -1) {
		self.runHandler(self.task);
	}
	jQuery.each(self.staticPostTasks, function(index, task) {
		self.runHandler(task);
	});
};

InfiniteInstructionHandler.prototype.runHandler = function(task) {
	var handlerName = 'handle' + task.capitalize();
	if (typeof(this[handlerName]) === 'function') {
		if (!this[handlerName]()) {
			$.debug(task + " task failed!");
		}
	} else {
		$.debug("Unknown task passed to the AJAX instruction handler: "+ task +" ("+typeof(this[handlerName])+")");
	}
};

InfiniteInstructionHandler.prototype.handleStatus = function() {
	return true;
};

InfiniteInstructionHandler.prototype.handleTrigger = function() {
	if (this.instructions.trigger !== undefined) {
		jQuery.each(this.instructions.trigger, function(index, event) {
			$(event[1]).trigger(event[0]);
		});
	}
	return true;
};

InfiniteInstructionHandler.prototype.handleRedirect = function() {
	if (this.instructions.redirect !== undefined) {
		window.location = this.instructions.redirect;
	}
	return true;
};

InfiniteInstructionHandler.prototype.handleRefresh = function() {
	location.reload();
	return true;
};

InfiniteInstructionHandler.prototype.handleDialog = function() {
	var self = this;
	$.debug("Handling dialog creation for AJAX request");
	if (!this.content) {
		$.debug("No content provided!");
		return false;
	}

	var options = {};
	var $modal = $("<div />", {'class': 'modal fade', 'role': 'dialog', 'tabindex': '-1'});
	var $dialog = $("<div />", {'class': 'modal-dialog'}).appendTo($modal);
	var $dialogContent = $("<div />", {'class': 'modal-content'}).appendTo($dialog);

	if (this.options.title !== undefined) {
		var title = $("<div />", {'class': 'modal-header'}).html(this.options.title).appendTo($dialogContent);
	}

	var $body = $("<div />", {'class': 'modal-body'}).html(this.content).appendTo($dialogContent);
	var $form = $body.find('form');
	
	if ($form.length > 0) {
		$modal.on('show.bs.modal', function() {
			$preparer.fire($body);
		});
		$modal.on('shown.bs.modal', function() {
			$body.find('*').trigger('visible');
			var $focus = $body.find('.has-error :focusable').first();
			if ($focus.length === 0) {
				$focus = $body.find(':focusable').first();
			}
			if ($focus.length > 0) {
				$focus.focus();
			}
		});
		$('input:not(.noEnterSubmit),select:not(.noEnterSubmit)', $form).keypress(function(e){
			if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
				$form.submit();
				return false;
			} else {
				return true;
			}
		});
	}

	if (this.options.buttons !== undefined) {
		var $footer = $("<div />", {'class': 'modal-footer'}).appendTo($dialogContent);
		jQuery.each(this.options.buttons, function(label, settings) {
			var $button = $("<button />", {'class': 'btn'}).html(label).appendTo($footer);
			if (settings.role === undefined) {
				settings.role = 'close';
			}
			if (settings.state === undefined) {
				settings.state = 'default';
			}
			if (settings.role === 'submit' && $form.length > 0) {
				if (settings.state === 'default') {
					settings.state = 'primary';
				}
				$form.submit(function() {
					$modal.modal('hide');
				});
				$button.click(function() {
					$form.submit();
				});
				$button.attr('data-dismiss', 'modal');
			} else if (settings.role === 'reset' && $form.length > 0) {
				$button.click(function() {
					$form.reset();
				});
			} else {
				$button.attr('data-dismiss', 'modal');
			}
			$button.addClass('btn-' + settings.state);
		});
	}

	$modal.appendTo($('body'));
	$modal.modal(options);
	$modal.on('hidden.bs.modal', function() {
		$(this).remove();
	});
	return true;
};

InfiniteInstructionHandler.prototype.getTarget = function() {
};