Zepto(function() {
	function sourceLink(file, line) {
		var link = 'txmt://open?url=file://' + $.trim(file) + '&line=' + $.trim(line);
		return link;
	}
	
	window.opener.inspectorOpened();
	
	$(window).on('unload', function() {
		window.opener.inspectorClosed();
	});
	
	
	function fitSource() {
		$('a.source > kbd').each(function() {
			if (!$(this).data('original')) {
				$(this).data('original', this.innerHTML);
			}
				
			trimLeft(this);
		});
	}
	
	$(window).on('resize', function() {
		$('a.source > kbd').each(function() {
			this.innerHTML = $(this).data('original');
			trimLeft(this);
		});
	});

	
	$(document).on('click', 'a.dump, a.backtrace, a.context', function(e) {
		var link = $(this),
			id = link.attr('href'),
			el = $(id),
			bt = link.closest('ol.backtrace');
		
		if (el.hasClass('visible')) {
			el.removeClass('visible');
			link.removeClass('active');
			return false;
		}
		
		var oldTop = link.offset().top;
		
		if (bt.length > 0) {
			bt.find('pre.dump').removeClass('visible');
			bt.find('a.dump').removeClass('active');
		} else {
			$('pre.dump, ol.backtrace').removeClass('visible');
			$('a.dump, a.backtrace, a.context').removeClass('active');
		}
		
		el.addClass('visible');
		link.addClass('active');
		
		window.scrollTo(0, window.scrollY - (oldTop - link.offset().top));
		
		fitSource();
		
		return false;
	});
	
	$.each(window.opener.logs(), function(i, log) {
		appendLog(log);
		fitSource();
	});
	
	setInterval(function() {
		$.each(window.opener.unprocessed_logs(), function(i, log) {
			appendLog(log);
		});
		fitSource();
	}, 1000);
	
	function appendLog(log) {
		var section = $('<section />')
				.appendTo('body'),
			list = $('<ol />')
				.addClass('log')
				.appendTo(section);
		
		// Break into parts
		var parts = log.log.split(/\n(?:- ){40}\n/m);
				
		// Parse log URI
		var header = $('<h1 />')
			.text(' ' + parseLogUri(parts.shift()) + ' ')
			.prependTo(section);
		
		// Add source
		$('<small />')
			.text(log.source)
			.addClass(log.source)
			.prependTo(header);
		
		// Add time
		var time = new Date(log.time);
		
		$('<small />')
			.text(double_digit(time.getHours()) + ':' 
				+ double_digit(time.getMinutes()) + ':' 
				+ double_digit(time.getSeconds()))
			.addClass('time')
			.prependTo(header);
		
		function double_digit(t) {
			t += "";
			
			if (t.length == 1) {
				return '0' + t;
			} else {
				return t;
			}
		}
		
		// Get rid of the last part
		parts.pop();
		
		// Parse and append all log entries
		$.each(parts, function(i, part) {
			var listItem = $('<li />').appendTo(list),
				id,
				message = [];
			
			// Parse all entry messages
			$.each(parseLogEntry(part), function(i, m) {
				if (typeof m === 'string') {
					var parts = parseMessageParts($.trim(m));
					message.push(parts.pop());
					while (parts.length > 0) $(parts.pop()).appendTo(listItem);
				} else if (m.Variable) {
					var type = m.Variable.match(/\((\w+)\)/);
					
					if (type && ['boolean', 'integer', 'double', 'float', 'string', 'NULL', 'resource'].indexOf(type[1]) > -1) {
						
						message.push('<var class="' + type[1] + '">' 
							+ $.trim(m.Dump) 
							+ '</var>');
					} else {
						id = uniqueID();
						
						message.push('<a class="dump" href="#' + id + '">' 
							+ (type ? type[1] : parseMessage(m.Variable)) + '</a>');
						$('<pre />')
							.attr('id', id)
							.addClass('dump')
							.text(parseDump(m.Dump))
							.appendTo(listItem);
					}
				} else {
					var _class, _text;
					
					if (m.Notice) {
						message.push('<span class="text notice">' + parseMessage(m.Notice) + '</span>');
					} else if (m.Warning) {
						message.push('<span class="text warning">' + parseMessage(m.Warning) + '</span>');
					} else if (m.Error) {
						message.push('<span class="text error">' + parseMessage(m.Error) + '</span>');
					} else if (m.Exception) {
						message.push('<span class="text exception">' + parseMessage(m.Exception) + '</span>');
					}

					if (m.File && m.Line) {
						message.push('<a href="' + sourceLink(m.File, m.Line) + '" class="source"><kbd>In ' 
							+ $.trim(m.File)
							+ ' at line '
							+ $.trim(m.Line)
							+ '</kbd></a>');
					}
					
					if (m.Context) {
						id = uniqueID();
						
						message.unshift('<a class="context" href="#' + id + '">Context</a>');
						$('<pre />')
							.attr('id', id)
							.addClass('dump')
							.text(parseDump(m.Context))
							.appendTo(listItem);
					}
					if (m.Backtrace) {
						id = uniqueID();
						
						message.unshift('<a class="backtrace" href="#' + id + '">Backtrace</a>');
						var counter = 1, backtrace = parseBacktrace(m.Backtrace);
						
						for (var i=0; i < backtrace.length; i++) 
						{ 
							if ('</li>' == backtrace.substr(i, 5)) counter++; 
						} 
						
						$('<ol />')
							.attr('id', id)
							.css('counter-reset', 'backtrace ' + counter)
							.addClass('backtrace')
							.html(backtrace)
							.appendTo(listItem);
					}
				}
			});
			
			$('<p />')
				.addClass('message ')
				.html(message.join(' '))
				.prependTo(listItem);
		});
	}
	
	function parseMessageParts(text) {
		var parts = [];
		
		parts.push(text.replace(/([a-z\s]+): (\d+\s?\w+)\s\(([+\-]\d+(?:\.\d+)?\s?\w+)\)\./ig, function (match, metric, absolute, relative) {
			metric = $.trim(metric);
			parts.push('<table class="' + metric.replace(/\s+/,'-').toLowerCase() + '"><tr>' 
				+ '<td class="metric">' + metric + '</td>'
				+ '<td class="absolute">' + absolute + '</td>'
				+ '<td class="relative">' + relative + '</td>'
			+ '</tr></table>');
			return '';
		}));
		
		return parts;
	}
	
	function parseLogUri(header) {
		var found = header.match(/Logged for (.+?) at/);
		
		if (found.length > 1) {
			return found[1];
		}
		
		return '';
	}
	
	function parseLogEntry(item) {
		var object = {},
			property;
		
		// Parse the entry
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
					return parseMessage(text);
				}
			}
		);
		
		// Get rid of all the noise
		var parsed = [];
		
		$.each(tokenizer.parse(item), function(i, o) {
			if (o && (typeof o !== 'string' || $.trim(o))) parsed.push(o);
		});
		
		if (parsed.length < 1) {
			parsed.push(object)
		}
		
		return parsed;
	}
	
	function parseMessage(message) {
		return $.trim(message.replace(/\&/g, '&amp;').replace(/</g, '&lt;'));
	}
	
	function parseDump(dump) {
		return dump
			.replace(/\n\s*\n/, '')
			.replace(/^\n+/, '')
			.replace(/\n+$/, '')
			.replace(/^ {4}/mg, '');
	}

	function parseBacktrace(backtrace) {
		// Remove padding
		backtrace = backtrace
			.replace(/\n\s*\n/, '')
			.replace(/^ {4}/mg, '');
		
		// Parse the entry
		var parts = [],
			dumps = [],
			dumpID = '',
			source = '',
			code = '';
		
		var tokenizer = new Tokenizer(
			[
				/^\d+\.\s*/m,
				/In "(.+?)" at line (\d+):/,
				/^--- Dump: (.+)/m, 
				/^--- End of dump/m
			],
			function(text, isToken, regex) {
				if (isToken) {
					if (text.substring(0, 8) === '--- Dump') {
						dumpID = uniqueID();
						var variable = text.match(regex)[1];
						
						code = code.replace(variable, '<a class="dump" href="#' + dumpID + '">' + variable + '</a>');
					} else if (text === '--- End of dump') {
						dumpID = '';
					} else if (text.match(/^\d+\./)) {
						wrapItem();
						parts.push('<li>');
					} else {
						var _source = text.match(regex);
						source = '<a href="' 
							+ sourceLink(_source[1], _source[2]) 
							+ '" class="source"><kbd>In ' 
							+ _source[1]
							+ ' at line '
							+ _source[2]
							+ ':</kbd></a>';
					}
				} else if (dumpID) {
					dumps.push('<pre id="' + dumpID + '" class="dump">' + parseDump(text) + '</pre>');
				} else if (!code) {
					code = '<pre class="code">' + parseMessage(text) + '</pre>';
				}
				
				return text;
			}
		)
		
		function wrapItem() {
			if (dumps.length > 0 || code || source) {
				parts.push(source);
				parts.push(code);
				parts.push(dumps.join(''));
				parts.push('</li>');
				
				source = '';
				code = '';
				dumps = [];
			}
		}
		
		tokenizer.parse(backtrace);
		
		wrapItem();
		
		return parts.join(' ');
	}
	
	function uniqueID () {
		var delim = "-";
	
		function S4() {
			return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
		}
	
		return (S4() + S4() + delim + S4() + delim + S4() + delim + S4() + delim + S4() + S4() + S4());
	}
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
			do { this.next(); } while( !this.ended );
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

/*
 * Trim left: http://jsfiddle.net/sP9AE/1/
 */
function trimLeft(row){
	var trimContents = function(row, node){
		while (row.scrollWidth > row.offsetWidth) {

			var childNode = node.firstChild;

			if (!childNode)
				return true;            

			if (childNode.nodeType == document.TEXT_NODE){
				trimText(row, node, childNode);
			}
			else {
				var empty = trimContents(row, childNode);
				if (empty){
					node.removeChild(childNode);
				}
			}
		}
	}
	var trimText = function(row, node, textNode){
		var prefix = 'In …',
			value = prefix + textNode.nodeValue;
		do {
			value = prefix + value.substr(prefix.length + 1);
			textNode.nodeValue = value;

			if (value == prefix){
				node.removeChild(textNode);
				return;
			}
		}
		while (row.scrollWidth > row.offsetWidth);
	}

	trimContents(row, row);
}