var brite = brite || {};

brite.version = "1.1.2";

if ( typeof module === "object" && module && typeof module.exports === "object" ) {
		module.exports = brite;
} else if ( typeof define === "function" && define.amd ) {
		define( "brite", [], function () { return brite; } );
} else {
		window.brite = brite;
}

// ------------------- //
// ------ brite ------ //

/**
 * @namespace brite is used to managed the lifescycle of UI components (create, display, and destroy)
 * 
 */
(function($) {
	
	// Note: for now, document bound view events are just namespaced with the view_id
	var DOC_EVENT_NS_PREFIX = ".";
	var WIN_EVENT_NS_PREFIX = ".";
	
	var cidSeq = 0;

	var _componentDefStore = {};
	
	// _templateLoadedPerComponentName[ComponentName] is defined and === true wne the template has been loaded
	var _templateLoadedPerComponentName = {};

	// when loading a component, we put a promise in this map
	var _deferredByComponentName = {};

	var _transitions = {};

	// ------ Public API: Component Management ------ //

	/**
	 * MUST be called to register the component
	 * 
	 * @param {String}
	 *            name the name of the component
	 * 
	 * @param {config}
	 *            config a config object
	 * 
	 *            config.parent {String|jQuery} jquery selector, html element, jquery object (if not set, the the element will not be
	 *            added in the rendering logic). <br />
	 *            Note 1) If ctx.parent is absent from the component definition and from this method call, the brite
	 *            will not append the returned element to the DOM. So, if ctx.parent is null, then the create() must
	 *            take care of adding the elements to the DOM. However, the postDisplay will still be called.
	 *
	 *            config.animation (experimental) {String} the animation ("fromLeft" , "fromRight", or null) (default undefined)
	 * 
	 *            config.replace (experimental) {String|jQuery} jquery selector string, html element, or jquery object (default undefined) of the
	 *            element to be replaced
	 * 
	 *            config.emptyParent {Boolean} (default false) if set/true will call empty() on the parent before adding the new element (default
	 *            false). Valid only if no transition and build return an element
	 * 
	 *            config.unique (experimental) {Boolean} if true, the component will be display only if there is not already one component with
	 *            the same name in the page.
	 * 
	 *            config.loadTmpl {Boolean|String} (default false) If true, then, it will load the template the first time this component is displayed.
	 *                                                 If it is a string it use it as the file name to be loaded from the directory. If it starts with "/" then, it will be from the base, otherwise,
	 *                                                 it will be relative to the template folder. The default template folder is "template/" but can be set by brite.config.tmplPath.
	 *                                                 
	 * 
	 *            config.checkTmpl {Boolean|String|jQuery} (default false). (require config.loadTmpl) If true, it will check if the template for this component has been added, by default it will check "#tmpl-ComponentName". 
	 *                                                                   If it is a string or jQuery, then it will be use with jQuery if it exists.
	 *                                                                   Note that the check happen only the first time, then, brite will remember for subsequent brite.display  
	 *                                     
	 * @param {Object|Function}
	 *            componentFactory (Required) Factory function or "object template" that will be used to create the
	 *            object instance. If componentFactory is a plain object, the "object template" will be cloned to create
	 *            the component instance. If it is a function, it will be called and a component instance object will be
	 *            exptected as return value.<br />
	 *            <br />
	 * 
	 * A "Component" object can have the following methods <br />
	 * <br />
	 *      component.create(data,config): (required) function that will be called with (data,config) to build the
	 *                                     component.$element.<br />
	 *      component.init(data,config): (optional) Will be called just after the create and the component instance has been
	 *                                   initialized. <br />
	 *      component.postDisplay(data,config): (optional) This method will get called with (data,config) after the component
	 *                                          has been created and initialized (postDisplay is deferred for performance optimization) <br />
	 *                                          Since this call will be deferred, it is a good place to do non-visible logic, such as event bindings.<br />
	 *      component.destroy() (optional) This will get called when $.bRemove or $.bEmpty is called on a parent (or on the
	 *                                     element for $.bRemove). It will get called before this component htmlElement will get removed<br />
	 *      component.postDestroy() (optional) This will get called when $.bRemove or $.bEmpty is called on a parent (or on
	 *                                         the element for $.bRemove). It will get called after this component htmlElement will get removed<br />
	 * 
	 */
	brite.registerView = function(name, arg1, arg2) {
		var def = {};
		def.name = name;
		def.componentFactory = (arg2)?arg2:arg1;
		var config = (arg2)?arg1:null; // no config if only two arguments
		def.config = $.extend({}, brite.viewDefaultConfig,config);
		_componentDefStore[name] = def;

		// This resolve the deferred if we had a deferred component loading 
		// (old way, where the brite.register is in the template)
		var deferred = _deferredByComponentName[name];
		if (deferred) {
			deferred.resolve(def);
			delete _deferredByComponentName[name];
		}
	};
	
	// for backgward compatibility
	brite.registerComponent = brite.registerView;
	
	

	/**
	 * This just instantiate a new component for a given name. This is useful for manipulating the component off
	 * lifecycle for performance. For example, sometime building a component and displaying in the background (with
	 * z-index) allow the browser to do its caching magic, and can speed up the first appearance of the component when
	 * it is due.
	 * 
	 * @param {string}
	 *            name
	 */
	/* DEPRECATED for now
	brite.instantiateComponent = function(name) {
		var loaderDeferred = loadComponent(name);
		return instantiateComponent(componentDef);
	}
	*/
	// ------ /Public API: Component Management ------ //

	// ------ Public API: Transition Management ------ //
	brite.registerTransition = function(name, transition) {
		_transitions[name] = transition;
	};

	brite.getTransition = function(name) {
		return _transitions[name];
	};
	// ------ /Public API: Transition Management ------ //

	// ------ Public API: Display Management ------ //

	/**
	 * This will create, init, and display a new view. It will load the view on demand if needed.
	 * 
	 * @param {String}
	 *            name (required) the view name
	 * @param {Element} HTML Element, jQuery element, or a jQuery selector, where the element will be added. 
	 * @param {Object}
	 *            data (optional, required if config) the data to be passed to the build and postDisplay.
	 * @param {Object}
	 *            config (optional) config override the component's config (see {@link brite.registerComponent} config
	 *            params for description)
	 * @return {Component} return the component instance.
	 */
	brite.display = function(viewName, parent, data, config) {
		if (parent){
			config = config || {};
			config.parent = parent;
		}
		return process(viewName, data, config);
	};
	
	// to ease backward compatiblity 
	brite.legacyDisplay = function(viewName, data, config) {
		var parent = (config)?config.parent:null;
		brite.display(viewName,parent,data,config);
	};
	
	

	/**
	 * Same as brite.display but bypass the build() step (postDisplay() will still be called). So, this will create a
	 * new component and attach it to the $element and call postDisplay on it.
	 * 
	 */
	brite.attach = function(viewName, $element, data, config) {
		return process(viewName, data, config, $element);
	};
	// ------ /Public API: Display Management ------ //

	// ------ Public Properties: Config ------ //
	/**
	 * Config for the brite module.
	 * <ul>
	 * <li><span class="light fixedFont">{String|jQuery}</span> <strong>config.componentsHTMLHolder</strong>
	 * (default: "body") jQuery selector or object pointing to the element that will be used to add the loaded component
	 * HTML.</li>
	 * </ul>
	 * 
	 */
	brite.config = {
		componentsHTMLHolder: "body",
		tmplPath: "tmpl/",
		jsPath: "js/",
		cssPath: "css/",
		tmplExt: ".tmpl"
		
	};

	brite.viewDefaultConfig = {
		loadTmpl: false,
		loadCss: false,
		emptyParent : false,
		postDisplayDelay : 0
	};
	
	brite.defaultComponentConfig = brite.viewDefaultConfig;
	// ------ /Public Properties: Config ------ //

	/**
	 * Return the promise().<br />
	 * 
	 * <ul>
	 * <li>It will load the component only if it not already loaded</li>
	 * 
	 * <li>As of now, the component is loaded by loading (via sync-AJAX class) and adding the "components/[name].html"
	 * content to the "body" (can be overriden with brite.config.componentsHTMLHolder)</li>
	 * 
	 * <li> So, developers need to make sure of the following:<br /> - the "component/[name].html" exists (relative to
	 * the current page) and does not contain any visible elements <br /> - the "component/[name].html" will call the
	 * briteui.registerComponent([name],componentDef) <br />
	 * </li>
	 * </ul>
	 * <br />
	 * TODO: Needs to make the the component
	 * 
	 * @param {Object}
	 *            name component name (no space or special character)
	 * @return The loaderDeferred
	 */
	function loadComponent(name) {
		var loaderDeferred = $.Deferred();

		var loadComponentDefDfd = loadComponentDef(name);
		
		
		loadComponentDefDfd.done(function(componentDef){
			var loadTemplateDfd, loadCssDfd;
			// --------- Load the tmpl if needed --------- //
			var loadTemplate = componentDef.config.loadTmpl; 
			if (loadTemplate && !_templateLoadedPerComponentName[name] ){
				// if we have a check template, we need to check if the template has been already loaded
				var needsToLoadTemplate = true;
				var checkTemplate = componentDef.config.checkTemplate;        
				if (checkTemplate){
					var templateSelector = (typeof checkTemplate == "string")?checkTemplate:("#tmpl-" + name);
					if ($(templateSelector).length > 0){
						needsToLoadTemplate = false;
					}         
				}
				 
				if (needsToLoadTemplate){
					loadTemplateDfd = $.Deferred();
					// if it is a string, then, it is the templatename, otherwise, the component name is the name
					var templateName = (typeof loadTemplate == "string")?templateName:(name + ".html");
					$.ajax({
						url : brite.config.tmplPath + name + brite.config.tmplExt,
						async : true
					}).complete(function(jqXHR, textStatus) {
						$(brite.config.componentsHTMLHolder).append(jqXHR.responseText);
						_templateLoadedPerComponentName[name] = true;
						loadTemplateDfd.resolve();
					});       
				}
				
			}
			// --------- /Load the tmpl if needed --------- //
			
			// --------- Load the css if needed --------- //
			var loadCss = componentDef.config.loadCss;
			if (loadCss){
				//TODO: need to add the checkCss support
				loadCssDfd = $.Deferred();
				var cssFileName = brite.config.cssPath + name + ".css";
				var includeDfd = includeFile(cssFileName,"css");
				includeDfd.done(function(){
					loadCssDfd.resolve();
				}).fail(function(){
					if (console){
						console.log("Brite ERROR: cannot load " + cssFileName + ". Ignoring issue");
					}
					loadCssDfd.resolve();
				});      
			}
			// --------- /Load the Template if needed --------- //
			
			
			$.when(loadTemplateDfd,loadCssDfd).done(function(){
				loaderDeferred.resolve(componentDef);
			});
			
						
		});
		
		loadComponentDefDfd.fail(function(ex){
			if (console){
				console.log("BRITE-ERROR: Brite cannot load component: " + name + "\n\t " + ex);
			}
			loaderDeferred.reject();
		});
		
		return loaderDeferred.promise();
	}

	// Load the componentDef if needed and return the promise for it
	function loadComponentDef(name){
		var dfd = $.Deferred();
		
		var componentDef = _componentDefStore[name];
		
		if (componentDef){
			dfd.resolve(componentDef);
		}else{
			var resourceFile = brite.config.jsPath + name + ".js";
			var includeDfd = includeFile(resourceFile,"js");
			includeDfd.done(function(){
				componentDef = _componentDefStore[name];
				if (componentDef){
					dfd.resolve(componentDef);
				}else{ 
					dfd.reject("Component js file [" + resourceFile + 
										"] loaded, but it did not seem to have registered the view - it needs to call brite.registerView('" + name + 
										"',...config...) - see documentation");        
				}
			}).fail(function(){
				dfd.reject("Component resource file " + resourceFile + " not found");
			});
		}
		
		return dfd.promise();
	}

	// if $element exist, then, bypass the create
	function process(name, data, config, $element) {
		var loaderDeferred = loadComponent(name);

		var processDeferred = $.Deferred();

		var createDeferred = $.Deferred();
		var initDeferred = $.Deferred();
		var postDisplayDeferred = $.Deferred();

		var processPromise = processDeferred.promise();
		processPromise.whenCreate = createDeferred.promise();
		processPromise.whenInit = initDeferred.promise();
		processPromise.whenPostDisplay = postDisplayDeferred.promise();
		
		loaderDeferred.done(function(componentDef) {
			config = buildConfig(componentDef, config);
			var component = instantiateComponent(componentDef);

			// If the config.unique is set, and there is a component with the same name, we resolve the deferred now
			// NOTE: the whenCreate and whenPostDisplay won't be resolved again
			// TODO: an optimization point would be to add a "bComponentUnique" in the class for data-b-view that
			// have a confi.unique = true
			// This way, the query below could be ".bComponentUnique [....]" and should speedup the search significantly
			// on UserAgents that supports the getElementsByClassName
			if (config.unique) {
				var $component = $("[data-b-view='" + name + "']");
				if ($component.length > 0) {
					component = $component.bComponent();
					processDeferred.resolve(component);
					return processDeferred;
				}
			}

			// ------ create ------ //
			var deferred$element = $.Deferred();
			// if there is no element, we invoke the build
			if (!$element) {
				// Ask the component to create the new $element
				var createReturn = invokeCreate(component, data, config);
				// if it custom Deferred, then, assume it will get resolved with the $element (as by the API contract)
				if (createReturn && $.isFunction(createReturn.promise) && !createReturn.jquery) {
					// TODO: will need to use the new jQuery 1.6 pipe here (right now, just trigger on done)
					createReturn.done(function($element) {
						deferred$element.resolve($element);
					}).fail(function() {
						deferred$element.reject();
					});
				}
				// otherwise, if the $element is returned , resolve the deferred$element immediately
				else {
					if (createReturn) {
						$element = createReturn;
					}
					deferred$element.resolve($element);
				}
			}
			// if the $element is already here, then, it is an attach, so, do a immediate Deffered
			else {
				deferred$element.resolve($element);
			}
			// ------ /create ------ //

			// ------ render & resolve ------ //
			deferred$element.promise().done(function(createResult) {
				// if there is an element, then, manage the rendering logic.
				var $element;
				if (createResult) {
					if (typeof createResult === "string"){
						createResult = createResult.trim();
					}
					// make sure we get the jQuery object
					$element = $(createResult);

					bind$element($element, component, data, config);

					// attached the componentPromise to this $element, this way, during rendering sub component can sync
					// with it.
					$element.data("componentProcessPromise", processPromise);

					createDeferred.resolve(component);

					$.when(invokeInit(component, data, config)).done(function() {
						// render the element
						// TODO: implement deferred for the render as well.
						renderComponent(component, data, config);

						// TODO: this might need to be fore the renderComponent
						initDeferred.resolve(component);

					});

				} else {
					// TODO: need to look if we need this. Basically, that allow to have create methods that do/return
					// nothing but still instantiate the component
					createDeferred.resolve(component);

					// TODO: probably need to invokeInit in this case as well. For now, just resolve the initDeferred
					initDeferred.resolve(component);

				}

				processPromise.whenInit.done(function() {
					var parentComponentProcessPromise, invokePostDisplayDfd;

					// if there is a parent component, then need to wait until it display to display this one.
					if ($element && $element.parent()) {
						var parentComponent$Element = $element.parent().closest("[data-b-view]");

						if (parentComponent$Element.length > 0) {
							parentComponentProcessPromise = parentComponent$Element.data("componentProcessPromise");
							if (parentComponentProcessPromise){
								parentComponentProcessPromise.whenPostDisplay.done(function() {
									invokePostDisplayDfd = invokePostDisplay(component, data, config);
									invokePostDisplayDfd.done(function() {
										postDisplayDeferred.resolve(component);
									});
								});
							}
						}
					}

					// if we did not have any parentComponentProcessPromise, then, just invoke
					if (!parentComponentProcessPromise) {
						invokePostDisplayDfd = invokePostDisplay(component, data, config);
						invokePostDisplayDfd.done(function() {
							postDisplayDeferred.resolve(component);
						});
					}

				});

			});
			// ------ /render & resolve ------ //
			processPromise.whenPostDisplay.done(function() {
				processDeferred.resolve(component);
			});
		});

		loaderDeferred.fail(function(){
			processDeferred.reject();
			createDeferred.reject();
			initDeferred.reject();
			postDisplayDeferred.reject();
		});

		return processPromise;
	}

	function renderComponent(component, data, config) {
		var $parent;
		if (config.transition) {
			var transition = brite.getTransition(config.transition);

			if (transition) {
				transition(component, data, config);
			} else {
				brite.log.error("Transition [" + config.transition + "] not found. Transitions need to be registered via brite.registerTranstion(..) before call.");
			}
		}
		// if no transition remove/show
		else {
			if (config.replace) {
				$(config.replace).bRemove();
			}

			
			// note: if there is no parent, then, the sUI.diplay caller is responsible to add it
			if (config.parent) {
				$parent = $(config.parent);
				if (config.emptyParent) {
					$parent.bEmpty();
				}
				$parent.append(component.$el);
			}
		}

	}

	// ------ Helpers ------ //
	// build a config for a componentDef
	function buildConfig(componentDef, config) {
		var instanceConfig = $.extend({}, componentDef.config, config);
		instanceConfig.componentName = componentDef.name;
		return instanceConfig;
	}

	function instantiateComponent(componentDef) {
		var component;
		var componentFactory = componentDef.componentFactory;
		if (componentFactory) {
			// if it is a function, call it, it should return a new component object
			if ($.isFunction(componentFactory)) {
				component = componentFactory();
			}
			// if it is a plainObject, then, we clone it (NOTE: We do a one level clone)
			else if ($.isPlainObject(componentFactory)) {
				component = $.extend({}, componentFactory);
			} else {
				brite.log.error("Invalid ComponentFactory for component [" + componentDef.componentName +
												"]. Only types Function or Object are supported as componentFactory. Empty component will be created.");
			}
		} else {
			brite.log.error("No ComponentFactory for component [" + componentDef.componentName + "]");
		}

		if (component) {
			component.name = componentDef.name;
			// .cid is a legacy property, .id is the one to use. 
			component.cid = component.id = "bview_" + cidSeq++;
		}
		return component;
	}

	function invokeCreate(component, data, config) {
		// backward compatibility
		var createFunc = component.create || component.build;
		// assert that we have a build method
		if (!createFunc || !$.isFunction(createFunc)) {
			brite.log.error("Invalid 'create' function for component [" + component.name + "].");
			return;
		}
		return createFunc.call(component, data, config);
	}

	function invokeInit(component, data, config) {
		var initFunc = component.init;
		if ($.isFunction(initFunc)) {
			return initFunc.call(component, data, config);
		}
	}
	// 
	function bind$element($element, component, data, config) {
		component.el = $element[0];
		// component.$element is for deprecated, .$el is te way to access it. 
		component.$el = component.$element = $element; 
		$element.data("bview", component);

		$element.attr("data-b-view", config.componentName);
		$element.attr("data-brite-cid", component.cid);
	}

	// Note: This will be called even if .postDisplay is not defined (test is inside this method)
	//       So, we do the view events binding here. 
	function invokePostDisplay(component, data, config) {
		var invokeDfd = $.Deferred();

		// bind the view events
		if (component.events){
			bindEvents(component.events,component.$el,component);
		}
		
		// bind the document events (note: need to have a namespace since they will need to be cleaned up)
		if (component.docEvents){
			bindEvents(component.docEvents,$(document),component, DOC_EVENT_NS_PREFIX + component.id);
		}
		
		// bind the window events if present
		if (component.winEvents){
			bindEvents(component.winEvents,$(window),component, WIN_EVENT_NS_PREFIX + component.id);
		}
		
		if (component.parentEvents){
			$.each(component.parentEvents,function(key,val){
				var parent = component.$el.bView(key);
				if (parent !== null){
					var events = component.parentEvents[key];
					bindEvents(events,parent.$el,component,"." + component.id);
				}
			});
		}
		
		bindDaoEvents(component);

		// Call the eventual postDisplay
		// (differing for performance)
		if (component.postDisplay) {
			// if the component has a delay >= 0, then, we use a setTimeout
			if (config.postDisplayDelay >= 0) {
				setTimeout(function() {
					var postDisplayDfd = component.postDisplay(data, config);
					if (postDisplayDfd && $.isFunction(postDisplayDfd.promise)) {
						postDisplayDfd.done(function() {
							invokeDfd.resolve();
						});
					} else {
						invokeDfd.resolve();
					}
				}, config.postDisplayDelay);
			}
			// otherwise, we call it in sync
			else {

				var postDisplayDfd = component.postDisplay(data, config);
				if (postDisplayDfd && $.isFunction(postDisplayDfd.promise)) {
					postDisplayDfd.done(function() {
						invokeDfd.resolve();
					});
				} else {
					invokeDfd.resolve();
				}
			}
		}
		// if there is now postDisplay, then, trigger it anyway
		else {
			invokeDfd.resolve();
		}

		return invokeDfd.promise();
	}
	
	function bindEvents(eventMap,$baseElement,component,namespace){
		$.each(eventMap,function(edef,etarget){
			var edefs = edef.split(";");
			var ename = edefs[0] + ((namespace)?namespace:"");
			var eselector = edefs[1]; // can be undefined, but in this case it is direct.

			var efn = getFn(component,etarget);
			if (efn){
				$baseElement.on(ename,eselector,function(){
					var args = $.makeArray(arguments);
					efn.apply(component,args);
				});
			}else{
				throw "BRITE ERROR: '" + component.name + "' component event handler function '" + etarget + "' not found."; 
			}
		});		
	}
	
	function bindDaoEvents(component){
		var daoEvents = component.daoEvents;
		
		if (component.daoEvents){
			// for now, the namespace is just the component id
			var ns = component.id;
			$.each(daoEvents,function(edef,etarget){
				var efn = getFn(component,etarget);
				if (efn){
					var edefs = edef.split(";");
					var ename = edefs[0];
					ename = ename.charAt(0).toUpperCase() + ename.slice(1);
					var eventTypes = edefs[1];
					var entityTypes = edefs[2];
					brite.dao["on" + ename](eventTypes,entityTypes,function(event){
						var args = $.makeArray(arguments);
						efn.apply(component,args);						
					},ns);
				}else{
					throw "BRITE ERROR: '" + component.name + "' component daoEvent handler function '" + etarget + "' not found.";
				}
			});
		}
	}
	
	function getFn(component,target){
			var fn = target;
			if (!$.isFunction(fn)){
				fn = component[target];
			}
			return fn;		
	}
	// ------ /Helpers ------ //

	// --------- File Include (JS & CSS) ------ //
	/*
	 * Include the file name in the <head> part of the DOM and return a deferred that will resolve when done
	 */
	function includeFile(fileName, fileType) {
		var dfd = $.Deferred();
		var fileref;
		if(fileType === "js") {
			fileref = document.createElement('script');
			fileref.setAttribute("type", "text/javascript");
			fileref.setAttribute("src", fileName);
		} else if(fileType === "css") {
			fileref = document.createElement("link");
			fileref.setAttribute("rel", "stylesheet");
			fileref.setAttribute("type", "text/css");
			fileref.setAttribute("href", fileName);
		}
		
		if (fileType === "js"){
			if (fileref.addEventListener){
				fileref.onload = function(){
					dfd.resolve(fileName);
				};
			}else{ // for old IE
				// TODO: probably need to handle the error case here
				fileref.onreadystatechange = function(){
					if (fileref.readyState === "loaded" || fileref.readyState === "complete"){
							dfd.resolve(fileName);
					}
				};
			}
			
			if (fileref.addEventListener){
				fileref.addEventListener('error', function(){
					dfd.reject();
				}, true);
			}
		}else if (fileType === "css"){
			if (document.all){
				// The IE way, which is interestingly the most standard
				fileref.onreadystatechange = function() {
					var state = fileref.readyState;
					if (state === 'loaded' || state === 'complete') {
						fileref.onreadystatechange = null;
						dfd.resolve(fileName);
					}
				};
			}else{
				
				// unfortunately, this will rarely be taken in account in modern browsers
				if (fileref.addEventListener) {
					fileref.addEventListener('load', function() {
						dfd.resolve(fileName);
					}, false);
				}

				// hack from: http://www.backalleycoder.com/2011/03/20/link-tag-css-stylesheet-load-event/
				var html = document.getElementsByTagName('html')[0];
				var img = document.createElement('img');
				$(img).css("display","none"); // hide the image
				img.onerror = function(){
					html.removeChild(img);
					// for css, we cannot know if it fail to load for now
					dfd.resolve(fileName);
				};
				html.appendChild(img);
				img.src = fileName;      
			}
		}
		
		if( typeof fileref != "undefined") {
			document.getElementsByTagName("head")[0].appendChild(fileref);
		}
		
		return dfd.promise();
	}
	// --------- /File Include (JS & CSS) ------ //

})(jQuery);

