function InfiniteLog($canvas, initial) {
	var self = this;
	$canvas.addClass('infinite-log');
	this.$canvas = $canvas;
	this.data = initial;
	this.elements = {};
	this.renderedMessages = {};
	this.renderCanvas();
	this.updateData();
}

InfiniteLog.prototype.updateData = function() {
	var self = this;
	if (this.data['_'].ended === null || !this.data['_'].ended) {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 5000);
	}
	if (this.data.messages) {
		this.updateMessages();
	}
	if (this.elements.bars === undefined) {
		this.elements.bars = {};
	}
	if (this.data.progress) {
		if (this.elements.bars._overall === undefined) {
			this.elements.bars._overall = {};
			this.elements.bars._overall.$canvas = $("<div />", {'class': 'list-group-item'}).appendTo(this.elements.$topProgressList);
			this.elements.bars._overall.$title = $("<div />", {'class': 'list-group-item-heading'}).html('<strong>Overall Progress</strong>').appendTo(this.elements.bars._overall.$canvas);
			this.elements.bars._overall.$wrapper = $("<div />", {'class': 'progress'}).appendTo(this.elements.bars._overall.$canvas);
			this.elements.bars._overall.$progress = $("<div />", {'class': 'infinite-bar infinite-bar-striped active', 'role': 'progressbar', 'aria-valuenow': 0, 'aria-valuemin': 0, 'aria-valuemax': 100}).appendTo(this.elements.bars._overall.$wrapper);
		}
		this.elements.bars._overall.percentage = Math.round((this.data.progress.done / this.data.progress.total)*100, 1);
		this.elements.bars._overall.$progress.html(this.elements.bars._overall.percentage +"%").attr('aria-valuenow', this.elements.bars._overall.percentage).css({'width': this.elements.bars._overall.percentage+'%'});
		if (this.data['_'].ended !== null) {
			this.elements.bars._overall.$progress.removeClass('active');
		}
	}
	this.drawInfo(this.data['_']);

	if (this.data.tasks) {
		this.drawTasks(self.elements.$progressList, this.data.tasks)
	}

	if (this.data.output) {
		if (jQuery.isArray(this.data.output)) {
			this.elements.$output.$body.html(this.data.output.join("\n"));
		} else {
			this.elements.$output.$body.html(this.data.output);
		}
		this.elements.$output.show();
	}
};

InfiniteLog.prototype.drawInfo = function (info) {
	var self = this;
	var detailConfig = {
		'started': {
			'icon': 'fa fa-play',
			'label': 'Date Started'
		},
		'ended': {
			'icon': 'fa fa-stop',
			'label': 'Date Ended'
		},
		'duration': {
			'icon': 'fa fa-clock-o',
			'label': 'Duration'
		},
		'estimatedTimeRemaining': {
			'icon': 'fa fa-spinner fa-spin',
			'label': 'Estimated Time Remaining',
			'value': function(values, $detail, $detailValue) {
				var value = values['estimatedTimeRemaining'];
				$detailValue.html(value);
				if (values['status'] !== 'running') {
					$detail.hide();
				} else {
					$detail.show();
				}
			}
		},
		'peak_memory': {
			'icon': 'fa fa-tachometer',
			'label': 'Peak Memory'
		},
		'status': {
			'icon': 'fa fa-cogs',
			'label': 'Status',
			'value': function(values, $detail, $detailValue) {
				var value = values['status'];
				$detail.show();
				$detail.removeClass('list-group-item-success list-group-item-info list-group-item-warning list-group-item-danger');
				switch (value) {
					case 'success':
						$detail.addClass('list-group-item-success');
						$detailValue.html('Success');
					break;
					case 'loaded':
						$detail.addClass('list-group-item-success');
						$detailValue.html('Loaded');
					break;
					case 'running':
						$detail.addClass('list-group-item-info');
						$detailValue.html('Running');
					break;
					case 'error':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Error');
					break;
					case 'interrupted':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Error (Interrupted)');
					break;
					default:
						$detail.hide();
					break;
				}
			}
		},
		'log_status': {
			'icon': 'fa fa-cog',
			'label': 'Log Status',
			'value': function(values, $detail, $detailValue) {
				var value = values['log_status'];
				$detail.show();
				$detail.removeClass('list-group-item-success list-group-item-info list-group-item-warning list-group-item-danger');
				switch (value) {
					case 'fine':
						$detail.addClass('list-group-item-success');
						$detailValue.html('No Errors or Warnings');
					break;
					case 'warning':
						$detail.addClass('list-group-item-warning');
						$detailValue.html('Contains Warnings');
					break;
					case 'error':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Contains Error');
					break;
					default:
						$detail.hide();
					break;
				}
			}
		},
		'menu': {
			'icon': 'fa fa-cogs',
			'label': 'Status',
			'value': function(values, $detail, $detailValue) {
				$detail.html('');
				var value = values['menu'];
				if (value.length === 0) {
					$detail.hide();
					return;
				}
				$detail.show();
				var $menuList = $("<div />", {'class': 'btn-group'}).appendTo($detail);
				jQuery.each(value, function(index, item) {
					var $item = $("<a />", {'href': item.url}).html(item.label).addClass('btn').appendTo($menuList);
					if (item.attributes !== undefined) {
						$item.attr(item.attributes);
					}
					if (item.class === undefined) {
						$item.addClass('btn-default');
					} else {
						$item.addClass(item.class);
					}
				});
			}
		},
	}
	if (this.elements.$detailsList === undefined) {
		self.elements.$detailsList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$details.$body);
		self.elements.details = {};
		self.elements.detailValues = {};
		jQuery.each(detailConfig, function(key, config) {
			self.elements.details[key] = $("<div />", {'class': 'list-group-item row', 'title': config.label}).appendTo(self.elements.$detailsList);
			self.elements.details[key].append($("<div />").addClass('col-xs-1').append($("<div />", {'class': config.icon + ' detail-icon'})));
			self.elements.detailValues[key] = $("<div />", {'class': 'detail-value col-xs-11'}).appendTo(self.elements.details[key]);
		});
	}
	
	jQuery.each(detailConfig, function(key, config) {
		if (info[key] === undefined || !info[key]) {
			self.elements.details[key].hide();
		} else {
			self.elements.details[key].show();
			if (config.value !== undefined) {
				config.value(info, self.elements.details[key], self.elements.detailValues[key])
			} else {
				self.elements.detailValues[key].html(info[key]);
			}
		}
	});

}

