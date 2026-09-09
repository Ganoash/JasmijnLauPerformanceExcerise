<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Shortcodes;

use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Support\GoalFormatter;

final class GoalsLinkShortcode
{
	public function __construct(private readonly GoalRepository $goals)
	{
	}

	public function register(): void
	{
		add_shortcode('lpt_goals_link', [$this, 'render']);
	}

	public function render(mixed $attributes = []): string
	{
		unset($attributes);

		if (! is_user_logged_in()) {
			return '';
		}

		$goals = $this->goals->findActiveByUser(get_current_user_id());
		ob_start();
		?>
		<div class="lpt-goals-shortcode">
			<?php if ($goals !== []) : ?>
				<ul>
					<?php foreach ($goals as $goal) : ?>
						<li>
							<?php
							echo esc_html(
								$goal->name . ', '
								. GoalFormatter::date($goal->goalDate) . ', '
								. GoalFormatter::targetTime($goal->targetTime)
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
				<a href="<?php echo esc_url(home_url('/training-goals/')); ?>">Mijn Wedstrijden bekijken</a>
			<?php else : ?>
				<p>Stel een doel in voor je volgende wedstrijd.</p>
				<a href="<?php echo esc_url(home_url('/training-goals/')); ?>">Wedstrijd instellen</a>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
