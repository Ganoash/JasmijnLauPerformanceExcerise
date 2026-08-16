<?php
/**
 * @var string $action_url
 * @var string|null $error_message
 * @var string $frontend_url
 * @var array<int,int[]> $linked_types
 * @var \LauPerformanceTraining\Domain\TrainingType[] $linked_training_types
 * @var string $nonce
 * @var \LauPerformanceTraining\Domain\Schema $schema
 * @var \LauPerformanceTraining\Domain\TrainingType[] $training_types
 * @var \LauPerformanceTraining\Domain\Training[] $trainings
 * @var WP_User $user
 * @var \LauPerformanceTraining\Domain\Week $week
 */

$day_names = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
$time_names = ['morning' => 'ochtend', 'afternoon' => 'middag'];
?>
<div class="wrap">
	<h1>Schema voor <?php echo esc_html($user->display_name); ?></h1>

	<p>
		<strong><?php echo esc_html('Week ' . $week->isoWeekNumber() . ', ' . date_i18n('d-m-Y', strtotime($week->startDate())) . ' - ' . date_i18n('d-m-Y', strtotime($week->endDate()))); ?></strong>
	</p>

	<p>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-schema-editor&user_id=' . $user->ID . '&week_start_date=' . rawurlencode($week->plusWeeks(-1)->startDate()))); ?>">Vorige week</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-schema-editor&user_id=' . $user->ID)); ?>">Huidige week</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lpt-schema-editor&user_id=' . $user->ID . '&week_start_date=' . rawurlencode($week->plusWeeks(1)->startDate()))); ?>">Volgende week</a>
		<a class="button" href="<?php echo esc_url($frontend_url); ?>" target="_blank" rel="noopener">Frontend bekijken</a>
	</p>

	<?php if (isset($_GET['updated'])) : ?>
		<div class="notice notice-success"><p>Schema opgeslagen.</p></div>
	<?php endif; ?>

	<?php if (isset($_GET['lpt_error'])) : ?>
		<div class="notice notice-error"><p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['lpt_error']))); ?></p></div>
	<?php endif; ?>

	<?php if ($error_message !== null) : ?>
		<div class="notice notice-error"><p><?php echo esc_html($error_message); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url($action_url); ?>">
		<input type="hidden" name="action" value="lpt_save_schema">
		<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
		<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $user->ID); ?>">
		<input type="hidden" name="week_start_date" value="<?php echo esc_attr($week->startDate()); ?>">

		<table class="widefat striped">
			<thead>
				<tr>
					<th>Dag</th>
					<th>Training</th>
					<th>Primaire oefening</th>
					<th>Extra oefeningen</th>
					<th>Coachopmerking</th>
					<th>Ingevuld door atleet</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($trainings as $index => $training) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html($day_names[$training->dayIndex]); ?></strong><br>
							<?php echo esc_html(date_i18n('d-m-Y', strtotime($week->dayDate($training->dayIndex)))); ?><br>
							<?php echo esc_html($time_names[$training->timeOfDay] ?? $training->timeOfDay); ?>
							<input type="hidden" name="trainings[<?php echo esc_attr((string) $index); ?>][training_id]" value="<?php echo esc_attr((string) $training->id); ?>">
						</td>
						<td>
							<textarea name="trainings[<?php echo esc_attr((string) $index); ?>][description]" rows="4" class="large-text"><?php echo esc_textarea($training->description); ?></textarea>
						</td>
						<td>
							<select name="trainings[<?php echo esc_attr((string) $index); ?>][primary_training_type_id]">
								<option value="">Geen</option>
								<?php foreach ($training_types as $type) : ?>
									<option value="<?php echo esc_attr((string) $type->id); ?>" <?php selected($training->primaryTrainingTypeId, $type->id); ?>>
										<?php echo esc_html($type->name); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<fieldset class="lpt-extra-exercises">
								<legend class="screen-reader-text">Extra oefeningen</legend>
								<?php foreach ($linked_training_types as $type) : ?>
									<label>
										<input
											type="checkbox"
											name="trainings[<?php echo esc_attr((string) $index); ?>][linked_training_type_ids][]"
											value="<?php echo esc_attr((string) $type->id); ?>"
											<?php checked(in_array($type->id, $linked_types[$training->id] ?? [], true)); ?>
										>
										<?php echo esc_html($type->name); ?>
									</label><br>
								<?php endforeach; ?>
								<?php if ($linked_training_types === []) : ?>
									<span class="description">Maak eerst een actieve krachtoefening aan.</span>
								<?php endif; ?>
							</fieldset>
							<p class="description">Selecteer een of meerdere krachtoefeningen naast de primaire training.</p>
						</td>
						<td>
							<textarea name="trainings[<?php echo esc_attr((string) $index); ?>][coach_comment]" rows="4" class="large-text"><?php echo esc_textarea($training->coachComment); ?></textarea>
						</td>
						<td>
							Afstand: <?php echo esc_html($training->actualDistance === null ? '-' : (string) $training->actualDistance); ?><br>
							Uitvoering: <?php echo esc_html($training->executionComment !== '' ? $training->executionComment : '-'); ?><br>
							Blessure: <?php echo esc_html($training->injuryComment !== '' ? $training->injuryComment : '-'); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php submit_button('Schema opslaan'); ?>
	</form>
</div>
