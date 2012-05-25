(function() {
	
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

var comments = getAllCommentNodes(document.getElementsByTagName("html")[0]);

for (var i=0, comment; comment = comments[i]; i++){
	console.log(comment)
}

// __ae_log_monitor($log);

// Enable button for the top window
if (false && window === window.top) {
	
	var script = document.createElement('iframe');
	script.setAttribute('src', "/console/button.html");
	script.setAttribute('style', "position: fixed; bottom: 40px; left: 40px; width: 60px; height: 60px");
	script.setAttribute('frameborder', "0");
	document.body.appendChild(script);
}

})();
