function CanisInstructionHandler (instructions, ajaxEvent, ajaxOptions) {
	if (typeof(instructions) !== 'object') {
		instructions = {};
	}
	this.instructions = instructions;

	if (typeof(ajaxOptions) !== 'object') {
		ajaxOptions = {};
	}
	this.ajaxOptions = ajaxOptions;

	if (ajaxOptions.context === undefined || typeof(ajaxOptions.context) !== 'object') {
		ajaxOptions.context = {};
	}
	this.context = ajaxOptions.context;

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

	if (instructions.pauseTimer !== undefined) {
		if (instructions.pauseTimer) {
			timer.pause();
		} else {
			timer.resume();
		}
	}
}

CanisInstructionHandler.prototype.staticPostTasks = ['status', 'trigger'];

CanisInstructionHandler.prototype.handle = function() {
	var self = this;
	if (self.task && jQuery.inArray(self.task, this.staticPostTasks) === -1) {
		self.runHandler(self.task);
	}
	if (self.instructions.taskSet) {
		jQuery.each(self.instructions.taskSet, function(index, task) {
			var instructor = new CanisInstructionHandler(task, self.ajaxEvent, self.ajaxOptions);
			instructor.handle();
		});
	}
	jQuery.each(self.staticPostTasks, function(index, task) {
		self.runHandler(task);
	});
};

CanisInstructionHandler.prototype.runHandler = function(task) {
	var handlerName = 'handle' + task.capitalize();
	if (typeof(this[handlerName]) === 'function') {
		if (!this[handlerName]()) {
			$.debug(task + " task failed!");
		}
	} else {
		$.debug("Unknown task passed to the AJAX instruction handler: "+ task +" ("+typeof(this[handlerName])+")");
	}
};

CanisInstructionHandler.prototype.handleStatus = function() {
	return true;
};

CanisInstructionHandler.prototype.handleTrigger = function() {
	var self = this;
	if (this.instructions.trigger !== undefined) {
		jQuery.each(this.instructions.trigger, function(index, event) {
			if (event[1] !== undefined) {
				var $target = $(event[1]);
			} else if (self.context) {
				var $target = self.context;
			} else {
				return true;
			}
			$target.trigger(event[0]);
		});
	}
	return true;
};


CanisInstructionHandler.prototype.handleRemoveElement = function() {
	var self = this;
	if (this.instructions.selector !== undefined) {
		$(this.instructions.selector).remove();
	}
	return true;
};
CanisInstructionHandler.prototype.handleReplace = function() {
	var self = this;
	if (this.instructions.selector !== undefined && this.instructions.content !== undefined) {
		var $result = $(this.instructions.selector).replaceWith(this.instructions.content);
		$preparer.fire($result);
	}
	return true;
};

CanisInstructionHandler.prototype.handleRedirect = function() {
	if (this.instructions.redirect !== undefined) {
		window.location = this.instructions.redirect;
	}
	return true;
};

CanisInstructionHandler.prototype.handleRefresh = function() {
	location.reload();
	return true;
};

CanisInstructionHandler.prototype.handleMessage = function() {
	this.options.title = this.options.title || 'Notice';
	this.options.state = this.options.state || false;
	this.options.buttons = {
		'Close': {
			'state': this.options.buttonState || this.options.state || 'default'
		}
	};

	return this.handleDialog();
}

CanisInstructionHandler.prototype.handleDialog = function() {
	var self = this;
	if (!this.content) {
		$.debug("No content provided!");
		return false;
	}

	var options = {};
	var $modal = $("<div />", {'class': 'modal fade', 'role': 'dialog', 'tabindex': '-1'});
	var $dialog = $("<div />", {'class': 'modal-dialog'}).appendTo($modal);
	if (this.options.state === undefined) {
		this.options.state = 'default';
	}
	$dialog.addClass('modal-' + this.options.state);
	if (this.options.modalClass !== undefined) {
		$dialog.addClass(this.options.modalClass);
	}
	var $dialogContent = $("<div />", {'class': 'modal-content'}).appendTo($dialog);

	if (this.options.title !== undefined) {
		var titleTag = $("<h4 />", {'class': 'modal-title'}).html(this.options.title);
		var title = $("<div />", {'class': 'modal-header'}).append(titleTag).appendTo($dialogContent);
	}

	var $body = $("<div />", {'class': 'modal-body'}).html(this.content).appendTo($dialogContent);
	var $form = $body.find('form');
	$body.prepared = false;
	
	$modal.on('show.bs.modal', function() {
		if (!$body.prepared) {
			$preparer.fire($body);
			$body.prepared = true;
		}
	});
	if ($form.length > 0) {
		$modal.on('shown.bs.modal', function() {
			$body.find('*').trigger('visible');
			var $focus = $body.find('.has-error :focusable').first();
			if ($focus.length === 0) {
				$focus = $body.find(':focusable:not(.disabled):not(.ignore-focus)').first();
			}
			if ($focus.length > 0) {
				$focus.focus();
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

CanisInstructionHandler.prototype.getTarget = function() {
};