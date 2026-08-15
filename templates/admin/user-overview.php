<?php
/**
 * @var string $current_week
 * @var string $search
 * @var WP_User[] $users
 */
?>
<div class="wrap">
	<h1>Training schema’s</h1>

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
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($users as $user) : ?>
				<tr>
					<td><?php echo esc_html($user->display_name); ?></td>
					<td><?php echo esc_html($user->user_email); ?></td>
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
