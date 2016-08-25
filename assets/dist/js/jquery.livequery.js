/*! jquery.livequery - v1.3.6 - 2013-08-26
 * Copyright (c)
 *  (c) 2010, Brandon Aaron (http://brandonaaron.net)
 *  (c) 2012 - 2013, Alexander Zaytsev (http://hazzik.ru/en)
 * Dual licensed under the MIT (MIT_LICENSE.txt)
 * and GPL Version 2 (GPL_LICENSE.txt) licenses.
 */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a,b){function c(a,b,c,d){return!(a.selector!=b.selector||a.context!=b.context||c&&c.$lqguid!=b.fn.$lqguid||d&&d.$lqguid!=b.fn2.$lqguid)}a.extend(a.fn,{livequery:function(b,e){var f,g=this;
// Contnue the chain
// See if Live Query already exists
// Create new Live Query if it wasn't found
// Make sure it is running
// Run it immediately for the first time
return a.each(d.queries,function(a,d){if(c(g,d,b,e))
// Found the query, exit the each loop
return(f=d)&&!1}),f=f||new d(g.selector,g.context,b,e),f.stopped=!1,f.run(),g},expire:function(b,e){var f=this;
// Continue the chain
// Find the Live Query based on arguments and stop it
return a.each(d.queries,function(a,g){c(f,g,b,e)&&!f.stopped&&d.stop(g.id)}),f}});var d=a.livequery=function(b,c,e,f){var g=this;
// Return the Live Query
// The id is the index of the Live Query in $.livequiery.queries
// Mark the functions for matching later on
return g.selector=b,g.context=c,g.fn=e,g.fn2=f,g.elements=a([]),g.stopped=!1,g.id=d.queries.push(g)-1,e.$lqguid=e.$lqguid||d.guid++,f&&(f.$lqguid=f.$lqguid||d.guid++),g};d.prototype={stop:function(){var b=this;
// Short-circuit if stopped
b.stopped||(b.fn2&&
// Call the second function for all matched elements
b.elements.each(b.fn2),
// Clear out matched elements
b.elements=a([]),
// Stop the Live Query from running until restarted
b.stopped=!0)},run:function(){var b=this;
// Short-circuit if stopped
if(!b.stopped){var c=b.elements,d=a(b.selector,b.context),e=d.not(c),f=c.not(d);
// Set elements to the latest set of matched elements
b.elements=d,
// Call the first function for newly matched elements
e.each(b.fn),
// Call the second function for elements no longer matched
b.fn2&&f.each(b.fn2)}}},a.extend(d,{guid:0,queries:[],queue:[],running:!1,timeout:null,registered:[],checkQueue:function(){if(d.running&&d.queue.length)
// Run each Live Query currently in the queue
for(var a=d.queue.length;a--;)d.queries[d.queue.shift()].run()},pause:function(){
// Don't run anymore Live Queries until restarted
d.running=!1},play:function(){
// Restart Live Queries
d.running=!0,
// Request a run of the Live Queries
d.run()},registerPlugin:function(){a.each(arguments,function(b,c){
// Short-circuit if the method doesn't exist
if(a.fn[c]&&!(a.inArray(c,d.registered)>0)){
// Save a reference to the original method
var e=a.fn[c];
// Create a new method
a.fn[c]=function(){
// Call the original method
var a=e.apply(this,arguments);
// Return the original methods result
// Request a run of the Live Queries
return d.run(),a},d.registered.push(c)}})},run:function(c){c!==b?
// Put the particular Live Query in the queue if it doesn't already exist
a.inArray(c,d.queue)<0&&d.queue.push(c):
// Put each Live Query in the queue if it doesn't already exist
a.each(d.queries,function(b){a.inArray(b,d.queue)<0&&d.queue.push(b)}),
// Clear timeout if it already exists
d.timeout&&clearTimeout(d.timeout),
// Create a timeout to check the queue and actually run the Live Queries
d.timeout=setTimeout(d.checkQueue,20)},stop:function(c){c!==b?
// Stop are particular Live Query
d.queries[c].stop():
// Stop all Live Queries
a.each(d.queries,d.prototype.stop)}}),
// Register core DOM manipulation methods
d.registerPlugin("append","prepend","after","before","wrap","attr","removeAttr","addClass","removeClass","toggleClass","empty","remove","html","prop","removeProp"),
// Run Live Queries when the Document is ready
a(function(){d.play()})});