// ------ brite ------ //
// ---------------------- //

(function($) {
	// warning: duplicate definition (must be the same a previous block)
	var DOC_EVENT_NS_PREFIX = ".";
	var WIN_EVENT_NS_PREFIX = ".";
	

	/**
	 * Safely empty a HTMLElement of its children HTMLElement and bComponent by calling the preRemove and postRemove on
	 * every child components.
	 * 
	 * @return the jQuery object
	 */
	$.fn.bEmpty = function() {
		return this.each(function() {
			var $this = $(this);

			var componentChildren = $this.bFindComponents();

			// call the preRemoves
			$.each(componentChildren, function(idx, childComponent) {
				processDestroy(childComponent);
			});

			// do the empty
			$this.empty();

		});
	};

	/**
	 * Safely remove a HTMLElement and the related bComponent by calling the preRemote and postRemove on every child
	 * components as well as this component.
	 * 
	 * @return what a jquery.remove would return
	 */
	$.fn.bRemove = function() {

		return this.each(function() {
			var $this = $(this);
			$this.bEmpty();

			if ($this.is("[data-b-view]")) {
				var component = $this.data("bview");
				processDestroy(component);

				$this.remove();
			} else {
				$this.remove();
			}
		});

	};

	function processDestroy(component) {
		// The if(component) is a safeguard in case destroy gets call twice (issue when clicking fast on
		// test_brite-02-transition....)
		if (component) {
			// unbind view events
			$(document).off(DOC_EVENT_NS_PREFIX + component.id);
			$(window).off(WIN_EVENT_NS_PREFIX + component.id);
			
			if (brite.dao){
				brite.dao.offAny(component.id);
			}
			
			if (component.parentEvents){
				$.each(component.parentEvents,function(key,val){
					var parent = component.$el.bView(key);
					if (parent && parent.$el){
						parent.$el.off("." + component.id);
					}
				});
			}
									
			var destroyFunc = component.destroy;

			if ($.isFunction(destroyFunc)) {
				destroyFunc.call(component);
			}
			
			// Delete this element, as a sign at this component has been destroyed.
			delete component.$el;
		}
	}

})(jQuery);

