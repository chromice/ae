Zepto(function() {
	window.opener.consoleOpened();
	$(window).on('unload', function() {
		window.opener.consoleClosed();
	});
	$.each(window.opener.logs(), function(i, log) {
		appendLog(log);
	});
	
	function appendLog(log) {
		var section = $('<section />')
				.addClass(log.source)
				.appendTo('body'),
			list = $('<ol />')
				.appendTo(section);
		
		// Break into parts
		var parts = log.log.split(/\n(?:- ){40}\n/m);
				
		// Parse log URI
		var header = $('<h1 />')
			.text(' ' + parseLogUri(parts.shift()) + ' ')
			.prependTo(section);
		
		// Add time and source
		$('<small />')
			.text(log.source)
			.addClass(log.source)
			.prependTo(header);
		
		function double_digit(t) {
			t += "";
			
			if (t.length == 1) {
				return '0' + t;
			} else {
				return t;
			}
		};
		
		var time = new Date(log.time);
		
		$('<small />')
			.text(double_digit(time.getHours()) + ':' 
				+ double_digit(time.getMinutes()) + ':' 
				+ double_digit(time.getSeconds()))
			.addClass('time')
			.appendTo(header);
		
		
		// Get rid of the last part
		parts.pop();
		
		// Parse and append all log entries
		$.each(parts, function(i, item) {
			// console.log('- - - - -');
			var messages = parseLogEntry(item),
				listItem = $('<li />').appendTo(list),
				paragraph = $('<p />').appendTo(listItem);
			$.each(messages, function(i, m) {
				if (typeof m === 'string') {
					paragraph.append($.trim(m) + ' ');
				} else if (m.Variable) {
					var type = m.Variable.match(/\((\w+)\)/);
					
					if (type) {
						type = type[1];
						
						if (['boolean', 'integer', 'double', 'float', 'string', 'NULL', 'resource'].indexOf(type) > -1) {
							$('<var />')
								.addClass(type)
								.text($.trim(m.Dump))
								.appendTo(paragraph);
						}
					} else {
						$('<var />')
							.text($.trim(m.Variable))
							.appendTo(paragraph);
						$('<pre />')
							.text(m.Dump)
							.appendTo(listItem);
					}
				} else {
					var _class, _text;
					
					if (m.Notice) {
						_class = 'notice';
						_text = m.Notice;
					} else if (m.Warning) {
						_class = 'warning';
						_text = m.Warning;
					} else if (m.Error) {
						_class = 'error';
						_text = m.Error;
					} else if (m.Exception) {
						_class = 'exception';
						_text = m.Exception;
					}
					
					if (_class && _text) {
						paragraph
							.addClass(_class)
							.text($.trim(_text));
					}
					
					if (m.File && m.Line) {
						var source = $('<p />')
							.addClass('source')
							.appendTo(listItem);
						$('<kbd />')
							.addClass('file')
							.text(m.File)
							.appendTo(source);
						$('<kbd />')
							.addClass('line')
							.text(m.Line)
							.appendTo(source);
					}
					
					if (m.Context) {
						$('<var />')
							.text('Context')
							.appendTo(paragraph);
						$('<pre />')
							.text(m.Context)
							.appendTo(listItem);
					}
					if (m.Backtrace) {
						$('<var />')
							.text('Backtrace')
							.appendTo(paragraph);
						$('<pre />')
							.text(m.Backtrace)
							.appendTo(listItem);
					}
				}
				console.log(m);
			});
		});
	};
	
	function parseLogEntry(item) {
		var object = {},
			property;
		
		var tokenizer = new Tokenizer(
			[
				/^(Backtrace|Context):/m,
				/^(Notice|Warning|Error|Exception|Code|File|Line): /m,
				/^--- Dump: (.+)/m, 
				/^--- End of dump/m
			],
			function(text, isToken, regex) {
				if (isToken) {
					if (text.substring(0, 8) === '--- Dump') {
						object['Variable'] = text.match(regex)[1];
						property = 'Dump';
					} else if (text === '--- End of dump') {
						var _object = object;
						
						object = {};
						property = '';
						
						return _object;

					} else {
						property = text.match(regex)[1];
					}
				} else if (!isToken && property) {
					object[property] = text;
					
					property = '';
				} else {
					return text;
				}
			}
		);
		
		var parsed = [];
		
		$.each(tokenizer.parse(item), function(i, o) {
			if (o && (typeof o !== 'string' || $.trim(o))) parsed.push(o);
		});
		
		if (parsed.length < 1) {
			parsed.push(object)
		}
		
		return parsed;
	}
	
	function parseLogUri(header) {
		var found = header.match(/Logged for (.+?) at/);
		
		if (found.length > 1) {
			return found[1];
		}
		
		return '';
	};
});

/**
 * Tokenizer/jQuery.Tokenizer
 * Copyright (c) 2007-2008 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 2/29/2008
 *
 * @projectDescription JS Class to generate tokens from strings.
 * http://flesler.blogspot.com/2008/03/string-tokenizer-for-javascript.html
 *
 * @author Ariel Flesler
 * @version 1.0.1
 */
;(function(){

	var Tokenizer = function( tokenizers, doBuild ){
		if( !(this instanceof Tokenizer ) )
			return new Tokenizer( tokenizers, onEnd, onFound );

		this.tokenizers = tokenizers.splice ? tokenizers : [tokenizers];
		if( doBuild )
			this.doBuild = doBuild;
	};

	Tokenizer.prototype = {
		parse:function( src ){
			this.src = src;
			this.ended = false;
			this.tokens = [ ];
			do this.next(); while( !this.ended );
			return this.tokens;
		},
		build:function( src, real ){
			if( src )
				this.tokens.push(
					!this.doBuild ? src :
					this.doBuild(src,real,this.tkn)
				);	
		},
		next:function(){
			var self = this,
				plain;

			self.findMin();
			plain = self.src.slice(0, self.min);

			self.build( plain, false );

			self.src = self.src.slice(self.min).replace(self.tkn,function( all ){
				self.build(all, true);
				return '';
			});

			if( !self.src )
				self.ended = true;
		},
		findMin:function(){
			var self = this, i=0, tkn, idx;
			self.min = -1;
			self.tkn = '';

			while(( tkn = self.tokenizers[i++]) !== undefined ){
				idx = self.src[tkn.test?'search':'indexOf'](tkn);
				if( idx != -1 && (self.min == -1 || idx < self.min )){
					self.tkn = tkn;
					self.min = idx;
				}
			}
			if( self.min == -1 )
				self.min = self.src.length;
		}
	};

	if( window.jQuery ){
		jQuery.tokenizer = Tokenizer;//export as jquery plugin
		Tokenizer.fn = Tokenizer.prototype;
	}else
		window.Tokenizer = Tokenizer;//export as standalone class

})();