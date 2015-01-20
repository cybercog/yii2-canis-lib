function InfiniteCallstack(parent, eventName) {
	this.parent = parent;
	this.eventName = eventName;
	this.callstack = [];
}
InfiniteCallstack.prototype.fire = function () {
    var args = [];
    Array.prototype.push.apply( args, arguments );
    var self = this;
    //console.log("fire: "+ this.eventName +"; n: "+ this.callstack.length);
    //console.log(this.parent);
	// jQuery.each(this.callstack, function(i, callback) {
	// 	console.log(callback.toString());
	// });
	// return;

	jQuery.each(this.callstack, function(i, callback) {
		//console.log([self, args]);
		callback.apply(self.parent, args);
	});
};
InfiniteCallstack.prototype.add = function (callback) {
	this.callstack.push(callback);
};


function InfiniteEvent() {
	this.calls = [];
	this.uniqueId = Math.random().toString(36).substr(2, 10);
	this.isValid = true;
}

function InfiniteComponent() {
	this.isInitializing = true;
	this.callbacks = {};
	this.uniqueId = false;
}

var triggerDepth = 0;

InfiniteComponent.prototype.isInitializing = true;
InfiniteComponent.prototype.isLoading = false;

InfiniteComponent.prototype.getUniqueId = function () {
	if (!this.uniqueId) {
		this.uniqueId = Math.random().toString(36).substr(2, 10);
	}
	return this.uniqueId;
};

InfiniteComponent.prototype.setUniqueId = function (uniqueId) {
	if (this.uniqueId && uniqueId !== this.uniqueId) {
	//	console.log("Warning: Changed UniqueID from "+ this.uniqueId +" to "+ uniqueId);
	}
	this.uniqueId = uniqueId;
};
InfiniteComponent.prototype.objectClass = 'Unknown';

InfiniteComponent.prototype.trigger = function(eventName, eventObject) {
	var self = this;
	if (eventObject === undefined) {
		eventObject = new InfiniteEvent();
	} else {
		eventObject = jQuery.extend(true, {}, eventObject);
	}
	eventObject.calls.push(self.objectClass +":::"+ eventName +":::"+ Math.random().toString(36).substr(2, 10));
	jQuery.each(eventName.split(" "), function(index, name) {
		self.getCallback(name).fire(self, eventObject);
	});
}

InfiniteComponent.prototype.on = function(eventName, callback) {
	var self = this;
	jQuery.each(eventName.split(" "), function(index, name) {
		self.getCallback(name).add(callback);
	});
}

InfiniteComponent.prototype.loadData = function(data) {
	if (data === undefined) { return; }
	var self = this;
	this.isLoading = true;
	jQuery.each(data, function (key, value) {
		self.set(key, value);
	});
	this.isLoading = false;
};

InfiniteComponent.prototype.getCallback = function(eventName) {
	if (this.callbacks[eventName] === undefined) {
		this.callbacks[eventName] = new InfiniteCallstack(this, eventName);
	}
	return this.callbacks[eventName];
}

InfiniteComponent.prototype.has = function(name) {
	var functionName = 'has' + name.charAt(0).toUpperCase() + name.substr(1);
	if (this[functionName] !== undefined) {
		return this[functionName]();
	} else {
		return this[name] !== undefined && this[name] !== null;
	}
}

InfiniteComponent.prototype.get = function(name, defaultValue) {
	if (name === undefined){ 
		console.trace();
	}
	if (defaultValue === undefined) {
		defaultValue = null;
	}
	var functionName = 'get' + name.charAt(0).toUpperCase() + name.substr(1);
	if (this[functionName] !== undefined) {
		return this[functionName](defaultValue);
	} else if (this[name] !== undefined) {
		return this[name];
	}
	return defaultValue;
}
InfiniteComponent.prototype.set = function(name, value) {
	var triggerChange = false;
	var upperCamel = name.charAt(0).toUpperCase() + name.substr(1);
	var functionName = 'set' + upperCamel;
	var eventName = 'change' + upperCamel;
	//console.log(functionName);
	if (this[functionName] !== undefined) {
		triggerChange = this[functionName](value);
	} else if (this[name] !== undefined && this[name] instanceof InfiniteComponent) {
		this[name].loadData(value);
		triggerChange = false;
	} else if (this[name] !== undefined) {
		if (!_.isEqual(this[name], value)) {
			triggerChange = true;
			this[name] = value;
		}
	} else {
		this[name] = value;
		triggerChange = true;
	}
	if (triggerChange && !this.isInitializing && !this.isLoading) {
		this.trigger('change ' + eventName);
	}
}

InfiniteComponent.prototype.generatePanel = function($parent, title, state) {
	if (title === undefined) {
		title = false;
	}
	if (state === undefined) {
		state = 'default';
	}
	var view = {};
	view.$canvas = $("<div />", {'class': 'panel panel-'+state}).appendTo($parent);
	if (title) {
		if (typeof title === 'string') {
			title = {'label': title};
		}
		if (title.menu === undefined) {
			title.menu = false;
		}
		if (title.level === undefined) {
			title.level = 1;
		}
		title.level  = parseInt(title.level , 10);
		title.level  = title.level  + 2;
		view.$header = $("<div />", {'class': 'panel-heading'}).appendTo(view.$canvas);
		view.$title = $("<h"+title.level+" />", {'class': 'panel-title'}).html(title.label).appendTo(view.$header);
		if (title.menu) {
			var $btnGroup = this.generateButtonGroup(title.menu).appendTo(view.$title).addClass('pull-right');
		}
	}
	view.$body = $("<div />", {'class': 'panel-body'}).appendTo(view.$canvas);
	return view;
};

InfiniteComponent.prototype.generateButtonGroup = function(buttons, options) {
	var self = this;
	if (options === undefined) {
		options = {
			'replace': {
			}
		};
	}
	var defaultButtonConfig = {
		'field': 'button',
		'onClick': false,
		'icon': false,
		'label': false,
		'url': false,
		'state': 'primary',
		'options': {}
	};
	var size = options.size || 'sm';
	delete options.size;
	var $btnGroup = $("<div />", options).addClass('btn-group btn-group-'+size);
	jQuery.each(buttons, function(index, button) {
		button = jQuery.extend(true, {}, defaultButtonConfig, button);
		if (button.url) {
			button.field = 'a';
			button.options.href = decodeURIComponent(button.url).template(options.replace);
		}
		var $btn = $("<"+button.field+"/>", button.options).appendTo($btnGroup).addClass('btn btn-'+button.state);
		var $icon = false;
		if (button.icon) {
			$icon = $("<span />").addClass(button.icon).addClass('icon').appendTo($btn);
		}
		if (button.label) {
			$("<span />").html(button.label.template(options.replace)).appendTo($btn);
			if ($icon) {
				$icon.addClass('icon-with-label');
			}
			$btn.attr('title', button.label.template(options.replace));
		}
		if (button.onClick) {
			$btn.click(function(event) {
				var result = button.onClick(event, button);
				if (result === null) {
					return false;
				}
				return result;
			});
		}
	});
	return $btnGroup;
};
