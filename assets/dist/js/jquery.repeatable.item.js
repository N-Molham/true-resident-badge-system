/*!
 * Repeatable list item 1.6.6 (http://n-molham.github.io/jquery.repeatable.item/)
 * Copyright 2014 Nabeel Molham (http://nabeel.molham.me).
 * Licensed under MIT License (http://opensource.org/licenses/MIT)
 */
!function(a,b){"use strict";jQuery(function(a){a.fn.repeatable_item=function(c){
// check if doT.js template engine is available
if("object"!=typeof doT)throw"Repeatable Exception: doT.js Template engine not found, click here https://github.com/olado/doT";
// default events handler
c=a.extend({init:function(){},completed:function(){},new_item:function(){},removed:function(){}},c);
// plugins methods
var d={/**
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
add_item:function(b,e,f){f=f||!1,
// check empty item
b.settings.is_empty&&(b.settings.is_empty=!1,b.find(".repeatable-empty").remove());
// add new index
var g;
// check data
switch(typeof f){case"boolean":g=b.item_template_dot(b.settings.defaultItem);break;case"object":
// refill fields data template
g=b.item_template_dot(f);break;default:
// fill in with value
g=b.item_template.outerHTML().replace(new RegExp("{"+b.settings.valueKeyName+"}","g"),f)}
// add new index
g=g.replace(new RegExp("{"+b.settings.indexKeyName+"}","g"),e),
// clear placeholder left overs
g=d.clean_template_holders(g);
// replace HTML and append to list
var h=a(g).appendTo(b);
// index increment
b.settings.startIndex=parseInt(e)+1,
// trigger event: add new
b.trigger("repeatable-new-item",[b,h,e,f]),c.new_item(b,h,e,f)}};
// chaining
// element loop
return this.each(function(e,f){var g=a(f);
// trigger event: initialize
g.trigger("repeatable-init"),c.init(g),
// settings
g.settings=a.extend({startIndex:0,templateSelector:"",indexKeyName:"index",valueKeyName:"value",addButtonLabel:"Add New",addButtonClass:"btn btn-primary",wrapperClass:"repeatable-wrapper",confirmRemoveMessage:"Are Your Sure ?",confirmRemove:"no",emptyListMessage:"<li>No Items Found</li>",defaultItem:{},values:[],valuesOrder:[],is_empty:!0},g.data()),
// wrap list
g.wrap('<div class="'+g.settings.wrapperClass+'" />');
// index parsing
var h=parseInt(g.settings.startIndex);
// repeatable item template
if(""==g.settings.templateSelector)
// use internal template
g.item_template=g.find("> [data-template=yes]").removeAttr("data-template").remove();else
// use external template from query selector
try{g.item_template=a(a(g.settings.templateSelector).html())}catch(i){throw"Repeatable Exception: Invalid item template selector <"+g.settings.templateSelector+">"}if(1!==g.item_template.size())
// throw exception cause the template item not set
throw"Repeatable Exception: Template item not found.";
// add values if any
if(
// compiled template function
g.item_template_dot=doT.template(g.item_template.outerHTML()),
// remove selector
g.item_template.remove_selector=g.item_template.prop("tagName").toLowerCase(),g.item_template.is("[class]")&&(
// specified more by class
g.item_template.remove_selector+='[class*="'+g.item_template.prop("className")+'"]'),
// create add button and wrap if in p tag
g.add_new_btn=a('<p class="add-wrapper"><a href="#" class="'+g.settings.addButtonClass+'">'+g.settings.addButtonLabel+"</a></p>").insertAfter(g).on("click repeatable-add-click","a",function(a){a.preventDefault(),
// add new item
d.add_item(g,g.settings.startIndex)}),"object"==typeof g.settings.values){
// loop items for appending indexes
var j=[],k=a.isArray(g.settings.valuesOrder)&&g.settings.valuesOrder.length;if(a.each(k?g.settings.valuesOrder:g.settings.values,function(a,c){k&&g.settings.values!==b&&(
// load item based on the custom order
a=c,c=g.settings.values[a]),c.order_index!==b&&(
// use index from item data if exists
a=parseInt(c.order_index)),j.push(a),
// add new item
d.add_item(g,a,c),g.settings.is_empty=!1}),j.length){
// calculate next index
var l=Math.max.apply(Math,j);g.settings.startIndex=(l>h?l:h)+1}}g.settings.is_empty&&g.settings.emptyListMessage.length&&("item"===g.settings.emptyListMessage?
// empty list label if is set
g.add_new_btn.trigger("repeatable-add-click"):
// empty list label if is set
g.append(a(g.settings.emptyListMessage).addClass("repeatable-empty"))),
// remove button
g.on("click","[data-remove=yes]",function(b){
// confirm first
if(b.preventDefault(),"yes"===g.settings.confirmRemove&&!confirm(g.settings.confirmRemoveMessage))return!1;
// query the item to remove > remove it
var d=a(b.currentTarget).closest(g.item_template.remove_selector).remove();
// trigger event: item removed
g.trigger("repeatable-removed",[g,d]),c.removed(g,d)}),
// trigger event: initializing completed
g.trigger("repeatable-completed",[g]),c.completed(g)}),this},a.fn.outerHTML||(/**
			 * Get element whole HTML layout
			 *
			 * @returns {*|jQuery}
			 */
a.fn.outerHTML=function(){return a("<div />").append(this.eq(0).clone()).html()})})}(window);