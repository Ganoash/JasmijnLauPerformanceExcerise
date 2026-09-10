<?php
/**
 * @var string $action_url
 * @var string $error
 * @var \LauPerformanceTraining\Domain\HeartRateZones|null $latest
 * @var string $nonce
 * @var string $test_date
 * @var WP_User|null $user
 */
?>
<div class="wrap">
	<h1>Hartslagzones</h1>

	<?php if ($error !== '') : ?>
		<div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
	<?php endif; ?>

	<?php if (isset($_GET['updated'])) : ?>
		<div class="notice notice-success"><p>Hartslagzones opgeslagen.</p></div>
	<?php endif; ?>

	<?php if (! $user) : ?>
		<div class="notice notice-info">
			<p>Kies een gebruiker op de schema-overzichtspagina om hartslagzones te bewerken.</p>
		</div>
		<p>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-training')); ?>">
				Terug naar gebruikers
			</a>
		</p>
	<?php else : ?>
		<p>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-heart-rate-zones')); ?>">
				Terug naar gebruikers
			</a>
		</p>

		<h2><?php echo esc_html($user->display_name); ?></h2>

		<form method="post" action="<?php echo esc_url($action_url); ?>">
			<input type="hidden" name="action" value="lpt_save_heart_rate_zones">
			<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
			<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $user->ID); ?>">

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="lpt-lactate-test-date">Lactaattestdatum</label></th>
						<td>
							<input id="lpt-lactate-test-date" name="lactate_test_date" type="date" value="<?php echo esc_attr($test_date); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lpt-zone-1-upper">Zone 1 bovengrens</label></th>
						<td>
							<input id="lpt-zone-1-upper" name="zone_1_upper" type="number" step="1" value="<?php echo esc_attr((string) ($latest?->zone1Upper ?? '')); ?>" required>
							<p class="description">Wordt getoond als: Zone 1: tot en met dit aantal bpm.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lpt-zone-2-upper">Zone 2 bovengrens</label></th>
						<td>
							<input id="lpt-zone-2-upper" name="zone_2_upper" type="number" step="1" value="<?php echo esc_attr((string) ($latest?->zone2Upper ?? '')); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lpt-zone-3-upper">Zone 3 bovengrens</label></th>
						<td>
							<input id="lpt-zone-3-upper" name="zone_3_upper" type="number" step="1" value="<?php echo esc_attr((string) ($latest?->zone3Upper ?? '')); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lpt-zone-4-upper">Zone 4 bovengrens</label></th>
						<td>
							<input id="lpt-zone-4-upper" name="zone_4_upper" type="number" step="1" value="<?php echo esc_attr((string) ($latest?->zone4Upper ?? '')); ?>">
							<p class="description">Zone 5 wordt automatisch getoond vanaf de bovengrens van zone 4 + 1 bpm.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button('Hartslagzones opslaan'); ?>
		</form>

		<?php if ($latest) : ?>
			<h2>Huidige zones</h2>
			<ul>
				<?php foreach ($latest->displayRanges() as $range) : ?>
					<li><?php echo esc_html($range['label']); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>Lactaattestdatum: <?php echo esc_html(date_i18n('d-m-Y', strtotime($latest->lactateTestDate))); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
