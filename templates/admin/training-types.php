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
					<td>
						<input class="regular-text" id="lpt-name" name="name" value="<?php echo esc_attr($editing?->name ?? ''); ?>" required>
						<p class="description">Naam die Jasmijn in het schema kiest, bijvoorbeeld duurloop, interval of kracht.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lpt-category">Categorie</label></th>
					<td>
						<input class="regular-text" id="lpt-category" name="category" value="<?php echo esc_attr($editing?->category ?? ''); ?>" placeholder="running, cycling, swimming, strength" required>
						<p class="description">Sportgroep voor filtering en totalen. Gebruik bijvoorbeeld running, cycling, swimming of strength.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lpt-unit">Eenheid</label></th>
					<td>
						<input class="regular-text" id="lpt-unit" name="unit" value="<?php echo esc_attr($editing?->unit ?? ''); ?>" placeholder="kilometers, meters" required>
						<p class="description">Meetwaarde voor geplande en uitgevoerde afstand. Zwemmen met meters wordt automatisch omgerekend naar kilometers in de totalen.</p>
					</td>
				</tr>
                <tr>
                    <th scope="row"><label for="lpt-color">Kleur</label></th>
                    <td>
                        <input
                            id="lpt-color"
                            name="color"
                            type="color"
                            value="<?php echo esc_attr($editing?->color ?? '#ffffff'); ?>"
                        >
                        <p class="description">Kleur die bij deze oefening wordt weergegeven in het schema.</p>
                    </td>
                </tr>
				<tr>
					<th scope="row"><label for="lpt-linked-url">Link</label></th>
					<td>
						<input class="regular-text" id="lpt-linked-url" name="linked_url" type="url" value="<?php echo esc_attr($editing?->linkedUrl ?? ''); ?>">
						<p class="description">Optionele link naar uitleg, video of externe instructie voor deze oefening.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Actief</th>
					<td>
						<label>
							<input type="checkbox" name="active" value="1" <?php checked($editing?->active ?? true); ?>>
							Tonen bij nieuwe schema’s
						</label>
						<p class="description">Uitgeschakelde oefeningen blijven zichtbaar in bestaande schema’s, maar zijn niet meer te kiezen voor nieuwe trainingen.</p>
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
				<th>Kleur</th>
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
                        <span
                            style="
                                display: inline-block;
                                width: 20px;
                                height: 20px;
                                margin-right: 6px;
                                border: 1px solid #ccc;
                                border-radius: 3px;
                                background-color: <?php echo esc_attr($type->color); ?>;
                                vertical-align: middle;
                            "
                        ></span>
                        <?php echo esc_html($type->color); ?>
                    </td>
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
