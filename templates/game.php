<?php
/**
 * Шаблон кода игры, выводимой в списке игр во время поиска
 */
?><div class="matchcenter-game js-game-search-add" data-game_id="<?php echo $data['game_id']; ?>" data-title="<?php echo $data['title_full']; ?>" data-url="<?php echo $data['url']; ?>">
	<div class="matchcenter-game__team-flag-1"
	     style="background-image: url(<?php echo $data['team_flag_1']; ?>);"></div>
	<div class="matchcenter-game__game-data">
		<div class="matchcenter-game__teams">

			<div class="matchcenter-game__scheduled"><?php echo $data['scheduled']; ?></div>

			<div class="matchcenter-game__title">
				<div class="matchcenter-game__team-1"><?php echo $data['team_1']; ?></div>
				<div class="matchcenter-game__team-2"><?php echo $data['team_2']; ?></div>
			</div>


			<div class="matchcenter-game__score"><?php echo $data['score']; ?></div>

		</div>
	</div>
	<div class="matchcenter-game__team-flag-2"
	     style="background-image: url(<?php echo $data['team_flag_2']; ?>);"></div>
</div>
