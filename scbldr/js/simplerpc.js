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

/**
 * Simple message exchange.
 * 
 * Provides a set of utility methods to bind local objects and connect remote objects.
 * 
 * Current implementation relies on MessagePort.
 * 
 * @param {MessagePort} port
 */
function EMessageExchange(port) {
	this.port = port;
	
	var handlers = {};
	var seq = 0;
	var cbs = {};
	//var ints = {};
	var that = this;
	var pubq = {};
	
	/**
	 * 
	 * @param {String} name
	 * @param {Object} object
	 */
	this.bind = function(name, object) {
		if (name in handlers) delete handlers[name]; // return false;
		handlers[name] = EInvokeHandler(object);
	};
	
	/**
	 * 
	 * @param {String} name
	 * @param {Object} iface an object containing the "interface" definition for the remote object
	 * @return {EObject}
	 */
	this.connect = function(name, iface) {
		return new EObject({
			acall: function(method, args) {
				if (typeof jQuery === "undefined") throw new Error("jQuery required for sending requests with return");
				var msgid = ++seq;
				var d = new $.Deferred();
				that.port.postMessage({id: msgid, target:name, method: method, params: args});
				cbs[msgid] = d;
				return d;
			},
			anotify: function(method, args) {
				that.port.postMessage({target:name,method:method, params: args});
			}
		}, iface);
	};
	
	// the next few methods implement pub/sub pattern
	// this is not going to be easy for AJAX!!! you'll need to implement some sort of comet-like protocol
	// that constantly polls the server for new data.
	
	/**
	 * 
	 * @param {String} name
	 * @param {Function} handler
	 */
	this.subscribe = function(name, handler) {
		if (name in handlers) delete handlers[name];// throw new Error("Duplicate handler " + name);
		// notify we want to receive publications on subject
		that.port.postMessage({sub:name}); // set interest on other end
		if (name in pubq) {
			var q = pubq[name];
			while (q.length) {
				handler.apply(port, q.shift());
			}
			delete pubq[name];
		}
		handlers[name] = handler;
	};
	
	/**
	 * 
	 * @param {String} name
	 */
	this.unsubscribe = function(name) {
		that.port.postMessage({unsub:name}); // clear interest on other end
		delete handlers[name];
	};
	
	/**
	 * 
	 * @param {String} name
	 * @param {Any} data
	 */
	this.publish = function(name, data) {
		//if (!ints.hasOwnProperty(name)) return;
		that.port.postMessage({pub:name,data:data});
	};
	
	/**
	 * 
	 */
	this.shutdown = function() {
		for (var k in handlers) {
			if (handlers.hasOwnProperty(k)) delete handlers[k];
		}
		for (var k in cbs) {
			if (cbs.hasOwnProperty(k)) { cbs[k].reject("Potential memory leak"); delete cbs[k]; };
		}
		delete this.port;
	};
	
	/**
	 * 
	 * @param {} msg
	 */
	this.receive = function(msg) {
		var handler;
		if (msg.target) {
			//console.info("with target");
			handler = handlers[msg.target];
			if (!handler) throw new Error("No handler defined for " + msg.target);
		} else if (msg.id) {
			//console.info("with result");
			handler = resultHandler;
		} else {
			//console.info("pubsub event");
			handler = handlers[0];
		}
		handler.call(port, msg);
	};
	
	handlers[0] = function(msg) {
		if (msg.pub) {
			if (!(msg.pub in handlers)) {
				if (!pubq[msg.pub]) pubq[msg.pub] = [];
				pubq[msg.pub].push(msg.data);
			} else {
				handlers[msg.pub].call(port, msg.data);
			}
		} else if (msg.sub) {
			//ints[msg.sub] = true;
		} else if (msg.unsub) {
			//delete ints[msg.unsub];
		}
	};

	function resultHandler(msg) {
		if (!cbs[msg.id]) {
			return;
		}
		var d = cbs[msg.id];
		delete cbs[msg.id];
		if (msg.result)
			d.resolve(msg.result);
		else {
			var error = msg.error;
			var allowed = {
				"Error": Error,
				"SyntaxError": SyntaxError,
				"RangeError": RangeError,
				"ReferenceError": ReferenceError,
				"SyntaxError": SyntaxError,
				"TypeError": TypeError,
				"URIError": URIError
			};
			if (typeof window !== "undefined") {
				var e = new (allowed[error.name] || Error)(error.message, error.fileName, error.lineNumber);
				if (error.stack)
					e.stack = error.stack;
				error.__proto__ = e;
			}
			d.reject(error);
		}
	}
	
	this.port.addEventListener("message", function(e) {
		that.receive.call(that.port, e.data);
	}, false);
}

function EInvokeHandler(obj, permits) {
	function sendError(m, exc) {
		if (m.id) {
			this.postMessage({id:m.id,error:exc});
		} else {
			throw new Error(errorMessage);
		}
	}
	function sendResult(message, result) {
		if (message.id) {
			if (message.delay) {
				// only used this to test if messages were sent OOO
				var that = this;
				setTimeout(function() {
					that.postMessage({id: message.id, result: result});
				}, message.delay);
			} else {
				this.postMessage({id: message.id, result: result});
			}
		}
	}

	function messageHandler(message) {
		if (!message.hasOwnProperty("method") || !message.hasOwnProperty("params")) {
			console.info("Missing method and params.. not a call?");
			return;
		}
		var mname = message["method"];
		if (!(mname in obj)) {
			sendError.call(this, message, null, "No such method with name '" + mname + "' exists");
			return;
		}
		
		if (permits && !permits.hasOwnProperty(mname)) {
			sendError.call(this, message, null, "Access to method '" + mname + "' is not allowed");
			return;
		}
		
		var method = obj[mname];
		var params = message["params"];
		var result;
		try {
			result = method.apply(obj, params);
			// allow methods to return a result asynchronously (necessary for Worker in IE)
			// jQuery.Deferred isn't an actual type... so we're trying to guess if its a deferred
			if (typeof jQuery !== "undefined" && (result && jQuery.isFunction(result.done) && jQuery.isFunction(result.fail))) {
				var that =this;
				result.done(function(res) {
					sendResult.call(that, message, res);
				}).fail(function(err) {
					sendError.call(that, message, err);
				});
			} else {
				sendResult.call(this, message, result);
			}
		} catch(e) {
			console.error(e);
			if (message.id) {
				this.postMessage({id: message.id, error: e});
			}
		}
	}
	
	return messageHandler;
}

/*

if (typeof window === "undefined" && typeof console === "undefined") {
	console = new EObject(self, {
		info: {returns:false},
		debug: {returns:false},
		error: {returns:false},
		warn: {returns:false}
	});
}

*/
function EObject(dispatch, def) {
	var self = this;
	for (var k in def) {
		if (!def.hasOwnProperty(k)) continue;
		var v = def[k];
		if (k in this)
			throw new Error("Overriding existing methods not allowed");
		this[k] = (function(k, v) {
			return function() {
				var args = Array.prototype.slice.call(arguments, 0);
				if (v && v.returns) {
					return dispatch.acall(k, args);
				} else {
					dispatch.anotify(k, args);
				}
			};
		})(k, v);
	}
}
