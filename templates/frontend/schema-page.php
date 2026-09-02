<?php
/**
 * @var array<int,\LauPerformanceTraining\Domain\TrainingType[]> $linked_types
 * @var array<int,\LauPerformanceTraining\Domain\TrainingType|null> $primary_types
 * @var \LauPerformanceTraining\Domain\Schema $schema
 * @var bool $show_time_of_day
 * @var \LauPerformanceTraining\Domain\DistanceTotals $totals
 * @var \LauPerformanceTraining\Domain\Training[] $trainings
 * @var WP_User $user
 * @var \LauPerformanceTraining\Domain\Week $week
 */

$day_names = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
$time_names = ['morning' => 'ochtend', 'afternoon' => 'middag'];
$previous_week_url = home_url('/training-schema/' . $user->ID . '/' . rawurlencode($week->plusWeeks(-1)->startDate()) . '/');
$next_week_url = home_url('/training-schema/' . $user->ID . '/' . rawurlencode($week->plusWeeks(1)->startDate()) . '/');

if (! function_exists('lpt_hex_to_rgba')) {
	function lpt_hex_to_rgba(string $hex, float $alpha = 0.12): string
	{
		$hex = ltrim($hex, '#');

		if (strlen($hex) !== 6) {
			return 'rgba(255, 255, 255, 1)';
		}

		$red   = hexdec(substr($hex, 0, 2));
		$green = hexdec(substr($hex, 2, 2));
		$blue  = hexdec(substr($hex, 4, 2));

		return sprintf(
			'rgba(%d, %d, %d, %.2f)',
			$red,
			$green,
			$blue,
			$alpha
		);
	}
}

/**
 * @param \LauPerformanceTraining\Domain\TrainingType|null $primary_type
 * @param \LauPerformanceTraining\Domain\TrainingType[] $linked_types
 * @return array<string,array{label:string,unit:string,field:string,value:float|null}>
 */
if (! function_exists('lpt_distance_fields_for_training')) {
	function lpt_distance_fields_for_training(
		\LauPerformanceTraining\Domain\Training $training,
		?\LauPerformanceTraining\Domain\TrainingType $primary_type,
		array $linked_types
	): array {
		$fields = [];
		foreach (array_filter([$primary_type, ...$linked_types]) as $type) {
			$category = strtolower($type->category);
			if (! in_array($category, ['running', 'cycling', 'swimming'], true) || isset($fields[$category])) {
				continue;
			}

			$fields[$category] = [
				'label' => match ($category) {
					'running' => 'Lopen',
					'cycling' => 'Fietsen',
					default => 'Zwemmen',
				},
				'unit'  => $type->unit,
				'field' => 'actual_' . $category . '_distance',
				'value' => match ($category) {
					'running' => $training->actualRunningDistance,
					'cycling' => $training->actualCyclingDistance,
					default => $training->actualSwimmingDistance,
				},
			];
		}

		return $fields;
	}
}

/**
 * @param \LauPerformanceTraining\Domain\TrainingType[] $linked_types
 */
