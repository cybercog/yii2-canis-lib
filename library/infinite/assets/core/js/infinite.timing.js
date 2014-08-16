var timer = new TimerEngine();
function TimerEngine() {
	this.registry = {};
	this.globalPause = false;
}

TimerEngine.prototype.pause = function() {
	this.globalPause = true;
	return true;
};

TimerEngine.prototype.resume = function() {
	this.globalPause = false;
	return true;
};

TimerEngine.prototype.setInterval = function(id, func, interval) {
	this.clear(id);

	return this.registry[id] = {
		type: 'interval',
		timer: setInterval(this.wrap(id, func), interval),
		paused: false
	};
};

TimerEngine.prototype.setTimeout = function(id, func, delay) {
	this.clear(id);
	return this.registry[id] = {
		type: 'timeout',
		timer: setTimeout(this.wrap(id, func), delay),
		paused: false
	};
};

TimerEngine.prototype.wrap = function(id, func) {
	var self = this;
	return function() {
		if (self.globalPause) {
			return true;
		}
		if (self.registry[id] !== undefined && self.registry[id].paused === true) {
			return true;
		}
		return func();
	};
};

TimerEngine.prototype.clear = function(id) {
	if (this.registry[id] !== undefined) {
		if (this.registry[id].type === 'timeout') {
			clearTimeout(this.registry[id].timer);
		} else {
			clearInterval(this.registry[id].timer);
		}
		delete this.registry[id];
	}
	return true;
};

