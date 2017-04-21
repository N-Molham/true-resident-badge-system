/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c){b(function(){
// POI badge checklist
!function(){var a=b("#checklist-form");
// repeatable init
return 0!==a.length&&void a.find(".checklist-repeatable").repeatable_item()}(),
// badge steps
function(){
// vars
var a=b("#steps_list"),c=".select-trigger-type",d=".true-resident-step-condition",e=".true-resident-tax-type",f={},g=null;
// listing search
a.on("keydown","input.true-resident-autocomplete",function(a){var c=b(a.currentTarget);
// search bar autocomplete
return!!c.hasClass("ui-autocomplete-input")||void c.autocomplete({minLength:2,focus:function(a){
// prevent default behaviour
a.preventDefault()},source:function(a,c){
// vars
var d=a.term.trim(),e=""===d||d.length<1;if(g&&
// stop the last request
g.abort(),!e)
// load from cache
// AJAX request
return d in f?void c(f[d]):void(g=b.post(trbs_triggers.urls.get_listings,{search_keywords:d,page:1,search_location:"",per_page:30,orderby:"featured",order:"DESC",form_data:"",trsc_autocomplete:!0,show_pagination:!1},function(a){if(a.success){var b=[];for(var e in a.data)b.push({label:a.data[e].text,value:a.data[e].id});
// cache it for later
f[d]=b,c(b)}}))}})}),
// when the trigger type changes
a.on("change trbs-change",c,function(a){var c=b(a.currentTarget);
// look for the linked fields
c.siblings(d).hide().filter('[data-toggle="'+c.val()+'"]').show()}),a.on("change trbs-change",e,function(a){if(a.currentTarget.value.length>0){
// linked terms field
var c=b(a.currentTarget),d=[],e=c.siblings(".spinner"),f=c.siblings(".true-resident-term"),g=parseInt(f.data("value"));
// disable the field and empty it
f.empty().prop("disabled",!0),c.prop("disabled",!0),e.addClass("is-active"),b.post(ajaxurl,{action:"trbs_get_taxonomy_terms",taxonomy:a.currentTarget.value},function(a){if(a.success){for(var b in a.data)a.data.hasOwnProperty(b)&&
// add the new option
d.push('<option value="'+b+'"'+(g===parseInt(b)?" selected":"")+">"+a.data[b]+"</option>");
// fill in the options
f.html(d)}else alert(a.data)},"json").always(function(){
// re-enable the field
f.prop("disabled",!1),c.prop("disabled",!1),e.removeClass("is-active")})}}),
// trigger change on load
b([c,e].join(", ")).trigger("trbs-change"),
// Inject our custom step details into the update step action
b(document).on("update_step_data",function(a,b,c){
// get the give trigger type related conditions
c.find(d+'[data-toggle="'+b.trigger_type+'"]').each(function(a,c){
// setup condition name as step data property with input value
b[c.getAttribute("name")]=c.value})})}()})}(window,jQuery);