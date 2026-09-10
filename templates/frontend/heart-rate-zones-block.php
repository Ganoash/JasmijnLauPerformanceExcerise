<?php
/**
 * @var string $lactate_test_url
 * @var string $login_url
 * @var bool $logged_in
 * @var \LauPerformanceTraining\Domain\HeartRateZones|null $zones
 */
$ranges = $zones ? $zones->displayRanges() : [];
?>
<section class="lpt-heart-rate-zones">
	<h2>Hartslagzones</h2>

	<?php if (! $logged_in) : ?>
		<p>Log in om je hartslagzones te bekijken.</p>
		<div class="lpt-heart-rate-zones__actions">
			<a class="lpt-heart-rate-zones__button" href="<?php echo esc_url($login_url); ?>">Inloggen</a>
			<a class="lpt-heart-rate-zones__button lpt-heart-rate-zones__button--secondary" href="<?php echo esc_url($lactate_test_url); ?>">Plan een lactaattest</a>
		</div>
	<?php elseif ($ranges === []) : ?>
		<p>Nog geen hartslagzones bekend. Plan een lactaattest.</p>
		<a class="lpt-heart-rate-zones__button" href="<?php echo esc_url($lactate_test_url); ?>">Plan een lactaattest</a>
	<?php else : ?>
		<ul class="lpt-heart-rate-zones__list">
			<?php foreach ($ranges as $range) : ?>
				<li>
					<span class="lpt-heart-rate-zones__heart lpt-heart-rate-zones__heart--<?php echo esc_attr($range['color']); ?>" aria-hidden="true">♥</span>
					<span><?php echo esc_html($range['label']); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