InfiniteLog.prototype.drawTasks = function ($wrapperCanvas, tasks, parentTask) {
	var self = this;
	if (self.elements.bars === undefined) {
		self.elements.bars = {};
	}
	jQuery.each(tasks, function(id, task) {
		if (parentTask !== undefined) {
			id = parentTask +'.'+ id;
		}
		if (self.elements.bars[id] === undefined) {
			self.elements.bars[id] = {};
			self.elements.bars[id].$canvas = $("<div />", {'class': 'list-group-item'}).appendTo($wrapperCanvas);
			self.elements.bars[id].$title = $("<div />", {'class': 'list-group-item-heading'}).html(task.name).appendTo(self.elements.bars[id].$canvas);
			self.elements.bars[id].$estimate = $("<span />", {'class': 'label label-primary'}).html('').hide().appendTo(self.elements.bars[id].$title);
			self.elements.bars[id].$wrapper = $("<div />", {'class': 'progress'}).appendTo(self.elements.bars[id].$canvas);
			self.elements.bars[id].$progress = $("<div />", {'class': 'infinite-bar infinite-bar-striped infinite-bar-info active', 'role': 'progressbar', 'aria-valuenow': 0, 'aria-valuemin': 0, 'aria-valuemax': 100}).appendTo(self.elements.bars[id].$wrapper);
			self.elements.bars[id].$subtaskCanvas = $("<div />", {'class': 'well expanded-only'}).appendTo(self.elements.bars[id].$canvas);
		}
		if (task.estimate) {
			self.elements.bars[id].$estimate.show().html('about '+ task.estimate + ' remaining');
		} else {
			self.elements.bars[id].$estimate.hide();
		}
		if (task.subtasks !== undefined) {
			if (!self.elements.bars[id].$canvas.hasClass('expandable')) {
				self.elements.bars[id].$canvas.addClass('expandable');
				self.elements.bars[id].$canvas.checkExpandable();
			}
			self.elements.bars[id].$subtaskCanvas.addClass('expanded-only').removeClass('hidden');
			self.drawTasks(self.elements.bars[id].$subtaskCanvas, task.subtasks, id);
		} else {
			self.elements.bars[id].$subtaskCanvas.removeClass('expanded-only').addClass('hidden');
		}
		self.elements.bars[id].percentage = Math.round((task.done / task.total)*100, 1);
		self.elements.bars[id].$progress.html(self.elements.bars[id].percentage +"%").attr('aria-valuenow', self.elements.bars[id].percentage).css({'width': self.elements.bars[id].percentage+'%'});
		if (task.duration !== undefined && self.elements.bars[id].$progress.hasClass('active')) {
			self.elements.bars[id].$progress.removeClass('active');
			self.elements.bars[id].$title.append($("<span />", {'class': 'badge'}).html(task.duration));
		}
	});
};