// ------------------------------------- //
// ------------- bView APIs ------------ //
(function($) {

	/**
	 * 
	 * Return the component that this html element belong to. Thi traverse the tree backwards (this html element up to
	 * document) to find the closest html element containing the brite component for this name.
	 * 
	 * If a componentName is given then it will try to find the given component.
	 * 
	 * If no componentName is given, then it will return the first component found.
	 * 
	 * For example:
	 * 
	 * @example var myComponent = $(thisDiv).bComponent("myComponent");
	 * 
	 * @param {String}
	 *            componentName The component name to be match when traversing the tree, if undefined, then, the
	 *            closestComponent will be return.
	 * 
	 */
	$.fn.bView = function(viewName) {

		// iterate and process each matched element
		var $el;
		if (viewName) {
			$el = $(this).closest("[data-b-view='" + viewName + "']");
		} else {
			$el = $(this).closest("[data-b-view]");
		}

		return $el.data("bview");

	};
	
})(jQuery);	
// ------------- /bView APIs ------------ //
// ------------------------------------- //


// ------------------------------------- //
// --------- old bComponent APIs ------- //
(function($) {
		
	// backwards compatibility;
	$.fn.bComponent = $.fn.bView;

	/**
	 * Get the list of components that this htmlElement contains.
	 * 
	 * @param {string}
	 *            componentName (optional) if present, will filter only the component with this matching name
	 * @return a javascript array of all the match component
	 */
	$.fn.bFindComponents = function(componentName) {
		var childrenComponents = [];

		this.each(function() {
			var $this = $(this);

			var $componentElements;

			if (componentName) {
				$componentElements = $(this).find("[data-b-view='" + componentName + "']");
			} else {
				$componentElements = $(this).find("[data-b-view]");
			}

			$componentElements.each(function() {
				var $component = $(this);
				childrenComponents.push($component.data("bview"));
			});
		});

		return childrenComponents;
	};

	/**
	 * Get the list of components that this htmlElement contains.
	 * 
	 * @param {string}
	 *            componentName (optional) if present, will filter only the component with this matching name
	 * @return a javascript array of all the match component
	 */
	$.fn.bFindFirstComponent = function(componentName) {
		var childrenComponents = [];

		this.each(function() {
			var $this = $(this);

			var $componentElements;

			if (componentName) {
				$componentElements = $(this).find("[data-b-view='" + componentName + "']:first");
			} else {
				$componentElements = $(this).find("[data-b-view]:first");
			}

			$componentElements.each(function() {
				var $component = $(this);
				childrenComponents.push($component.data("bview"));
			});
		});

		return childrenComponents;
	};



})(jQuery);

