<?php
/**
 * @var array<int,\LauPerformanceTraining\Domain\TrainingType[]> $linked_types
 * @var array<int,\LauPerformanceTraining\Domain\TrainingType|null> $primary_types
 * @var \LauPerformanceTraining\Domain\Schema $schema
 * @var \LauPerformanceTraining\Domain\DistanceTotals $totals
 * @var \LauPerformanceTraining\Domain\Training[] $trainings
 * @var WP_User $user
 * @var \LauPerformanceTraining\Domain\Week $week
 */

$day_names = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
$time_names = ['morning' => 'ochtend', 'afternoon' => 'middag'];
?>
<main class="lpt-schema-page">
	<header class="lpt-schema-header">
		<div>
			<h1>Training schema</h1>
			<p><?php echo esc_html($user->display_name); ?></p>
		</div>
		<div class="lpt-week-label">
			<?php echo esc_html('Week ' . $week->isoWeekNumber()); ?><br>
			<?php echo esc_html(date_i18n('d-m-Y', strtotime($week->startDate())) . ' - ' . date_i18n('d-m-Y', strtotime($week->endDate()))); ?>
		</div>
	</header>

	<section class="lpt-totals" aria-label="Totalen">
		<div><span>Lopen: </span><strong data-total="running"><?php echo esc_html(number_format_i18n($totals->runningKm, 2)); ?></strong><small>km</small></div>
		<div><span>Fietsen: </span><strong data-total="cycling"><?php echo esc_html(number_format_i18n($totals->cyclingKm, 2)); ?></strong><small>km</small></div>
		<div><span>Zwemmen: </span><strong data-total="swimming"><?php echo esc_html(number_format_i18n($totals->swimmingKm, 2)); ?></strong><small>km</small></div>
	</section>

	<section class="lpt-training-list" aria-label="Trainingen">
		<?php foreach ($trainings as $training) : ?>
			<?php $primary_type = $primary_types[$training->id] ?? null; ?>
			<article
				class="lpt-training-row"
				data-training-id="<?php echo esc_attr((string) $training->id); ?>"
				data-category="<?php echo esc_attr($primary_type?->category ?? ''); ?>"
				data-unit="<?php echo esc_attr($primary_type?->unit ?? ''); ?>"
			>
				<div class="lpt-training-main">
					<div class="lpt-training-date">
						<span><strong><?php echo esc_html($day_names[$training->dayIndex]); ?></strong>
						<?php echo esc_html(date_i18n('d-m-Y', strtotime($week->dayDate($training->dayIndex)))); ?>
						<?php echo esc_html($time_names[$training->timeOfDay] ?? $training->timeOfDay); ?></span>
					</div>

					<div class="lpt-training-content">
						<div class="lpt-description">
							<strong class="lpt-field-heading">Beschrijving</strong>
							<?php echo $training->description !== '' ? wp_kses_post(wpautop($training->description)) : '<p>Rust</p>'; ?>
						</div>

						<?php if ($primary_type || ($linked_types[$training->id] ?? []) !== []) : ?>
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
									<?php foreach ($linked_types[$training->id] ?? [] as $type) : ?>
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
					<label>
						<span>Afstand</span>
						<input data-field="actual_distance" type="number" step="0.01" min="0" value="<?php echo esc_attr($training->actualDistance === null ? '' : (string) $training->actualDistance); ?>">
					</label>
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
