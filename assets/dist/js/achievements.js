!function(i,h,r,e){h(function(){var n=h("#badgeos-achievements-container");if(!(n.length<1)){var e=h(r.body),g="",t=r.body.className,a=-1<t.indexOf("single-job_listing")&&-1<t.indexOf("single");!function(){if(!1!=a){var r=doT.template(h("#trbs-checklist-template").html()),c=h("#trbs-badges-challenges"),l=[],o={},d=null;h(trbs_badges.suggestion_form_link).appendTo(n.closest(".trbs_listing_rewards").find("> h2.widget-title")).magnificPopup({tClose:listifySettings.l10n.magnific.tClose,tLoading:listifySettings.l10n.magnific.tLoading,fixedContentPos:!1,fixedBgPos:!0,overflowY:"scroll",type:"ajax"}),e.on("tr-login-register-ajax-success",function(){i.location.reload(!0)}),c.on("change tr-change",".trbs-checklist-item input[type=checkbox]",function(e){var a,i,t=h(e.currentTarget),n=t.data(),s=n.badge;if(!1===Boolean(listify_child_overlays&&"is_user_logged_in"in listify_child_overlays?listify_child_overlays.is_user_logged_in:trbs_badges.is_logged_in))return h("#secondary-nav-menu").find(".overlay-login").trigger("tr-click"),t.prop("checked",!t.prop("checked")),!0;void 0===o[s]&&(o[s]={$badge:h("#badgeos-achievements-list-item-"+s),$checklist_points:t.closest(".trbs-checklist").find(".trbs-checklist-item input[type=checkbox]")}),o[s].complete_percentage=Math.round(o[s].$checklist_points.filter(":checked").length/o[s].$checklist_points.length*100),o[s].$badge.attr("data-completed",o[s].complete_percentage),d&&d.abort(),d=h.post(listifySettings.ajaxurl,h.extend({},n,{point:e.currentTarget.value,checked:e.currentTarget.checked,nonce:trbs_badges.checklist_nonce,action:"challenges_checklist_update"}),(a=t,i=o[s],function(e){if(!1===e.success)a.prop("checked",!a.prop("checked"));else{if(i.$badge.attr("data-completed",e.data.percentage).attr("data-last-earning",JSON.stringify(e.data.last_earning)),i.$checklist_points.length!==i.$checklist_points.filter(":checked").length)return;i.$badge.removeClass("user-has-not-earned").addClass("user-has-earned badgeos-glow"),setTimeout((t=i.$badge,function(){t.removeClass("badgeos-glow")}),2e3),i.$checklist_points.prop("checked",!1)}var t}),"json")}),n.on("click tr-click",".badgeos-achievements-challenges-item",function(e){var t=h(e.currentTarget),a=t.data("steps-data"),i=t.data("id");for(var n in l=[],a)if(a.hasOwnProperty(n)){var s=h.extend({},a[n],{step_id:n,badge_id:i});"challenges_checklist_listing_id"in s&&s.challenges_checklist_listing_id.toString()===g.toString()&&"challenges_checklist"in s&&!h.isEmptyObject(s.challenges_checklist)&&l.push(r(s))}if(l.length<1)return!0;c.html(l.join(""))})}}(),function(){var i=h("#badgeos-achievements-filter");if(i.length){for(var e=['<ul class="badgeos-badge-types-filter">'],t={},a=0,n=trbs_badges.badge_filters.length;a<n;a++)t=trbs_badges.badge_filters[a],e.push("<li"+(0===a?' class="current"':"")+'><a href="#'+t.value+'">'+t.filter_name+"</a></li>");e.push("</ul>"),e.push('<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="all" />'),i.html(e.join(""));var s=h("#achievements_list_filter");i.on("click",".badgeos-badge-types-filter a",function(e){e.preventDefault();var t=h(e.currentTarget),a=t.closest("li");!1===a.hasClass("current")&&(s.val(t.attr("href").replace("#","")).trigger("change"),i.find("li.current").removeClass("current"),a.addClass("current"))})}}(),h(".badgeos-achievements-list-item").livequery(function(e,t){h(t).webuiPopover({onShow:function(e){var t=h("#badgeos-achievements-container").find('.badgeos-achievements-list-item[data-target="'+e.attr("id")+'"]'),a=Math.abs(t.attr("data-completed")),i=e.find(".badgeos-earning");if(a=100<a?100:a,e.find(".badgeos-percentage-bar").css("width",a+"%"),e.find(".badgeos-percentage-number").html(a+"&percnt;"),i.length){var n=JSON.parse(t.attr("data-last-earning"));i.find(".badgeos-earning-count").text(n.earn_count),i.find(".badgeos-earning-date").text(n.date_earned_formatted)}}})}),function(){if(!1!=a){var e=t.match(/postid-\d+/i);null!==e&&(g=parseInt(e[0].replace("postid-","")),isNaN(g)||(h.ajaxSetup({data:{trbs_listing_id:g},dataFilter:function(e,t){if("json"!==t)return e;var a=h.parseJSON(e);return a.data&&a.data.badge_count<1&&(a.data.message=trbs_badges.empty_message),JSON.stringify(a)}}),h(r).ajaxSuccess(function(e,t,a,i){"get-achievements"===s("action",a.url)&&"badges"===s("type",a.url)&&setTimeout(function(){n.find(".badgeos-achievements-challenges-item:first").trigger("tr-click")},100)})))}}()}function s(e,t){t||(t=window.location.href),e=e.replace(/[\[\]]/g,"\\$&");var a=new RegExp("[?&]"+e+"(=([^&#]*)|&|#|$)").exec(t);return a?a[2]?decodeURIComponent(a[2].replace(/\+/g," ")):"":null}})}(window,jQuery,document);