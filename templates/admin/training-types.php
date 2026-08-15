<?php
/**
 * @var string $action_url
 * @var \LauPerformanceTraining\Domain\TrainingType|null $editing
 * @var string $error
 * @var string $nonce
 * @var \LauPerformanceTraining\Domain\TrainingType[] $types
 */
?>
<div class="wrap">
	<h1>Oefeningen</h1>

	<?php if ($error !== '') : ?>
		<div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
	<?php endif; ?>

	<?php if (isset($_GET['updated'])) : ?>
		<div class="notice notice-success"><p>Oefening opgeslagen.</p></div>
	<?php endif; ?>

	<h2><?php echo $editing ? 'Oefening bewerken' : 'Nieuwe oefening'; ?></h2>
	<form method="post" action="<?php echo esc_url($action_url); ?>">
		<input type="hidden" name="action" value="lpt_save_training_type">
		<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
		<?php if ($editing) : ?>
			<input type="hidden" name="training_type_id" value="<?php echo esc_attr((string) $editing->id); ?>">
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="lpt-name">Naam</label></th>
					<td><input class="regular-text" id="lpt-name" name="name" value="<?php echo esc_attr($editing?->name ?? ''); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="lpt-category">Categorie</label></th>
					<td><input class="regular-text" id="lpt-category" name="category" value="<?php echo esc_attr($editing?->category ?? ''); ?>" placeholder="running, cycling, swimming, strength" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="lpt-unit">Eenheid</label></th>
					<td><input class="regular-text" id="lpt-unit" name="unit" value="<?php echo esc_attr($editing?->unit ?? ''); ?>" placeholder="kilometers, meters" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="lpt-linked-url">Link</label></th>
					<td><input class="regular-text" id="lpt-linked-url" name="linked_url" type="url" value="<?php echo esc_attr($editing?->linkedUrl ?? ''); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Actief</th>
					<td>
						<label>
							<input type="checkbox" name="active" value="1" <?php checked($editing?->active ?? true); ?>>
							Tonen bij nieuwe schema’s
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button($editing ? 'Oefening opslaan' : 'Oefening toevoegen'); ?>
	</form>

	<h2>Bestaande oefeningen</h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th>Naam</th>
				<th>Categorie</th>
				<th>Eenheid</th>
				<th>Link</th>
				<th>Actief</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($types as $type) : ?>
				<tr>
					<td><?php echo esc_html($type->name); ?></td>
					<td><?php echo esc_html($type->category); ?></td>
					<td><?php echo esc_html($type->unit); ?></td>
					<td>
						<?php if ($type->linkedUrl !== '') : ?>
							<a href="<?php echo esc_url($type->linkedUrl); ?>" target="_blank" rel="noopener">Open</a>
						<?php endif; ?>
					</td>
					<td><?php echo $type->active ? 'Ja' : 'Nee'; ?></td>
					<td>
						<a href="<?php echo esc_url(admin_url('admin.php?page=lpt-training-types&training_type_id=' . $type->id)); ?>">Bewerken</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
