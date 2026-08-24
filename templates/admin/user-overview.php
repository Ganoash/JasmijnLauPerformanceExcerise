<?php
/**
 * @var string $action_url
 * @var string $current_week
 * @var array<int,array<int,array{day:string,time_of_day:string,comment:string}>> $injury_comments
 * @var string $nonce
 * @var string $search
 * @var array<int,int> $training_counts
 * @var WP_User[] $users
 */
$day_names  = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
$time_names = ['morning' => 'ochtend', 'afternoon' => 'middag'];
?>
<div class="wrap">
	<h1>Schema’s bewerken</h1>

	<form method="get">
		<input type="hidden" name="page" value="lpt-training">
		<p class="search-box">
			<label class="screen-reader-text" for="lpt-user-search">Gebruiker zoeken</label>
			<input id="lpt-user-search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Zoek gebruiker">
			<?php submit_button('Zoeken', '', '', false); ?>
		</p>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th>Naam</th>
				<th>E-mail</th>
				<th>Klachten deze week</th>
				<th>Trainingen per dag</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($users as $user) : ?>
				<tr>
					<td><?php echo esc_html($user->display_name); ?></td>
					<td><?php echo esc_html($user->user_email); ?></td>
					<td>
						<?php if (($injury_comments[$user->ID] ?? []) === []) : ?>
							-
						<?php else : ?>
							<ul>
								<?php foreach ($injury_comments[$user->ID] as $comment) : ?>
									<li>
										<strong>
											<?php echo esc_html(($day_names[(int) $comment['day']] ?? $comment['day']) . ' ' . ($time_names[$comment['time_of_day']] ?? $comment['time_of_day'])); ?>
										</strong>:
										<?php echo esc_html($comment['comment']); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url($action_url); ?>">
							<input type="hidden" name="action" value="lpt_save_user_training_preference">
							<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
							<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $user->ID); ?>">
							<select name="trainings_per_day">
								<option value="2" <?php selected($training_counts[$user->ID] ?? 2, 2); ?>>2 trainingen</option>
								<option value="1" <?php selected($training_counts[$user->ID] ?? 2, 1); ?>>1 training</option>
							</select>
							<?php submit_button('Opslaan', 'small', '', false); ?>
						</form>
					</td>
					<td>
						<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-schema-editor&user_id=' . $user->ID . '&week_start_date=' . rawurlencode($current_week))); ?>">
							Schema openen
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
