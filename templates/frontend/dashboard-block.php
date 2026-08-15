<?php
/**
 * @var array<string,\LauPerformanceTraining\Domain\Week> $links
 * @var int $user_id
 */
?>
<nav class="lpt-dashboard-block" aria-label="Training schema links">
	<ul>
		<?php foreach ($links as $label => $week) : ?>
			<li>
				<a href="<?php echo esc_url(home_url('/training-schema/' . $user_id . '/' . $week->startDate() . '/')); ?>">
					<span><?php echo esc_html($label); ?></span>
					<strong><?php echo esc_html('Week ' . $week->isoWeekNumber()); ?></strong>
					<small><?php echo esc_html(date_i18n('d-m-Y', strtotime($week->startDate())) . ' - ' . date_i18n('d-m-Y', strtotime($week->endDate()))); ?></small>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
