/**
 * 
 * Character Count Plugin with limit action == Original
 * 
 * Character Count Plugin - jQuery plugin Dynamic character count for text areas
 * and input fields written by Alen Grakalic
 * 
 * http://cssglobe.com/post/7161/jquery-plugin-simplest-twitterlike-dynamic-character-count-for-textareas
 * 
 * Copyright (c) 2009 Alen Grakalic (http://cssglobe.com) Dual licensed under
 * the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * Built for jQuery library http://jquery.com
 * 
 */

(function($) {

	$.fn.charCount = function(options) {

		// default configuration properties
		var defaults = {
			limit : 140,
			warning : 25,
			css : 'counter',
			counterElement : 'span',
			cssWarning : 'warning',
			cssExceeded : 'exceeded',
			counterText : '',
			warning: function (element) {
				return;
			},
			exceeded: function (element) {
				return;
			},
			allowed: function (element) {
				return;
			}
		};

		var options = $.extend(defaults, options);

		function calculate(obj) {
			
			var count = $(obj).val().length;
			
			var available = options.limit - count;
			
			var hasCssExceeded = $(obj).hasClass(options.cssExceeded);
			var hasCssWarning = $(obj).hasClass(options.cssWarning);
			
			if (available < 0) {
				
				if (!hasCssExceeded) {
					$(obj).addClass(options.cssExceeded);
					$(obj).next().addClass(options.cssExceeded);
				    options.exceeded(obj);
				}
				
				if (hasCssWarning) {
					$(obj).removeClass(options.cssWarning);
					$(obj).next().removeClass(options.cssWarning);
				}
				
				
			} else if (available <= options.warning && available >= 0) {
				
				if (hasCssExceeded) {
					
					$(obj).removeClass(options.cssExceeded);
					$(obj).next().removeClass(options.cssExceeded);
					$(obj).addClass(options.cssWarning);
					$(obj).next().addClass(options.cssWarning);
					options.allowed(obj);
					
				} else if (!hasCssWarning) {
				
					$(obj).addClass(options.cssWarning);
					$(obj).next().addClass(options.cssWarning);
					options.warning(obj);
					
				}
				
				
			} else {
				
				$(obj).removeClass(options.cssWarning);
				$(obj).removeClass(options.cssExceeded);
				$(obj).next().removeClass(options.cssWarning);
				$(obj).next().removeClass(options.cssExceeded);
				
				if (hasCssWarning || hasCssExceeded) {
					options.allowed(obj);
				}
				
			}
			
			$(obj).next().html(options.counterText + available);
		};

		this.each(function() {
			var counter = $('<' + options.counterElement + '>').addClass(options.css).text(options.counterText);
			
			$(this).after(counter);
			
			calculate(this);
			
			$(this).keyup(function() {
				calculate(this)
			});
			
			$(this).change(function() {
				calculate(this)
			});
		});

	};

})(jQuery);
