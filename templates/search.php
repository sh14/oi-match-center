<?php
/**
 * Шаблон кода, который выводится в админке
 */
?>
<button
	class="button game-search-button js-game-search-button"><?php _e( 'Добавить игру', 'oimatchcenter' ); ?></button>
<div class="game-search js-game-search-form">
	<div class="game-search__group js-game-search-field">
		<input
			class="game-search__input js-game-search-get"
			name="game_search"
			placeholder="<?php _e( 'Введите название команды и нажмите Enter', 'oimatchcenter' ); ?>"
			value="<?php echo get_search_query(); ?>">
		<div class="game-search__close js-game-search-close">✕</div>
	</div>
	<div class="game-search__result js-game-search-result"></div>
</div>
<script id="js-game-search-template" type="text/ejs">
		<?php echo oimatchcenter\get_template( 'game', array(
		'url'          => '<%=url%>',
		'game_id'      => '<%=game_id%>',
		'game-logo'    => '<%=game-logo%>',
		'season_image' => '<%=season_image%>',
		'season_title' => '<%=season_title%>',
		'scheduled'    => '<%=scheduled%>',
		'title_full'   => '<%=title_full%>',
		'team_1'       => '<%=team_1%>',
		'team_2'       => '<%=team_2%>',
		'team_flag_1'  => '<%=team_flag_1%>',
		'team_flag_2'  => '<%=team_flag_2%>',
		'score'        => '<%=score%>',
	) ); ?>

</script>

