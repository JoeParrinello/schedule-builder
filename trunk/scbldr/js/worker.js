/*!
 * Schedule builder
 *
 * Copyright (c) 2011, Edwin Choi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/* provides a fake Worker implementation
 * the implementation here is in no way a web worker replacement...
 * all this will do is load the script from the given URL, then look for a global
 * function named <script-name>_connectObject.
 * it passes the MessagePort to the script, which is then used to communicate between
 * the two end points.
 */

(function() {
	if (typeof Worker !== "undefined") return;
	
	function _Worker(url) {
		var scr = document.createElement("script");
		scr.type = "text/javascript";
		scr.src = url;
		$("head")[0].appendChild(scr);
		
		var channel = new MessageChannel();
		$(scr).load(function() {
			var sp = url.lastIndexOf('/');
			var ep = url.lastIndexOf('.');
			var name = url.substr(sp+1, ep - sp - 1);
			window[name + "_connectObject"](channel.port2);
			channel.port2.start();
		});
		$.extend(this, channel.port1);
	}
	$.extend(_Worker.prototype, {
		terminate: function() {
			this.close();
		}
	});
	
	Worker = _Worker;
}());