// -------- /old bComponent APIs ------- //
// ------------------------------------- //


// ------------------------ //
// ------ brite utils ------ //

(function($) {
	
	// add the trim prototype if not available natively.
	if(!String.prototype.trim) {
		String.prototype.trim = function () {
			return this.replace(/^\s+|\s+$/g,'');
		};
	}
	
	// default options for brite.whenEach
	var whenEachOpts = {
		failOnFirst : true
	};
	
	/**
	 * Convenient function that resolve each items serially with resolver function. 
	 * 
	 * @param {Array}    items:    array values to iterate through
	 * @param {Function} resolver: will be called with resolver(value,index) and can return the result or a promise of the result.
	 * @param {Object}   opts: (optional) options with the following values
	 *                     opts.failOnFirst {boolean} (default: true) if true, will reject on first fail with error object
	 * 
	 * @return {Promise} promise that will get resolve with an Array of result of each value
	 * 
	 * The promise is resolve with an array of result when success
	 * 
	 * The promise is rejected with an array of {success:[true/false],value:[result/error]}
	 *     
	 */
	brite.whenEach = function(items,resolver,opts){
		var dfd = $.Deferred();
		var results = [];
		var i = 0;
		
		opts = $.extend({},whenEachOpts, opts);
		
		resolveAndNext();
		
		function resolveAndNext(){
			if (i < items.length){
				var item = items[i];
				var result = resolver(item,i);

				// if the result is a promise (but not a jquery object, which is also a promise), then, pipe it
				if (typeof result !== "undefined" && result !== null && $.isFunction(result.promise) && !result.jquery){
					result.done(function(finalResult){
						results.push(finalResult);
						i++;
						resolveAndNext();
					});		
					
					// if it fails, then, reject
					// TODO: needs to support the failOnFirst: true
					result.fail(function(ex){
						var fails = $.map(function(val,idx){
							return {success:true,value:val};
						});
						fails.push({success:false,value:ex});
						dfd.reject(fails);
					});
					// TODO: need to handle the case the promise fail
				}
				// if it is a normal object or a jqueryObject, then, just push the value and move to the next
				else{
					results.push(result);
					i++;
					resolveAndNext();
				}
			}
			// once we run out
			else{
				dfd.resolve(results);
			}
		} 
		
		return dfd.promise();
	};
	
	
	// Private array of chars to use
	var CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');

	/**
	 * Create a random id. <br />
	 * <br />
	 * Code from: Math.uuid 2010 Robert Kieffer http://www.broofa.com
	 * 
	 * 
	 * @example brite.uuid(); // returns "92329D39-6F5C-4520-ABFC-AAB64544E172" brite.uuid(15); // 15
	 *          character ID (default base=62), returns "VcydxgltxrVZSTV" brite.uuid(8, 2); // returns "01001010"
	 * 
	 * @param {Number}
	 *            len (optional) length in char of the returned random ID. If absent, the standard UUID format will be
	 *            returned
	 * @param {Number}
	 *            radix (optional) radix of the random number. (Default: 62)
	 */
	brite.uuid = function(len, radix) {
		var chars = CHARS, uuid = [];
		radix = radix || chars.length;
		len = len || 10;
		for ( var i = 0; i < len; i++){
			uuid[i] = chars[0 | Math.random() * radix];
		}
		return uuid.join('');
	};

	/**
	 * Return the value from this rootObj with the "." delimited path name.
	 * 
	 * Return undefined if any of the node going down is undefined.
	 * 
	 * @param {Object}
	 *            rootObj this is the root obj to start from
	 * @param {String}
	 *            pathToValue this is the "." delimited path to the value
	 * 
	 * @example brite.value({contact:{firstName:"Mike"}},"contact.firstName"); // return Mike
	 * 
	 */
	brite.value = function(rootObj, pathToValue) {
		if (!rootObj) {
			return rootObj;
		}
		// for now, return the rootObj if the pathToValue is empty or null or undefined
		if (!pathToValue) {
			return rootObj;
		}
		var result;
		var i, l, names = pathToValue.split(".");
		var iName, iVal = rootObj;
		for (i = 0, l = names.length; i < l; i++) {
			iName = names[i];
			if (iVal == null) {
				return undefined;
			}
			iVal = iVal[iName];
			if (typeof iVal === "undefined") {
				return iVal;
			}
		}
		return iVal;
	};

	// substract all the values for two object (ignore the not numbers one), and return the new object.
	brite.substract = function(obj1,obj2){
		var r = {};
		$.each(obj1,function(key,val1){
			var val2 = obj2[key];
			if (!isNaN(val1) && !isNaN(val2)){
				r[key] = val1 - val2;
			}
		});
			
		return r;
	};
	
	// add all the values for two object (ignore the not numbers one), and return the new object.
	brite.add = function(obj1,obj2){
		var r = {};
		$.each(obj1,function(key,val1){
			var val2 = obj2[key];
			if (!isNaN(val1) && !isNaN(val2)){
				r[key] = val1 + val2;
			}
		});
			
		return r;
	};

	/**
	 * @namespace
	 * 
	 * Array utilities
	 */
	brite.array = {

		/**
		 * Remove item(s) from an array.
		 * Code from: Array Remove - By John Resig (MIT Licensed)
		 * 
		 * @param {Object}
		 *            a the Array
		 * @param {Object}
		 *            from the first index to remove from
		 * @param {Object}
		 *            to (optional) the last index to remove
		 */
		remove : function(a, from, to) {
			var rest = a.slice((to || from) + 1 || a.length);
			a.length = from < 0 ? a.length + from : from;
			return a.push.apply(a, rest);
		},

		/**
		 * For a array of object, this will get the first index of the matching prop name/value return -1 if no match
		 * 
		 * @param {Object}
		 *            a the Array
		 * @param {Object}
		 *            propName the property name
		 * @param {Object}
		 *            propValue the property value to be matched
		 */
		getIndex : function(a, propName, propValue) {
			if (a && propName && typeof propValue != "undefined") {
				var i, obj, l = a.length;
				for (i = 0; i < l; i++) {
					obj = a[i];
					if (obj && obj[propName] === propValue) {
						return i;
					}
				}
			}
			return -1;
		},
		
		getItem : function(a, propName, propValue){
			var idx = this.getIndex(a,propName,propValue);
			if (idx > -1){
				return a[idx];
			}else{
				return null;
			}
		},

		/**
		 * Sort an array of object by a propName
		 * 
		 * @param {Object}
		 *            a the Array
		 * @param {Object}
		 *            propName the property name to be sorted by
		 */
		sortBy : function(a, propName) {
			return a.sort(sortByFunc);
			function sortByFunc(a, b) {
				if (typeof a === "undefined")
					return -1;
				if (typeof b === "undefined")
					return 1;

				var x = a[propName];
				var y = b[propName];
				return ((x < y) ? -1 : ((x > y) ? 1 : 0));
			}
		},

		/**
		 * From an array of javascript obect, create a map (js object) where the key is the propName value, and the
		 * value is the array item. If the propName does not on an item exist, it will ingore the item.
		 * 
		 * @example var myVehicules = [{id:"truck",speed:80},{id:"racecar",speed:200}]; 
		 *          var vehiculeById = brite.array.toMap(myVehicules,"id"); // vehiculeById["truck"].speed == 80
		 *          
		 * @param {Object}
		 *            a The array
		 * @param {Object}
		 *            keyName the property name that will be use
		 */
		toMap : function(a, keyName) {
			var i, l = a.length;
			var map = {}, item, key;
			for (i = 0; i < l; i++) {
				item = a[i];
				key = item[keyName];
				if (typeof key != "undefined" && key != null) {
					map[key] = item;
				}
			}
			return map;
		}
	};

	/**
	 * Give a random number between two number
	 * 
	 * @param {Object}
	 *            from
	 * @param {Object}
	 *            to
	 */
	brite.randomInt = function(from, to) {
		var offset = to - from;
		return from + Math.floor(Math.random() * (offset + 1));
	};

	// from the "JavaScript Pattern" book
	brite.inherit = function(C, P) {
		var F = function() {
		};
		F.prototype = P.prototype;
		C.prototype = new F();
		C._super = P.prototype; 
		C.prototype.constructor = C;
	};
	
	
	// hack to force the browsers on mobile devices to redraw
	// basically, it is a visually invisible div, but technically in the display tree
	// that we change the content and css property (width). This seems to force the browser to refresh
	var _flushUIVar = 2;
	var _$flushUI;
	brite.flushUI = function(){
		if (brite.ua.hasTouch()){
			if (!_$flushUI){
				_$flushUI = $("<div id='b-flushUI' style='position:absolute;opacity:1;z-index:-1000;overflow:hidden;width:2px;color:rgba(0,0,0,0)'>flushUI</div>");
				$("body").append(_$flushUI);
			}
			_flushUIVar = _flushUIVar * -1;
			_$flushUI.text("").text(_flushUIVar);
			_$flushUI.css("width",_flushUIVar + "px");
		}
	};

})(jQuery);

