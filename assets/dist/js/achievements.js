/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){/**
		 * Check if there is logged in user or not
		 *
		 * @return {boolean}
		 */
function a(){return Boolean(listify_child_overlays&&"is_user_logged_in"in listify_child_overlays?listify_child_overlays.is_user_logged_in:trbs_badges.is_logged_in)}var d=b("#badgeos-achievements-container");if(!(d.length<1)){
// vars
var e="",f=c.body.className,g=f.indexOf("single-job_listing")>-1&&f.indexOf("single")>-1;
// badges with challenges checklist
!function(){if(!1!==g){
// compile checklist template
var c=doT.template(b("#trbs-checklist-template").html()),f=b("#trbs-badges-challenges"),h=[];
// badges click
d.on("click",".badgeos-achievements-challenges-item",function(d){var g=b(d.currentTarget),i=g.data("steps-data"),j=g.data("id");
// reset
h=[];for(var k in i)if(i.hasOwnProperty(k)){
// current step
var l=b.extend({},i[k],{step_id:k,badge_id:j});"challenges_checklist_listing_id"in l&&l.challenges_checklist_listing_id.toString()===e.toString()&&"challenges_checklist"in l&&!b.isEmptyObject(l.challenges_checklist)&&
// render badge's step checklist
h.push(c(l))}
// user needs to login first
// output checklist(s)
return h.length<1||(!1===a()?(b("#secondary-nav-menu").find(".overlay-login").trigger("tr-click"),!0):void f.html(h.join("")))})}}(),
// badges filter
function(){var a=b("#achievements_list_filter");if(a.length)for(option_value in trbs_badges.filter_labels)trbs_badges.filter_labels.hasOwnProperty(option_value)&&a.find('option[value="'+option_value+'"]').text(trbs_badges.filter_labels[option_value])}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()})}(),
// badges for current open listing
function(){if(!1!==g){var a=f.match(/postid-\d+/i);null!==a&&(
// loaded listing ID in the current page
e=parseInt(a[0].replace("postid-","")),isNaN(e)||b.ajaxSetup({data:{trbs_listing_id:e}}))}}()}})}(window,jQuery,document);