InfiniteLog.prototype.updateMessages = function() {
	var self = this;
	if (this.data.messages) {
		this.elements.$messages.show();
		jQuery.each(this.data.messages, function(index, message) {
			if (self.renderedMessages[index] === undefined) {
				self.renderedMessages[index] = $("<div />", {'class': 'expandable list-group-item'}).prependTo(self.elements.$messageList);
				switch (message.level) {
					case '_e':
						self.renderedMessages[index].addClass('list-group-item-danger');
					break;
					case '_w':
						self.renderedMessages[index].addClass('list-group-item-warning');
					break;
					default:
						self.renderedMessages[index].addClass('list-group-item-info');
					break;
				}
				self.renderedMessages[index].html(message.message);
				if (message.data !== null) {
					$("<code />").addClass('expanded-only infinite-log-preformatted').html(JSON.stringify(message.data, null, "\t")).appendTo(self.renderedMessages[index]);
				}
				var timeBadge = $('<span />', {'class': 'badge pull-right'}).html("+"+ message.fromStart);
				timeBadge.attr('title', 'Duration: ' + message.duration +'; Memory: '+ message.memory).prependTo(self.renderedMessages[index]);
			}
		});
	} else {
		this.elements.$messages.hide();
	}
};

InfiniteLog.prototype.renderCanvas = function() {
	this.elements.$grid = $("<div />", {'class': 'row'}).appendTo(this.$canvas);
	this.elements.$leftContainer = $("<div />", {'class': 'col-sm-6'}).appendTo(this.elements.$grid);
	this.elements.$left = $("<div />", {'class': 'infinite-sidebar'}).appendTo(this.elements.$leftContainer);
	//this.elements.$left.data('offset-top', 10).progressAffix();
	this.elements.$right = $("<div />", {'class': 'col-sm-6'}).appendTo(this.elements.$grid);

	this.elements.$details = $("<div />", {'class': 'panel panel-default'}).appendTo(this.elements.$left);
	this.elements.$progress = $("<div />", {'class': 'panel panel-default'}).appendTo(this.elements.$right);
	this.elements.$topProgress = $("<div />", {'class': 'panel panel-default'}).prependTo(this.$canvas);
	this.elements.$messages = $("<div />", {'class': 'panel panel-default'}).appendTo(this.elements.$left);
	this.elements.$output = $("<div />", {'class': 'panel panel-default infinite-log-output'}).hide().appendTo(this.elements.$left);

	this.elements.$details.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$details);
	$("<div />", {'class': 'panel-title'}).html('Details').appendTo(this.elements.$details.$title);
	this.elements.$details.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$details);

	//this.elements.$topProgress.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$progress);
	//$("<div />", {'class': 'panel-title'}).html('Progress').appendTo(this.elements.$topProgress.$title);
	//this.elements.$topProgress.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$topProgress);
	this.elements.$topProgressList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$topProgress);

	this.elements.$progress.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$progress);
	$("<div />", {'class': 'panel-title'}).html('Task Progress').appendTo(this.elements.$progress.$title);
	this.elements.$progress.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$progress);
	this.elements.$progressList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$progress.$body);

	this.elements.$messages.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$messages);
	$("<div />", {'class': 'panel-title'}).html('Messages').appendTo(this.elements.$messages.$title);
	this.elements.$messages.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$messages);
	this.elements.$messageList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$messages.$body);

	this.elements.$output.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$output);
	$("<div />", {'class': 'panel-title'}).html('Output').appendTo(this.elements.$output.$title);
	this.elements.$output.$body = $("<div />", {'class': 'panel-body infinite-log-preformatted'}).appendTo(this.elements.$output);


};

InfiniteLog.prototype.refresh = function() {
	var self = this;
	var ajax = {};
	ajax['url'] = this.data['_'].url;
	ajax['success'] = function(result) {
		if (result['_'] === undefined) { return; }
		self.data = result;
		self.updateData();
	}
	jQuery.ajax(ajax);
};

$("[data-log]").each(function() {
	var initial = $(this).data('log');
	$(this).removeAttr('data-log');
	$(this).data('log', new InfiniteLog($(this), initial));
});