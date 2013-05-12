var ae_log_severity = function(level) {
		if (this.level === undefined) this.level = 0;
		if (level === undefined) return this.level;
		this.level = Math.max(this.level, level);
	},
	ae_log_monitor = function (log, source) {
	
	if (this.logs === undefined) {
		this.logs = [];
	}
	
	// Return results, if called without arguments.
	if (log === undefined) {
		var logs = this.logs;
		this.logs = [];
		return logs;
	}

	// Route all new logs to top window monitor
	if (window !== window.top) {
		if (typeof window.top.ae_log_monitor === 'function') {
			window.top.ae_log_monitor(log, source === 'document' ? 'iframe' : source);
		} 
		return;
	}
	
	if (log.match(/^(?:Error|Exception):/m) !== null) ae_log_severity(2);
	if (log.match(/^(?:Warning|Notice):/m) !== null) ae_log_severity(1);
	
	this.logs.push({
		time: Date.now(),
		'source': source,
		'log': log
	});

	document.getElementById("ae-inspector-button").style.left = "40px";
};

// Intercept all AJAX calls.
(function(open) {
	XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
		this.addEventListener("readystatechange", function() {
			if (this.readyState === 4) {
				var log = this.getResponseHeader('X-ae-log');

				if (log) {
					ae_log_monitor(decode_base64(log), 'ajax');
				}
			}

		}, false);
		open.call(this, method, url, async, user, pass);
	};
	function decode_base64(s) {
		var e={},i,k,v=[],r='',w=String.fromCharCode;
		var n=[[65,91],[97,123],[48,58],[43,44],[47,48]];
		
		for(z in n){for(i=n[z][0];i<n[z][1];i++){v.push(w(i));}}
		for(i=0;i<64;i++){e[v[i]]=i;}
		
		for(i=0;i<s.length;i+=72){
		var b=0,c,x,l=0,o=s.substring(i,i+72);
			 for(x=0;x<o.length;x++){
					c=e[o.charAt(x)];b=(b<<6)+c;l+=6;
					while(l>=8){r+=w((b>>>(l-=8))%256);}
			 }
		}
		return r;
	}
})(XMLHttpRequest.prototype.open);

// Parse body and inject inspector button.
(function() {
	if (window === window.top && !document.getElementById('ae-inspector-button')) {
		document.write('<iframe id="ae-inspector-button" src="' + (base_path || '/') + 'inspector/button.html" style="position:fixed; bottom:40px; left:-999em; width:60px; height:60px" frameborder="0"></iframe>');
	}

	// Find the HTML comment node with the log
	function getAllCommentNodes(el) {
	
		var comments = [];
	
		if (el.nodeName == "#comment" || el.nodeType == 8) {
			
			if (el.nodeValue.substr(0,7) === ' ae-log') {
				comments.push(el.nodeValue.substr(8));
			}
	
		} else if (el.childNodes.length > 0 ) {
	
			for (var i = 0; i < el.childNodes.length; i++) {
	
				comments = comments.concat(getAllCommentNodes(el.childNodes[i]));
			}
		}
	
		return comments;
	}
	
	var comments = getAllCommentNodes(document);
	
	for (var i=0, comment; comment = comments[i]; i++){
		ae_log_monitor(comment, 'document');
	}
})();

// Hide button when inspector window is open.
function inspectorOpened() {
	document.getElementById("ae-inspector-button").style.display = "none";
}
function inspectorClosed() {
	document.getElementById("ae-inspector-button").style.display = "block";
}