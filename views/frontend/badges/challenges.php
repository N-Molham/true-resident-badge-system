<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 09-Mar-17
 * Time: 7:08 PM
 */
?>
<div id="trbs-badges-challenges"></div>

<script id="trbs-checklist-template" type="text/template">
	<div id="trbs-checklist-{{=it.id}}" class="trbs-checklist-container">
		<h2 class="widget-title">{{=it.title}}</h2>
		<ul class="trbs-checklist" data-badge="{{=it.badge_id}}" data-step="{{=it.step_id}}">
			{{ for ( var point_index in it.challenges_checklist ) { }}
			<li class="trbs-checklist-item">
				<label>
					<input type="checkbox" value="{{=point_index}}" data-badge="{{=it.badge_id}}" data-step="{{=it.step_id}}" />
					<span><span class="trbs-text">{{=it.challenges_checklist[point_index]}}</span></span>
				</label>
			</li>
			{{ } }}
		</ul>
	</div>
</script>