if (! function_exists('lpt_is_rest_training')) {
	function lpt_is_rest_training(
		\LauPerformanceTraining\Domain\Training $training,
		?\LauPerformanceTraining\Domain\TrainingType $primary_type,
		array $linked_types
	): bool {
		return $training->description === ''
			&& $primary_type === null
			&& $linked_types === [];
	}
}
?>
<main class="lpt-schema-page">
	<header class="lpt-schema-header">
		<div>
			<h1>Training schema</h1>
			<p><?php echo esc_html($user->display_name); ?></p>
		</div>
		<div class="lpt-week-summary">
			<div class="lpt-week-label">
				<?php echo esc_html('Week ' . $week->isoWeekNumber()); ?><br>
				<label for="lpt-week-picker" class="screen-reader-text">
					<?php esc_html_e('Selecteer week', 'lau-performance-training'); ?>
				</label>
				<input
					id="lpt-week-picker"
					type="date"
					value="<?php echo esc_attr($week->startDate()); ?>"
				>
				<span>
					<?php
					echo esc_html(
						' - '
						. date_i18n('d-m-Y', strtotime($week->endDate()))
					);
					?>
				</span>
			</div>
			<nav class="lpt-week-navigation" aria-label="<?php echo esc_attr__('Weeknavigatie', 'lau-performance-training'); ?>">
				<a class="lpt-week-button" href="<?php echo esc_url($previous_week_url); ?>"><?php esc_html_e('Vorige week', 'lau-performance-training'); ?></a>
				<a class="lpt-week-button" href="<?php echo esc_url($next_week_url); ?>"><?php esc_html_e('Volgende week', 'lau-performance-training'); ?></a>
			</nav>
		</div>
	</header>

	<section class="lpt-totals" aria-label="Totalen">
		<div><span>Lopen: </span><strong data-total="running"><?php echo esc_html(number_format_i18n($totals->runningKm, 2)); ?></strong><small>km</small></div>
		<div><span>Fietsen: </span><strong data-total="cycling"><?php echo esc_html(number_format_i18n($totals->cyclingKm, 2)); ?></strong><small>km</small></div>
		<div><span>Zwemmen: </span><strong data-total="swimming"><?php echo esc_html(number_format_i18n($totals->swimmingKm, 2)); ?></strong><small>km</small></div>
	</section>

	<div class="lpt-schema-controls">
		<label>
			<input type="checkbox" id="lpt-hide-rest-days">
			<span>Alleen trainingsdagen</span>
		</label>
	</div>

	<section class="lpt-training-list" aria-label="Trainingen">
		<?php foreach ($trainings as $training) : ?>
			<?php $primary_type = $primary_types[$training->id] ?? null; ?>
			<?php $training_linked_types = $linked_types[$training->id] ?? []; ?>
			<?php $distance_fields = lpt_distance_fields_for_training($training, $primary_type, $training_linked_types); ?>
			<?php $is_rest_training = lpt_is_rest_training($training, $primary_type, $training_linked_types); ?>
			<?php
                $background_color = $primary_type
                    ? lpt_hex_to_rgba($primary_type->color, 0.12)
                    : 'transparent';
            ?>
			<article
				class="lpt-training-row"
				data-training-id="<?php echo esc_attr((string) $training->id); ?>"
				data-is-rest="<?php echo esc_attr($is_rest_training ? '1' : '0'); ?>"
                style="background-color: <?php echo esc_attr($background_color); ?>;"
			>
				<div class="lpt-training-main">
					<div class="lpt-training-date">
						<span><strong><?php echo esc_html($day_names[$training->dayIndex]); ?></strong>
						<?php echo esc_html(date_i18n('d-m-Y', strtotime($week->dayDate($training->dayIndex)))); ?>
						<?php if ($show_time_of_day) : ?>
							<?php echo esc_html($time_names[$training->timeOfDay] ?? $training->timeOfDay); ?>
						<?php endif; ?></span>
					</div>

					<div class="lpt-training-content">
						<div class="lpt-description">
							<strong class="lpt-field-heading">Beschrijving</strong>
							<?php echo $training->description !== '' ? wp_kses_post(wpautop($training->description)) : '<p>Rust</p>'; ?>
						</div>

						<?php if ($primary_type || $training_linked_types !== []) : ?>
							<div class="lpt-training-types">
								<strong class="lpt-field-heading">Type</strong>
								<ul class="lpt-type-links">
									<?php if ($primary_type) : ?>
										<li>
											<?php if ($primary_type->linkedUrl !== '') : ?>
												<a href="<?php echo esc_url($primary_type->linkedUrl); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr('Info over ' . $primary_type->name); ?>">
													<?php echo esc_html($primary_type->name); ?>
												</a>
											<?php else : ?>
												<?php echo esc_html($primary_type->name); ?>
											<?php endif; ?>
										</li>
									<?php endif; ?>
									<?php foreach ($training_linked_types as $type) : ?>
										<li>
											<?php if ($type->linkedUrl !== '') : ?>
												<a href="<?php echo esc_url($type->linkedUrl); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr('Info over ' . $type->name); ?>">
													<?php echo esc_html($type->name); ?>
												</a>
											<?php else : ?>
												<?php echo esc_html($type->name); ?>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="lpt-feedback-fields">
					<?php foreach ($distance_fields as $category => $field) : ?>
						<label>
							<span><?php echo esc_html($field['label'] . ' (' . $field['unit'] . ')'); ?></span>
							<input
								data-field="<?php echo esc_attr($field['field']); ?>"
								data-category="<?php echo esc_attr($category); ?>"
								data-unit="<?php echo esc_attr($field['unit']); ?>"
								type="number"
								step="0.01"
								min="0"
								value="<?php echo esc_attr($field['value'] === null ? '' : (string) $field['value']); ?>"
							>
						</label>
					<?php endforeach; ?>
					<label>
						<span>Uitvoering</span>
						<textarea data-field="execution_comment" rows="3"><?php echo esc_textarea($training->executionComment); ?></textarea>
					</label>
					<label>
						<span>Klachten/ Blessures</span>
						<textarea data-field="injury_comment" rows="3"><?php echo esc_textarea($training->injuryComment); ?></textarea>
					</label>
					<span class="lpt-save-status" aria-live="polite"></span>
				</div>

				<?php if ($training->coachComment !== '') : ?>
					<div class="lpt-coach-comment">
						<strong>Opmerking Jasmijn</strong>
						<p><?php echo esc_html($training->coachComment); ?></p>
					</div>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</section>
</main>
