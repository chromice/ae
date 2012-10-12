var __ae_log_monitor = function (logs) {
	if (this.logs === undefined) {
		this.logs = [];
	}

	if (logs === undefined || !typeof logs === 'array') {
		logs = this.logs;
		this.logs = [];
		return logs;
	}

	// Route all new logs to top window monitor
	if (window !== window.top && typeof window.top.__ae_log_monitor === 'function') {
		window.top.__ae_log_monitor(logs);
		return;
	}

	this.logs = this.logs.concat(logs);

	for (var i=0, log; log = logs[i]; i++){
		console.log(log);
	} 
};

// Initialize main script
(function(open) {
	var script = document.createElement('script');
	script.setAttribute('src', "/console/main.js");
	document.head.appendChild(script);
})();

// Process AJAX requests
(function(open) {
	XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
		this.addEventListener("readystatechange", function() {
			if (this.readyState === 4) {
				var log = this.getResponseHeader('X-ae-log');

				if (log) {
					__ae_log_monitor([log]);
				}
			}

		}, false);
		open.call(this, method, url, async, user, pass);
	};
})(XMLHttpRequest.prototype.open);
