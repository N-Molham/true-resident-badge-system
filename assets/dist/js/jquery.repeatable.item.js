/*!
 * Repeatable list item 1.6.6 (http://n-molham.github.io/jquery.repeatable.item/)
 * Copyright 2014 Nabeel Molham (http://nabeel.molham.me).
 * Licensed under MIT License (http://opensource.org/licenses/MIT)
 */
!function(a){"use strict";jQuery(function(a){a.fn.repeatable_item=function(b){
// check if doT.js template engine is available
if("object"!=typeof doT)throw"Repeatable Exception: doT.js Template engine not found, click here https://github.com/olado/doT";
// default events handler
b=a.extend({init:function(){},completed:function(){},new_item:function(){},removed:function(){}},b);
// plugins methods
var c={/**
				 * Clean final item layout from placeholders
				 *
				 * @param {string} layout
				 * @return {string}
				 */
clean_template_holders:function(a){
// index/value cleanup
var b=a.replace(/\{[a-zA-Z0-9_\-]+\}/g,"");
// clean template placeholders
return b=b.replace(doT.templateSettings.evaluate,""),b=b.replace(doT.templateSettings.interpolate,""),b=b.replace(doT.templateSettings.encode,""),b=b.replace(doT.templateSettings.use,""),b=b.replace(doT.templateSettings.define,""),b=b.replace(doT.templateSettings.conditional,""),b=b.replace(doT.templateSettings.iterate,"")},/**
				 * Add new list item
				 *
				 * @param {Object} $list
				 * @param {Number} index
				 * @param {any} data
				 * @return void
				 */
add_item:function(d,e,f){f=f||!1,
// check empty item
d.settings.is_empty&&(d.settings.is_empty=!1,d.find(".repeatable-empty").remove());
// add new index
var g;
// check data
switch(typeof f){case"boolean":g=d.item_template_dot(d.settings.defaultItem);break;case"object":
// refill fields data template
g=d.item_template_dot(f);break;default:
// fill in with value
g=d.item_template.outerHTML().replace(new RegExp("{"+d.settings.valueKeyName+"}","g"),f)}
// add new index
g=g.replace(new RegExp("{"+d.settings.indexKeyName+"}","g"),e),
// clear placeholder left overs
g=c.clean_template_holders(g);
// replace HTML and append to list
var h=a(g).appendTo(d);
// index increment
d.settings.startIndex=parseInt(e)+1,
// trigger event: add new
d.trigger("repeatable-new-item",[d,h,e,f]),b.new_item(d,h,e,f)}};
// chaining
// element loop
return this.each(function(d,e){var f=a(e);
// trigger event: initialize
f.trigger("repeatable-init"),b.init(f),
// settings
f.settings=a.extend({startIndex:0,templateSelector:"",indexKeyName:"index",valueKeyName:"value",addButtonLabel:"Add New",addButtonClass:"btn btn-primary",wrapperClass:"repeatable-wrapper",confirmRemoveMessage:"Are Your Sure ?",confirmRemove:"no",emptyListMessage:"<li>No Items Found</li>",defaultItem:{},values:[],is_empty:!0},f.data()),
// wrap list
f.wrap('<div class="'+f.settings.wrapperClass+'" />');
// index parsing
var g=parseInt(f.settings.startIndex);
// repeatable item template
if(""==f.settings.templateSelector)
// use internal template
f.item_template=f.find("> [data-template=yes]").removeAttr("data-template").remove();else
// use external template from query selector
try{f.item_template=a(a(f.settings.templateSelector).html())}catch(h){throw"Repeatable Exception: Invalid item template selector <"+f.settings.templateSelector+">"}if(1!==f.item_template.size())
// throw exception cause the template item not set
throw"Repeatable Exception: Template item not found.";
// add values if any
if(
// compiled template function
f.item_template_dot=doT.template(f.item_template.outerHTML()),
// remove selector
f.item_template.remove_selector=f.item_template.prop("tagName").toLowerCase(),f.item_template.is("[class]")&&(
// specified more by class
f.item_template.remove_selector+='[class*="'+f.item_template.prop("className")+'"]'),
// create add button and wrap if in p tag
f.add_new_btn=a('<p class="add-wrapper"><a href="#" class="'+f.settings.addButtonClass+'">'+f.settings.addButtonLabel+"</a></p>").insertAfter(f).on("click repeatable-add-click","a",function(a){a.preventDefault(),
// add new item
c.add_item(f,f.settings.startIndex)}),"object"==typeof f.settings.values){
// loop items for appending indexes
var i=[];if(a.each(f.settings.values,function(a,b){"undefined"!=typeof b.order_index&&(
// use index from item data if exists
a=parseInt(b.order_index)),i.push(a),
// add new item
c.add_item(f,a,b),f.settings.is_empty=!1}),i.length){
// calculate next index
var j=Math.max.apply(Math,i);f.settings.startIndex=(j>g?j:g)+1}}f.settings.is_empty&&f.settings.emptyListMessage.length&&("item"==f.settings.emptyListMessage?
// empty list label if is set
f.add_new_btn.trigger("repeatable-add-click"):
// empty list label if is set
f.append(a(f.settings.emptyListMessage).addClass("repeatable-empty"))),
// remove button
f.on("click","[data-remove=yes]",function(c){
// confirm first
if(c.preventDefault(),"yes"==f.settings.confirmRemove&&!confirm(f.settings.confirmRemoveMessage))return!1;
// query the item to remove > remove it
var d=a(c.currentTarget).closest(f.item_template.remove_selector).remove();
// trigger event: item removed
f.trigger("repeatable-removed",[f,d]),b.removed(f,d)}),
// trigger event: initializing completed
f.trigger("repeatable-completed",[f]),b.completed(f)}),this},a.fn.outerHTML||(/**
			 * Get element whole HTML layout
			 *
			 * @returns {*|jQuery}
			 */
a.fn.outerHTML=function(){return a("<div />").append(this.eq(0).clone()).html()})})}(window);