// ------ /brite utils ------ //
// ------------------------ //


// ---------------------------------- //
// ------ brite.ua (User Agent) ------ //

/**
 * @namespace
 * 
 * User Agent utilities to know what capabilities the browser support.
 */
brite.ua = {};

(function($) {
	var CSS_PREFIXES = {webkit:"-webkit-",chrome:"-webkit-",mozilla:"-moz-",msie:"-ms-",opera:"-o-"};
	
	var VAR_PREFIXES = {webkit:"Webkit",mozilla:"Moz",chrome:"Webkit",msie:"ms",opera:"o"};
	

	// privates
	var _cssVarPrefix = null;
	var _cssPrefix = null;
	var _cssHas = null;
	var _cssHasNo = null;
	
	var _hasTouch = null;
	var _hasTransition = null;
	var _hasBackfaceVisibility = null;
	var _hasCanvas = null;
	var _transitionPrefix = null; 
	var _eventsMap = {}; // {eventName:true/false,....}

	var _browserType = null; // could be "webkit" "moz" "ms" "o"
	
	
	// --------- Get brite.ua.browser --------- //
	// Use the jquery compat code. (we still need this for the prefix)
	function uaMatch( ua ) {
		ua = ua.toLowerCase();
	
		var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
		/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
		/(msie) ([\w.]+)/.exec( ua ) ||
		ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
		[];
	
		return {
			browser: match[ 1 ] || "",
			version: match[ 2 ] || "0"
		};
	}

	var matched = uaMatch( navigator.userAgent );
	var browser = {};	
	if ( matched.browser ) {
		browser[ matched.browser ] = true;
		browser.version = matched.version;
	}
	// Chrome is Webkit, but Webkit is also Safari.
	if ( browser.chrome ) {
		browser.webkit = true;
	} else if ( browser.webkit ) {
		browser.safari = true;
	}	
	brite.ua.browser = browser;
	// --------- /Get brite.ua.browser --------- //
	
	
	// --------- Prefix and rendererType ------ //
	function computeBrowserType(){
		$.each(CSS_PREFIXES,function(key,val){
			if (brite.ua.browser[key]){
				_browserType = key;
				_cssPrefix = CSS_PREFIXES[key];
				_cssVarPrefix = VAR_PREFIXES[key];
			}
		});
	}
	
	brite.ua.cssPrefix = function() {
		if (_cssPrefix === null){
			computeBrowserType();
		}
		return _cssPrefix;
	};

	brite.ua.cssVarPrefix = function() {
		if (_cssVarPrefix === null){
			computeBrowserType();
		}
		return _cssVarPrefix;
	};
	// --------- /Prefix and rendererType ------ //
	
	/**
	 * return a css friendly string with all the "has-**" that this ua supports
	 * 
	 * @example 
	 *   brite.ua.cssHas(); // "has-canvas has-transition" for modern PC browsers
	 *                      // "has-canvas has-transition has-touch" in the case of touch devices
	 *   
	 */
	brite.ua.cssHas = function(){
		
		if (_cssHas === null){
			var STR = "has";
			_cssHas = "";
			$.each(brite.ua,function(key){
				var fun = brite.ua[key];
				var cssKey;
				if (key.indexOf(STR) === 0 && $.isFunction(fun)){
					if (fun.call(brite.ua)){
						cssKey = "has-" + key.substring(STR.length).toLowerCase();
						_cssHas += cssKey + " ";
					}
					
				}
			});
		}
		
		return _cssHas;
	};
	
	/**
	 * Return a css friendly version of the "no" of the has. "has-no-canvas" for example.
	 * 
	 * @example
	 *   brite.ua.
	 */
	brite.ua.cssHasNo = function(){
		if (_cssHasNo === null){
			var STR = "has";
			_cssHasNo = "";
			$.each(brite.ua,function(key){
				var fun = brite.ua[key];
				var cssKey;
				if (key.indexOf(STR) === 0 && $.isFunction(fun)){
					if (!fun.call(brite.ua)){
						cssKey = "has-no-" + key.substring(STR.length).toLowerCase();
						_cssHasNo += cssKey + " ";
					}
					
				}
			});
		}
		
		return _cssHasNo;		
	};

	/**
	 * Return true if the eventname is supported by this user agent.
	 * 
	 * @param {Object}
	 *            eventName
	 */
	brite.ua.supportsEvent = function(eventName) {
		var r = _eventsMap[eventName];
		if (typeof r === "undefined") {
			r = isEventSupported(eventName);
			_eventsMap[eventName] = r;
		}
		return r;
	};

	/**
	 * Convenient methods to know if this user agent supports touch events. It tests "touchstart".
	 */
	brite.ua.hasTouch = function() {
		return this.supportsEvent("touchstart");
	};

	brite.ua.hasCanvas = function() {
		if (_hasCanvas === null) {
			var test_canvas = document.createElement("canvas");
			_hasCanvas = (test_canvas.getContext) ? true : false;
		}
		return _hasCanvas;
	};

	/**
	 * Return true if the user agent supports CSS3 transition.
	 */
	brite.ua.hasTransition = function() {
		if (_hasTransition === null) {
			_hasTransition = hasStyle("transition","Transition","color 1s linear",true);
		}
		return _hasTransition;
	};
	
	
	brite.ua.hasBackfaceVisibility = function(){
		if (_hasBackfaceVisibility === null){
			_hasBackfaceVisibility = hasStyle("backface-visibility","BackfaceVisibility","hidden",true);
			
			// being conservative, because, sometime windows does not support backface visibility.
			if (navigator.platform.toLowerCase().indexOf("win") > -1){
				_hasBackfaceVisibility = false;	
			}
		}
		
		return _hasBackfaceVisibility;
	};

	// ------ Privates ------ //
	function hasStyle(styleName,styleVarName,sampleValue,withPrefix){
			var div = document.createElement('div');
			styleName = (withPrefix)?(brite.ua.cssPrefix() + styleName):styleName;
			div.innerHTML = '<div style="' + styleName + ': ' + sampleValue + '"></div>';
			styleVarName = (withPrefix)?(brite.ua.cssVarPrefix() + styleVarName):styleVarName;
			return (div.firstChild.style[styleVarName])?true:false;		
	}
	
	var isEventSupported = (function() {
		var TAGNAMES = {
			'select' : 'input',
			'change' : 'input',
			'submit' : 'form',
			'reset' : 'form',
			'error' : 'img',
			'load' : 'img',
			'abort' : 'img'
		};

		function isEventSupported(eventName) {
			var el = document.createElement(TAGNAMES[eventName] || 'div');
			eventName = 'on' + eventName;
			var isSupported = (eventName in el);
			if (!isSupported) {
				el.setAttribute(eventName, 'return;');
				isSupported = typeof el[eventName] == 'function';
			}
			el = null;
			return isSupported;
		}
		return isEventSupported;
	})();
	// ------ /Privates ------ //

})(jQuery);

