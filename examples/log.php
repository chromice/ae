<?php
	ae::import('ae/log.php');
	// ae::options('log')->set('directory', '/examples/log');
	
	ae::log("Hello world");
	
	trigger_error("This is some notice.", E_USER_NOTICE);
	
	$c = ae::container('/examples/container/container_inner.php')
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
	
	ae::log("Hello kitty. This is a number: ", 24, "And this is a boolean: ", true);
	ae::log("Hello again. This is a string: ", "foo", "As you can see strings are not dumped.");
	
	$r = ae::request();
	
	switch ($r->segment(0, 'normal'))
	{
		case 'error':
			ae::output('examples/log/trigger_error.php');
			break;

		case 'exception':
			ae::output('examples/log/throw_exception.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
		
		case 'critical':
			ae::output('examples/log/shutdown_error.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
			
		default: ?>
	<p>Team bravo.</p>
	<iframe style="position: fixed; bottom: 20px; left: 20px; z-index: -1" src="/log/critical" frameborder="0" width="0" height="0"></iframe>
	<iframe style="position: fixed; bottom: 20px; left: 20px; z-index: -1" src="/log/exception" frameborder="0" width="0" height="0"></iframe>
	<script type="text/javascript" charset="utf-8">
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
		
		var xhr = new XMLHttpRequest();
		xhr.open("GET", '/log/error', true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHTTPRequest');
		xhr.onreadystatechange = function(e) {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					var log = xhr.getResponseHeader('X-ae-log');
					if (!log) {
						console.error('Response header contains no log.')
					} else {
						console.log('AJAX request log:\n\n', decode_base64(log));
					}
				} else {
					console.error('There was a problem with the request.');
				}
			}
		}
		xhr.send();
	</script>
<?php
	}
?>