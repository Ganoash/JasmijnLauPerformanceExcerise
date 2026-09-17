<?php
/**
 * @var string $action_url
 * @var string $current_week
 * @var array<int,array<int,array{day:string,time_of_day:string,comment:string}>> $injury_comments
 * @var array<int,array<int,array{day:string,time_of_day:string,comment:string}>> $last_week_injury_comments
 * @var string $nonce
 * @var array<int,string> $payment_dates
 * @var string $payment_nonce
 * @var array<int,bool> $payment_overdue
 * @var string $search
 * @var array<int,int> $training_counts
 * @var WP_User[] $users
 */
use LauPerformanceTraining\Support\GoalFormatter;

$day_names  = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
$time_names = ['morning' => 'ochtend', 'afternoon' => 'middag'];
$payment_dates = $payment_dates ?? [];
$payment_overdue = $payment_overdue ?? [];
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

	<?php if (isset($_GET['payment_updated'])) : ?>
		<div class="notice notice-success"><p>Betaaldatum opgeslagen.</p></div>
	<?php endif; ?>

	<?php if (isset($_GET['lpt_error'])) : ?>
		<div class="notice notice-error"><p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['lpt_error']))); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th>Naam</th>
				<th>E-mail</th>
				<th>Volgende betaling</th>
                <th>Klachten vorige week</th>
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
						<?php
						$next_payment_date = $payment_dates[$user->ID] ?? '';
						$is_payment_overdue = (bool) ($payment_overdue[$user->ID] ?? false);
						?>
						<form method="post" action="<?php echo esc_url($action_url); ?>" class="lpt-payment-form <?php echo $is_payment_overdue ? 'is-overdue' : ''; ?>">
							<input type="hidden" name="action" value="lpt_save_user_payment">
							<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($payment_nonce); ?>">
							<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $user->ID); ?>">
							<label class="screen-reader-text" for="lpt-payment-date-<?php echo esc_attr((string) $user->ID); ?>">Volgende betaling op</label>
							<span class="lpt-payment-label">
								Volgende betaling op
								<?php if ($is_payment_overdue) : ?>
									<span class="lpt-payment-alert" aria-label="Betaaldatum verstreken">!</span>
								<?php endif; ?>
							</span>
							<span class="lpt-payment-controls">
								<input
									type="date"
									id="lpt-payment-date-<?php echo esc_attr((string) $user->ID); ?>"
									name="next_payment_date"
									value="<?php echo esc_attr($next_payment_date); ?>"
								>
								<button
									type="submit"
									name="payment_action"
									value="advance"
									class="button button-secondary lpt-payment-advance"
									aria-label="Herhaal volgende maand"
									title="Herhaal volgende maand"
								>
									<span class="dashicons dashicons-update" aria-hidden="true"></span>
								</button>
								<button type="submit" name="payment_action" value="save" class="button button-small">Opslaan</button>
							</span>
						</form>
					</td>
					<td>
						<?php if (($last_week_injury_comments[$user->ID] ?? []) === []) : ?>
							-
						<?php else : ?>
							<ul>
								<?php foreach ($last_week_injury_comments[$user->ID] as $comment) : ?>
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
						<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-heart-rate-zones&user_id=' . $user->ID)); ?>">
							Hartslagzones
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