// ------ /brite.ua (User Agent) ------ //
// ----------------------------------- //


var brite = brite || {};

/**
 * @namespace brite.dao data manager layers to register, access DAOs.
 * DAOs are javascript objects that must implement the following CRUD methods get, list, create, update, remove methods.<br />
 * Signatures of these methods should match the corresponding brite.dao.** methods.<br />
 * <br />
 * Note that DAO CRUD methods can return directly the result or a deferred object. Also, it is important to note that brite.dao.*** CRUD access methods
 * will always return deferred object (either the DAO return deferred, or a wrapped deferred if the DAO method did not return a deferred)<br />
 * <br />
 * The deferred pattern for daos allows the application to be agnostic about the call mode, synchronous or asynchronous (e.g. Ajax, Workers, and other callback based called),
 * and consequently offer the maximum flexibility during development and production. It also enforce a good practice on how to build the UI components.<br />
 * <br />
 * If there is a need to access the daos result directly, the brite.sdm ("straight dm") can be used.
 */
// --------- DAO Support --------- //
(function($) {

	var daoDic = {};

	//data change listeners
	var daoChangeEventListeners = {};

	//daoListeners
	var daoListeners = {};

	function getDao(objectType) {
		var dao = daoDic[objectType];
		if (dao) {
			return dao;
		} else {
			var er = "Cannot find the DAO for objectType: " + objectType;
			throw er;
		}
	}

	brite.dao = function(entityType) {
		return getDao(entityType);
	};

	var internalMethods = {
		isDataChange : true, 
		entityType: true
	};
	
	var dataChangeMethodRegEx = /remove|delete|create|update/i;


	/**
	 * Register a DAO for a given object type. A DAO must implements the "CRUD" method, get, list, create, update, remove and must return (directly
	 * or via deferred) the appropriate result value.
	 *
	 * @param {DAO Oject} a Dao instance that implement the crud methods: get, find, create, update, remove.
	 */
	brite.registerDao = function(daoHandler) {

		var daoObject = {};
		
		// support function or property
		var entityType = ($.isFunction(daoHandler.entityType))?daoHandler.entityType():daoHandler.entityType;
		
		if (!entityType || typeof entityType !== "string"){
			throw "Cannot register daoHandler because entityType '" + entityType + "' is not valid." + 
						" Make sure the daoHandler emplement .entityType() method which must return a string of the entity type"; 
		}
		
		daoObject._entityType = entityType;
		daoObject._handler = daoHandler;

		$.each(daoHandler, function(k, v) {
			// if it is a function and not an internalMethods
			if ($.isFunction(daoHandler[k]) && !internalMethods[k]) {
				var methodName = k;
				var isDataChange = dataChangeMethodRegEx.test(methodName);
				
				if (daoHandler.isDataChange){
					isDataChange = isDataChange || daoHandler.isDataChange(methodName); 
				}

				daoObject[methodName] = (function(entityType, methodName, isDataChange) {
					return function() {
						var resultObj = daoHandler[methodName].apply(daoHandler, arguments);
						var resultPromise = wrapWithDeferred(resultObj);

						_triggerOnDao(entityType, methodName, resultPromise);

						resultPromise.done(function(result) {
							_triggerOnResult(entityType, methodName, result);
							if (isDataChange) {
								brite.triggerDataChange(entityType, methodName, result);
							}
						});

						return resultPromise;
					};
				})(entityType, methodName, isDataChange);
			}
		});
		
		
		daoDic[entityType] = daoObject;
		
		if ($.isFunction(daoObject.init)){
			daoObject.init(entityType);
		}
		
		return daoObject;
	};

	// --------- Internal Utilities For Dao Events --------- //
	var _ALL_ = "_ALL_";

	/**
	 * Build the arguments for all the brite.dao.on*** events from the arguments
	 * Can be
	 * - (entityTypes,actions,func,namespace)
	 * - (entityTypes,func,namespace)
	 * - (func,namespace)
	 *
	 * Return an object with
	 *   .events (with the namespace)
	 *   .objectTypes (as class css selector, ".User, .Task"
	 *   .func the function to register
	 *   .namespace
	 */
	function buildDaoOnEventParamMap(args) {
		var i, val, namespace, map = {};

		// build the map
		for ( i = args.length - 1; i > -1; i--) {
			val = args[i];
			// if it is a function, set it.
			if ($.isFunction(val)) {
				map.func = val;
			}
			// if we did not get the function yet, this is the name space
			else if (!map.func) {
				namespace = val;
			}
			// if we have the func, and it is the second argument, it si the actions
			else if (map.func && i === 1) {
				map.actions = val;
			}
			// if we have the func, and it is the first argument, it is objectTypes
			else if (map.func && i === 0) {
				map.objectTypes = val;
			}
		}

		
		// create the namespace if not present
		if ( typeof namespace === "undefined") {
			throw "BRITE DAO BINDING ERROR: any binding with brite.dao.on*** needs to have a namespace after the function. " + 
						" Remember to cleanup the event at component close with brite.dao.off(mynamespace)"; 
						 
		}

		
		// complete the actions
		if (!map.actions) {
			map.actions = _ALL_ + "." + namespace;
		} else {
			var ns = "." + namespace + " ";
			// build the events, split by ',', add the namespace, and join back
			map.actions = map.actions.split(",").join(ns) + ns;
		}

		// complete the objectTypes
		// build the objectTypes, split by ',', add the "." prefix, and join back
		if (map.objectTypes) {
			var objectTypes = map.objectTypes.split(",");
			$.each(objectTypes, function(idx, val) {
				objectTypes[idx] = "." + $.trim(val);
			});
			map.objectTypes = objectTypes.join(",");
		}

		map.namespace = namespace;

		return map;
	}

	/**
	 * Utility method that will construct a jQuery event with the daoEvent
	 * and trigger it to the appropriate $receiver given the dictionary and objectType
	 *
	 */
	function _triggerDaoEvent(dic, $receiversRoot, objectType, daoEvent) {

		var evt = $.extend(jQuery.Event(daoEvent.action), {
			daoEvent : daoEvent
		});

		var $receiver = dic[objectType];

		if (!$receiver) {
			dic[objectType] = $receiver = $("<div class='" + objectType + "'></div>");
			$receiversRoot.append($receiver);
		}
		// trigger with the event.type == action
		$receiver.trigger(evt);
		
		// in the case of a "remove" event, we need to check if the $receiver did not get removed, 
		// otherwise, we need to add it back.
		if(evt.type === "remove" && $receiversRoot.find("." + objectType).size() == 0 && $receiver){
			$receiversRoot.append($receiver);
		}

		// trigger _ALL_ action in case there are some events registered for all event
		evt.type = _ALL_;
		$receiver.trigger(evt);
	}

	// --------- /Internal Utilities For Dao Events --------- //

	// --------- brite.dao.onDao --------- //
	var $daoDao = $("<div></div>");
	// dictionary of {objectType:$dataEventReceiver}

	var onDaoReceiverDic = {};
	/**
	 * This will trigger on any DAO calls before the dao action is completed (for
	 * asynch daos), hence, the resultPromise property of the daoEvent.
	 *
	 * @param objectTypes       e.g., "User, Task" (null for any)
	 * @param actions            e.g., "create, list, get" (null for any)
	 * @param listenerFunction  The function to be called with the daoEvent
	 *            listenerFunction(event) with event.daoEvent as
	 *            daoEvent.action
	 *            daoEvent.entityType
	 *            daoEvent.resultPromise
	 *
	 */
	brite.dao.onDao = function(objectTypes, actions, listenerFunction, namespace) {
		var map = buildDaoOnEventParamMap(arguments);
		$daoDao.on(map.actions, map.objectTypes, map.func);
		return map.namespace;
	};


	brite.dao.offDao = function(namespace) {
		$daoDao.off("." + namespace);
	};

	function _triggerOnDao(entityType, action, resultPromise) {
		var daoEvent = {
			entityType : entityType,
			action : action,
			resultPromise : resultPromise
		};

		_triggerDaoEvent(onDaoReceiverDic, $daoDao, entityType, daoEvent);
	}

	// --------- /brite.dao.onDao --------- //

	// --------- brite.dao.onResult --------- //
	var $daoResult = $("<div></div>");
	// dictionary of {objectType:$dataEventReceiver}
	var onResultReceiverDic = {};

	/**
	 * This will trigger when the dao resolve the result of a particular DAO call.
	 * This will not trigger in case of a dao failure.
	 *
	 * @param objectTypes       e.g., "User, Task" (null for any)
	 * @param actions           e.g., "create, list, get" 
	 * @param listenerFunction  The function to be called with the daoEvent
	 *            listenerFunction(daoEvent)
	 *            daoEvent.action
	 *            daoEvent.objectType
	 *            daoEvent.objectId
	 *            daoEvent.data
	 *            daoEvent.opts
	 *            daoEvent.result
	 */
	brite.dao.onResult = function(objectTypes, actions, listenerFunction, namespace) {
		var map = buildDaoOnEventParamMap(arguments);
		$daoResult.on(map.actions, map.objectTypes, map.func);
		return map.namespace;
	};


	brite.dao.offResult = function(namespace) {
		$daoResult.off("." + namespace);
	};

	function _triggerOnResult(entityType, action, result) {
		var daoEvent = {
			entityType: entityType,
			action : action,
			result : result
		};

		_triggerDaoEvent(onResultReceiverDic, $daoResult, entityType, daoEvent);
	}

	// --------- /brite.dao.onResult --------- //

	// --------- Brite.dao.onDataChange --------- //
	var $daoDataChange = $("<div></div>");

	// dictionary of {objectType:$dataEventReceiver}
	var dataChangeReceiverDic = {};

	/**
	 * This trigger on data change event only (like "create, update, remove") and not on others. For other binding,
	 * use the brite.dao.onResult which will trigger anytime
	 *
	 * @param {String} namespace: the namespace for this event.
	 * @param {String} objectTypes: the object types e.g., "User, Task" (null for any object type);
	 * @param {String} actions: this dao action names e.g., "create, update" 
	 */
	brite.dao.onDataChange = function(objectTypes, actions, func, namespace) {
		var map = buildDaoOnEventParamMap(arguments);
		$daoDataChange.on(map.actions, map.objectTypes, map.func);
		return map.namespace;
	};


	brite.dao.offDataChange = function(namespace) {
		$daoDataChange.off("." + namespace);
	};


	brite.triggerDataChange = function(entityType, action, result) {
		var daoEvent = {
			entityType : entityType,
			action : action,
			result : result
		};

		_triggerDaoEvent(dataChangeReceiverDic, $daoDataChange, entityType, daoEvent);
	};

	// --------- /Brite.dao.onDataChange --------- //
	
	brite.dao.offAny = function(namespace){
		brite.dao.offResult(namespace);
		brite.dao.offDao(namespace);
		brite.dao.offDataChange(namespace);
	};

	/**
	 * Wrap with a deferred object if the obj is not a deferred itself.
	 */
	function wrapWithDeferred(obj) {
		//if it is a deferred, then, trust it, return it.
		if (obj && $.isFunction(obj.promise)) {
			return obj;
		} else {
			var dfd = $.Deferred();
			dfd.resolve(obj);
			return dfd;
		}
	}

})(jQuery);
// --------- /DAO Support --------- //



