/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){/**
		 * Check if there is logged in user or not
		 *
		 * @return {boolean}
		 */
function e(){return Boolean(listify_child_overlays&&"is_user_logged_in"in listify_child_overlays?listify_child_overlays.is_user_logged_in:trbs_badges.is_logged_in)}/**
		 * Get Query string in url
		 *
		 * @param {String} name
		 * @param {String} url
		 * @return {String}
		 */
function f(a,b){b||(b=window.location.href),a=a.replace(/[\[\]]/g,"\\$&");var c=new RegExp("[?&]"+a+"(=([^&#]*)|&|#|$)"),d=c.exec(b);return d?d[2]?decodeURIComponent(d[2].replace(/\+/g," ")):"":null}var g=b("#badgeos-achievements-container");if(!(g.length<1)){
// vars
var h=b(c.body),i="",j=c.body.className,k=j.indexOf("single-job_listing")>-1&&j.indexOf("single")>-1;
// badges with challenges checklist
!function(){if(!1!==k){
// compile checklist template
var c=doT.template(b("#trbs-checklist-template").html()),f=b("#trbs-badges-challenges"),j=[],l={},m=null,n=b(trbs_badges.suggestion_form_link);
// add suggestion link
n.appendTo(g.closest(".trbs_listing_rewards").find("> h2.widget-title")).magnificPopup({tClose:listifySettings.l10n.magnific.tClose,tLoading:listifySettings.l10n.magnific.tLoading,fixedContentPos:!1,fixedBgPos:!0,overflowY:"scroll",type:"ajax"}),
// when ajax login is successful
h.on("tr-login-register-ajax-success",function(){
// force reload the page
a.location.reload(!0)}),
// challenges checklist item checked/unchecked
f.on("change tr-change",".trbs-checklist-item input[type=checkbox]",function(a){var c=b(a.currentTarget),f=c.data(),g=f.badge;
// user needs to login first
// user needs to login first
// toggle back
// cache needed data
// completion percentage check 
return!1===e()?(b("#secondary-nav-menu").find(".overlay-login").trigger("tr-click"),c.prop("checked",!c.prop("checked")),!0):(d===l[g]&&(l[g]={$badge:b("#badgeos-achievements-list-item-"+g),$checklist_points:c.closest(".trbs-checklist").find(".trbs-checklist-item input[type=checkbox]")}),l[g].complete_percentage=Math.round(l[g].$checklist_points.filter(":checked").length/l[g].$checklist_points.length*100),l[g].$badge.attr("data-completed",l[g].complete_percentage),m&&m.abort(),void(m=b.post(listifySettings.ajaxurl,b.extend({},f,{point:a.currentTarget.value,checked:a.currentTarget.checked,nonce:trbs_badges.checklist_nonce,action:"challenges_checklist_update"}),function(a,b){return function(c){if(!1===c.success)
// toggle back
a.prop("checked",!a.prop("checked"));else{
// not all points are checked
if(
// update with the new complete percentage
b.$badge.attr("data-completed",c.data.percentage).attr("data-last-earning",JSON.stringify(c.data.last_earning)),b.$checklist_points.length!==b.$checklist_points.filter(":checked").length)return;
// toggle earn status
b.$badge.removeClass("user-has-not-earned").addClass("user-has-earned badgeos-glow"),setTimeout(function(a){return function(){
// remove the glow
a.removeClass("badgeos-glow")}}(b.$badge),2e3),
// reset checked points
b.$checklist_points.prop("checked",!1)}}}(c,l[g]),"json")))}),
// badges click
g.on("click tr-click",".badgeos-achievements-challenges-item",function(a){var d=b(a.currentTarget),e=d.data("steps-data"),g=d.data("id");
// reset
j=[];for(var h in e)if(e.hasOwnProperty(h)){
// current step
var k=b.extend({},e[h],{step_id:h,badge_id:g});"challenges_checklist_listing_id"in k&&k.challenges_checklist_listing_id.toString()===i.toString()&&"challenges_checklist"in k&&!b.isEmptyObject(k.challenges_checklist)&&
// render badge's step checklist
j.push(c(k))}
// output checklist(s)
return j.length<1||void f.html(j.join(""))})}}(),
// badges filter
function(){var a=b("#badgeos-achievements-filter");if(a.length){for(var c=['<ul class="badgeos-badge-types-filter">'],d={},e=0,f=trbs_badges.badge_filters.length;e<f;e++)d=trbs_badges.badge_filters[e],c.push("<li"+(0===e?' class="current"':"")+'><a href="#'+d.value+'">'+d.filter_name+"</a></li>");
// filters list closing tag
c.push("</ul>"),
// hidden input
c.push('<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="all" />'),
// replace current badge filter with the new one
a.html(c.join(""));var g=b("#achievements_list_filter");
// badge type filter click
a.on("click",".badgeos-badge-types-filter a",function(c){c.preventDefault();
// clicked link
var d=b(c.currentTarget),e=d.closest("li");!1===e.hasClass("current")&&(
// set selected badge type value
g.val(d.attr("href").replace("#","")).trigger("change"),
// set current badge type CSS class
a.find("li.current").removeClass("current"),e.addClass("current"))})}}(),
// badges popover init
function(){b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover({onShow:function(a){
// related badge
var c=b("#badgeos-achievements-container").find('.badgeos-achievements-list-item[data-target="'+a.attr("id")+'"]'),d=Math.abs(c.attr("data-completed")),e=a.find(".badgeos-earning");if(
// 100% max
d=d>100?100:d,
// bar width
a.find(".badgeos-percentage-bar").css("width",d+"%"),
// bar text
a.find(".badgeos-percentage-number").html(d+"&percnt;"),e.length){
// update last earning
var f=JSON.parse(c.attr("data-last-earning"));
// earning count
e.find(".badgeos-earning-count").text(f.earn_count),
// earning date
e.find(".badgeos-earning-date").text(f.date_earned_formatted)}}})})}(),
// badges for current open listing
function(){if(!1!==k){var a=j.match(/postid-\d+/i);null!==a&&(
// loaded listing ID in the current page
i=parseInt(a[0].replace("postid-","")),isNaN(i)||(b.ajaxSetup({data:{trbs_listing_id:i},dataFilter:function(a,c){if("json"!==c)
// return data with no change
return a;
// parse raw data into json object
var d=b.parseJSON(a);
// no badges found, change empty badges message content
return d.data&&d.data.badge_count<1&&(d.data.message=trbs_badges.empty_message),JSON.stringify(d)}}),b(c).ajaxSuccess(function(a,b,c,d){"get-achievements"===f("action",c.url)&&"badges"===f("type",c.url)&&setTimeout(function(){
// trigger first badge click
g.find(".badgeos-achievements-challenges-item:first").trigger("tr-click")},100)})))}}()}})}(window,jQuery,document);