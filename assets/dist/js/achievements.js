/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){var a=b("#badgeos-achievements-container");if(!(a.length<1)){
// popover init
b(".badgeos-achievements-list-item").livequery(function(a,c){b(c).webuiPopover()});var d=c.body.className;if(!(d.indexOf("single")<0||d.indexOf("single-job_listing")<0)){var e=d.match(/postid-\d+/i);if(null!==e){
// loaded listing ID in the current page
var f=parseInt(e[0].replace("postid-",""));isNaN(f)||b.ajaxSetup({data:{trbs_listing_id:f}})}}}})}(window,jQuery,document);