// --------- bEntity --------- //
(function($) {

	/**
	 * Return the bEntity {id,type,name,$element} (or a list of such) of the closest html element matching entity type in the data-entity.
	 * 
	 * The return value is like: 
	 * 
	 * .type     will be the value of the attribute data-entity 
	 * .id       will be the value of the data-entity-id
	 * .name     (optional) will be the value of the data-entity-name
	 * .$el      will be the $element containing the matching data-entity attribute
	 *  
	 * If no entityType, then, return the first entity of the closest html element having a data-b-entity. <br />
	 * 
	 * $element.bEntity("User"); // return the closest entity with data-entity="User"
	 * $element.bEntity(">children","Task"); // return all the data-entity="Task" children from this $element.  
	 * $element.bEntity(">first","Task"); // return the first child entity matching data-entity="Task"
	 * 
	 * TODO: needs to implement the >children and >first
	 * 
	 * @param {String} entity type (optional) the object 
	 * @return null if not found, the first found entity with {id,type,name,$element}.
	 */
	$.fn.bEntity = function(entityType) {

		var i, result = null;
		// iterate and process each matched element
		this.each(function() {
			// ignore if we already found one
			if (result === null){
				var $this = $(this);
				var $sObj;
				if (entityType) {
					$sObj = $this.closest("[data-entity='" + entityType + "']");
				} else {
					$sObj = $this.closest("[data-entity]");
				}
				if ($sObj.length > 0) {
					result = {
						type : $sObj.attr("data-entity"),
						id : $sObj.attr("data-entity-id"),
						$el : $sObj
					};
					var name = $sObj.attr("data-entity-name");
					if (typeof name !== "undefined"){
						result.name = name;
					}
				}
			}
		});
		
		return result;
		
	};

})(jQuery);

// ------ /bEntity ------ //

// ------ LEGACY jQuery DAO Helper ------ //
(function($) {

	/**
	 * Return the objRef {id,type,$element} (or a list of such) of the closest html element matching the objType match the data-obj_type.<br />
	 * If no objType, then, return the first objRef of the closest html element having a data-obj_type. <br />
	 *
	 * @param {String} objType (optional) the object table
	 * @return null if not found, single object with {id,type,$element} if only one jQuery object, a list of such if this jQuery contain multiple elements.
	 */
	//@Deprecated
	$.fn.bObjRef = function(objType) {
		var resultList = [];

		var obj = null;
		// iterate and process each matched element
		this.each(function() {
			var $this = $(this);
			var $sObj;
			if (objType) {
				$sObj = $this.closest("[data-obj_type='" + objType + "']");
			} else {
				$sObj = $this.closest("[data-obj_type]");
			}
			if ($sObj.length > 0) {
				var objRef = {
					type : $sObj.attr("data-obj_type"),
					id : $sObj.attr("data-obj_id"),
					$element : $sObj
				};
				resultList.push(objRef);
			}
		});

		if (resultList.length === 0) {
			return null;
		} else if (resultList.length === 1) {
			return resultList[0];
		} else {
			return resultList;
		}

	};

})(jQuery);

// ------ /LEGACY jQuery DAO Helper ------ //

var brite = brite || {};

/**
 * @namespace brite.event convenient touch/mouse event helpers.
 */
brite.event = brite.event || {};

// ------ brite event helpers ------ //
(function($){
	var hasTouch = brite.ua.hasTouch();
	/**
	 * if it is a touch device, populate the event.pageX and event.page& from the event.touches[0].pageX/Y
	 * @param {jQuery Event} e the jquery event object 
	 */
	brite.event.fixTouchEvent = function(e){
			if (hasTouch) {
					var oe = e.originalEvent;
					if (oe.touches.length > 0) {
							e.pageX = oe.touches[0].pageX;
							e.pageY = oe.touches[0].pageY;
					}
			}
			
			return e;
	};
		
	/**
	 * Return the event {pageX,pageY} object for a jquery event object (will take the touches[0] if it is a touch event)
	 * @param {jQuery Event} e the jquery event object
	 */
	brite.event.eventPagePosition = function(e){
		var pageX, pageY;
		if (e.originalEvent && e.originalEvent.touches){
			pageX = e.originalEvent.touches[0].pageX;
			pageY = e.originalEvent.touches[0].pageY;
		}else{
			pageX = e.pageX;
			pageY = e.pageY;
		}
		return {
			pageX: pageX,
			pageY: pageY
		};
	};

})(jQuery);
// ------ /brite event helpers ------ //

