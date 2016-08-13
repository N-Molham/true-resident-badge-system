/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c){b(function(){
// vars
var a=".select-trigger-type",c=".true-resident-step-condition";
// when the trigger type changes
b("#steps_list").on("change trbs-change",a,function(a){var d=b(a.currentTarget);
// hide all available condition except for the linked one
d.siblings(c).hide().filter('[data-toggle="'+d.val()+'"]').show()}),
// trigger change on load
b(a).trigger("trbs-change"),
// Inject our custom step details into the update step action
b(document).on("update_step_data",function(a,b,d){
// get the give trigger type related conditions
d.find(c+'[data-toggle="'+b.trigger_type+'"]').each(function(a,c){
// setup condition name as step data property with input value
b[c.getAttribute("name")]=c.value})})})}(window,jQuery);