<?php
/**
 * @var string $action_url
 * @var \LauPerformanceTraining\Domain\Goal[] $active_goals
 * @var bool $completed
 * @var \LauPerformanceTraining\Domain\Goal|null $edit_goal
 * @var string $error_message
 * @var \LauPerformanceTraining\Domain\Goal[] $inactive_goals
 * @var string $nonce
 * @var bool $own_goals
 * @var int $target_user_id
 * @var WP_User $user
 */

use LauPerformanceTraining\Support\GoalFormatter;

/**
 * @param \LauPerformanceTraining\Domain\Goal[] $goals
 */
if (! function_exists('lpt_render_goals_page_list')) {
	function lpt_render_goals_page_list(array $goals, string $action_url, string $nonce, int $target_user_id, string $page_url): void
	{
		if ($goals === []) {
			?>
			<p class="lpt-empty-goals">Geen wedstrijden.</p>
			<?php
			return;
		}
		?>
		<div class="lpt-goal-list">
			<?php foreach ($goals as $goal) : ?>
				<article class="lpt-goal-card">
					<div>
						<h3><?php echo esc_html($goal->name); ?></h3>
						<p class="lpt-goal-meta">
							<?php echo esc_html(GoalFormatter::date($goal->goalDate)); ?>
							<span><?php echo esc_html(GoalFormatter::targetTime($goal->targetTime)); ?></span>
						</p>
						<?php if ($goal->description !== '') : ?>
							<p><?php echo esc_html($goal->description); ?></p>
						<?php endif; ?>
						<?php if ($goal->actualTime !== '') : ?>
							<p class="lpt-goal-actual">Behaalde tijd: <?php echo esc_html($goal->actualTime); ?></p>
						<?php endif; ?>
					</div>
					<div class="lpt-goal-card-actions">
						<a href="<?php echo esc_url(add_query_arg('edit_goal_id', (string) $goal->id, $page_url)); ?>">Bewerken</a>
						<form method="post" action="<?php echo esc_url($action_url); ?>" data-lpt-delete-goal>
							<input type="hidden" name="action" value="lpt_delete_goal">
							<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
							<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $target_user_id); ?>">
							<input type="hidden" name="goal_id" value="<?php echo esc_attr((string) $goal->id); ?>">
							<button type="submit" class="lpt-delete-button">Verwijderen</button>
						</form>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

$page_url = $own_goals ? home_url('/training-goals/') : home_url('/training-goals/' . $target_user_id . '/');
$form_goal = $edit_goal;
?>
<main class="lpt-goals-page" data-goal-completed="<?php echo esc_attr($completed ? '1' : '0'); ?>">
	<header class="lpt-goals-header">
		<div>
			<h1>Wedstrijden</h1>
			<p><?php echo esc_html($user->display_name); ?></p>
		</div>
	</header>

	<?php if ($error_message !== '') : ?>
		<div class="lpt-goals-notice is-error">
			<?php echo esc_html($error_message); ?>
		</div>
	<?php endif; ?>
    <section class="lpt-goals-section">
		<h2>Actieve doelen</h2>
			<?php lpt_render_goals_page_list($active_goals, $action_url, $nonce, $target_user_id, $page_url); ?>
	</section>

	<section class="lpt-goals-section">
		<h2>Inactieve doelen</h2>
			<?php lpt_render_goals_page_list($inactive_goals, $action_url, $nonce, $target_user_id, $page_url); ?>
	</section>
	<section class="lpt-goal-form-section">
		<form method="post" action="<?php echo esc_url($action_url); ?>" class="lpt-goal-form">
			<input type="hidden" name="action" value="lpt_save_goal">
			<input type="hidden" name="_lpt_nonce" value="<?php echo esc_attr($nonce); ?>">
			<input type="hidden" name="user_id" value="<?php echo esc_attr((string) $target_user_id); ?>">
			<input type="hidden" name="goal_id" value="<?php echo esc_attr($form_goal ? (string) $form_goal->id : ''); ?>">

			<div class="lpt-goal-form-grid">
				<label>
					<span>Naam</span>
					<input name="name" required value="<?php echo esc_attr($form_goal ? $form_goal->name : ''); ?>">
				</label>
				<label>
					<span>Datum</span>
					<input name="goal_date" type="date" required value="<?php echo esc_attr($form_goal ? $form_goal->goalDate : ''); ?>">
				</label>
				<label>
					<span>Streeftijd</span>
					<input name="target_time" value="<?php echo esc_attr($form_goal ? $form_goal->targetTime : ''); ?>">
				</label>
				<label>
					<span>Werkelijke tijd</span>
					<input name="actual_time" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" placeholder="HH:MM:SS" value="<?php echo esc_attr($form_goal ? $form_goal->actualTime : ''); ?>">
				</label>
			</div>

			<label>
				<span>Beschrijving</span>
				<textarea name="description" rows="4"><?php echo esc_textarea($form_goal ? $form_goal->description : ''); ?></textarea>
			</label>

			<label class="lpt-goal-active-toggle">
				<input type="hidden" name="active" value="0">
				<input type="checkbox" name="active" value="1" <?php checked($form_goal ? $form_goal->active : true); ?>>
				<span>Actief</span>
			</label>

			<div class="lpt-goal-actions">
				<button type="submit"><?php echo esc_html($form_goal ? 'Wedstrijd opslaan' : 'Wedstrijd toevoegen'); ?></button>
				<?php if ($form_goal) : ?>
					<a class="lpt-goal-secondary" href="<?php echo esc_url($page_url); ?>">Annuleren</a>
				<?php endif; ?>
			</div>
		</form>
	</section>
</main>