// ------ transition helper ------ //
;(function($){
	
	/**
	 * simple and convenient methods to perform css3 animations (takes care of the css prefix)
	 * opts.transition: this will be the transition value added as css style (e.g.,: "all 0.3s ease;")
	 * opts.transform: the css transform instruction (e.g.,: "scale(.01)")
	 * opts.onTimeout: (optional, default false). If true or >= 0, then the transformation will be performed on timeout)  
	 */
	
	$.fn.bTransition = function(opts) {
		
		return this.each(function() {
			var $this = $(this);
			var timeout = -1;
			if (typeof opts.onTimeout === "boolean"){
				timeout = (opts.onTimeout)?0:-1;
			}else if (typeof opts.onTimeout === "number"){
				timeout = opts.onTimeout;
			}
			if (timeout > -1){
				setTimeout(function(){
					performTransition($this,opts);
				},timeout);
			}else{
				performTransition($this,opts);
			} 
			// add the transition
		});
	};
	
	// helper function
	function performTransition($this,opts){
		$this.css("transition",opts.transition);
		$this.css("transform",opts.transform);
	}
})(jQuery);  
// ------ /transition helper ------ //

// ------ /brite special events ------ //
(function($){
	
	// to prevent other events (i.e., btap) to trigger when dragging.
	var _dragging = false;
	
	var mouseEvents = {
			start: "mousedown",
			move: "mousemove",
			end: "mouseup"
	};

	var touchEvents = {
			start: "touchstart",
			move: "touchmove",
			end: "touchend"
	};
	
	function getTapEvents(){
		if (brite.ua.hasTouch()){
			return touchEvents;
		}else{
			return mouseEvents;
		}
	}  
	
	// --------- btap & btaphold --------- //
	$.event.special.btap = {
		add: btabAddHandler
	}; 
	
	$.event.special.btaphold = {
		add: btabAddHandler
	}; 
	
	function btabAddHandler(handleObj) {

		var tapEvents = getTapEvents();

		$(this).on(tapEvents.start, handleObj.selector, function(event) {
			var elem = this;
			var $elem = $(elem);
			
			var origTarget = event.target, startEvent = event, timer;
			
			function handleEnd(event){
				clearAll();
				if (event.target === origTarget && !_dragging){
					// use event.eventPhase because we should ignore bubbling event when triggering this meta event
					var ep = event.eventPhase;
					var pass = (elem === origTarget && ep === 2) || (elem !== origTarget && ep === 3);
					if (pass && !event.originalEvent.b_processed){
						// we take the pageX and pageY of the start event (because in touch, touchend does not have pageX and pageY)
						brite.event.fixTouchEvent(startEvent);
						triggerCustomEvent(elem, event,{type:"btap",pageX: startEvent.pageX,pageY: startEvent.pageY});
						// flag this originalEvent as processed
						// Note: this allow to prevent multiple triggering without having to use the stopPropagation which will be too
						//       destructive for other event handlers
						event.originalEvent.b_processed = true;
					}
				}
			}
			
			function clearAll(){
				clearTimeout(timer);
				$elem.off(tapEvents.end,handleEnd);
			}  
			
			$elem.on(tapEvents.end,handleEnd);
			
			timer = setTimeout(function() {
				if (!_dragging){
					brite.event.fixTouchEvent(startEvent);
					triggerCustomEvent( elem, startEvent,{type:"btaphold"});
				}
			}, 750 );
		});

	}  


	linkSpecialEventsTo(["btaphold"],"btap");
	
	// --------- /btap & btaphold --------- //
	
	
	// --------- bdrag* --------- //
	var BDRAGSTART="bdragstart",BDRAGMOVE="bdragmove",BDRAGEND="bdragend";
	
	// Note: those below are part of the drop events, but are not supported yet.
	//       Need to think some more.
	var BDRAGENTER="bdragenter",BDRAGOVER="bdragover",BDRAGLEAVE="bdragleave",BDROP="bdrop";
	
	var dragThreshold = 5;
	

	$.event.special[BDRAGSTART] = {
		add : bdragAddHandler
	};

	$.event.special[BDRAGMOVE] = {
		add : bdragAddHandler
	};

	$.event.special[BDRAGEND] = {
		add : bdragAddHandler
	};
	
	function bdragAddHandler(handleObj) {
		
		var tapEvents = getTapEvents();

		$(this).on(tapEvents.start, handleObj.selector, function(event) {
			var elem = this;
			var $elem = $(this);
			var dragStarted = false;
			var startEvent = event;
			var startPagePos = brite.event.eventPagePosition(startEvent);
			var origTarget = event.target;
			var $origTarget = $(origTarget);
			
			var $document = $(document);
			var uid = "_" + brite.uuid(7);
			
			// drag move (and start)
			$document.on(tapEvents.move + "." + uid,function(event){
				var bextra;
				var currentPagePos = brite.event.eventPagePosition(event);
				// fix a bug on Chrome that always change the cursor to text
				$("body").css("-webkit-user-select","none");
				
				if (!dragStarted){
					if(Math.abs(startPagePos.pageX - currentPagePos.pageX) > dragThreshold || Math.abs(startPagePos.pageY - currentPagePos.pageY) > dragThreshold) {
						dragStarted = true;
						_dragging = true;
						$origTarget.data("bDragCtx", {});
						bextra = buildDragExtra(event, $origTarget, BDRAGSTART);
						triggerCustomEvent( origTarget, event,{type:BDRAGSTART,target:origTarget,bextra:bextra});  
						
						event.stopPropagation();
						event.preventDefault();
						
					}
				}
				
				if(dragStarted) {
					bextra = buildDragExtra(event, $origTarget, BDRAGMOVE);
					triggerCustomEvent( origTarget, event,{type:BDRAGMOVE,target:origTarget,bextra:bextra});
					event.stopPropagation();
					event.preventDefault();
				}
			});
			
			// drag end
			$document.on(tapEvents.end + "." + uid, function(event){
				// chrome fix cleanup (remove the hack)
				$("body").css("-webkit-user-select","");
				if (dragStarted){
					var bextra = buildDragExtra(event, $origTarget, BDRAGEND);
					triggerCustomEvent( origTarget, event,{type:BDRAGEND,target:origTarget,bextra:bextra});
					event.stopPropagation();
					event.preventDefault();            
				}  
				
				$document.off("." + uid);
				_dragging = false;
			});
					
		});
	}
	
	
	/**
	 * Build the extra event info for the drag event. 
	 */
	function buildDragExtra(event,$elem,dragType){
		brite.event.fixTouchEvent(event);
		var hasTouch = brite.ua.hasTouch();
		var extra = {
			eventSource: event,
			pageX: event.pageX,
			pageY: event.pageY      
		};
		
		var oe = event.originalEvent;
		if (hasTouch){
			extra.touches = oe.touches;
		}
		
		var bDragCtx = $elem.data("bDragCtx");
		
		if (dragType === BDRAGSTART){
			bDragCtx.startPageX = extra.startPageX = extra.pageX;
			bDragCtx.startPageY = extra.startPageY = extra.pageY;
			
			bDragCtx.lastPageX = bDragCtx.startPageX = extra.startPageX;
			bDragCtx.lastPageY = bDragCtx.startPageY = extra.startPageY;
		}else if (dragType === BDRAGEND){
			// because, on iOs, the touchEnd event does not have the .touches[0].pageX
			extra.pageX = bDragCtx.lastPageX;
			extra.pageY = bDragCtx.lastPageY;
		}
		
		extra.startPageX = bDragCtx.startPageX;
		extra.startPageY = bDragCtx.startPageY;
		extra.deltaX = extra.pageX - bDragCtx.lastPageX;
		extra.deltaY = extra.pageY - bDragCtx.lastPageY;
		
		bDragCtx.lastPageX = extra.pageX;
		bDragCtx.lastPageY = extra.pageY;
		return extra;
	}
	// --------- /bdrag* --------- //
	
	
	
	// --------- btransitionend --------- //
	// Note: even if jQuery 1.8 add the prefix, it still does not normalize the transitionend event.
	$.event.special.btransitionend = {

		setup : function(data, namespaces) {
			var eventListener = "transitionend";
			if (this.addEventListener){
				if (!brite.ua.browser.mozilla){
					eventListener = brite.ua.cssVarPrefix().toLowerCase() + "TransitionEnd";
				}
				this.addEventListener(eventListener,function(event){
					triggerCustomEvent(this,event,{type:"btransitionend"});
				});
				
			}else{
				// old browser, just trigger the event since transition should not be supported anyway
				triggerCustomEvent(this,jQuery.Event("btransitionend"),{type:"btransitionend"});
			}
		 

		}

	};   
	// --------- /btransitionend --------- //
	
	// --------- Event Utilities --------- //
	
	// Link
	function linkSpecialEventsTo(eventNames,eventRef){
		$.each(eventNames,function(idx,val){
			$.event.special[ val ] = {
				setup: function() {
					$( this ).bind( eventRef, $.noop );
				}
			};      
		});
	}
		
	function triggerCustomEvent( elem, nativeEvent, override ) {
		var newEvent = jQuery.extend(
			new jQuery.Event(),
			nativeEvent,override
		);
		$(elem).trigger(newEvent);    
	}
	// --------- /Event Utilities --------- //  
		
})(jQuery);
// ------ /brite special events ------ //
