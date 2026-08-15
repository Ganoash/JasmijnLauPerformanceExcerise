<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

final class UserContext extends BaseAcceptanceContext
{
	/**
	 * @Given a logged-in athlete exists
	 */
	public function loggedInAthleteExists(): void
	{
		$this->state->athleteUserId = $this->createUser('athlete');
		$this->state->currentUserId = $this->state->athleteUserId;
		wp_set_current_user($this->state->currentUserId);
	}

	/**
	 * @Given an athlete exists
	 */
	public function athleteExists(): void
	{
		$this->state->athleteUserId = $this->createUser('athlete');
	}

	/**
	 * @Given a coach is logged in
	 */
	public function coachIsLoggedIn(): void
	{
		$this->state->coachUserId = $this->createUser(
			'coach',
			[
				'manage_training_schemas',
				'view_all_training_schemas',
			]
		);
		$this->state->currentUserId = $this->state->coachUserId;
		wp_set_current_user($this->state->currentUserId);
	}

	/**
	 * @Given nobody is logged in
	 */
	public function nobodyIsLoggedIn(): void
	{
		$this->state->currentUserId = 0;
		wp_set_current_user(0);
	}
}
