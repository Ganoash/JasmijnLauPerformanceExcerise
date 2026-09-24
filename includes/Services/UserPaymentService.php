<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Validation\DateValidator;

final class UserPaymentService
{
	public const META_NEXT_PAYMENT_DATE = 'lpt_next_payment_date';

	private const TIMEZONE = 'Europe/Amsterdam';

	public function __construct(
		private readonly ?DateValidator $date_validator = null,
		private readonly ?DateFactory $date_factory = null
	) {
	}

	public function nextPaymentDate(int $user_id): string
	{
		$value = get_user_meta($user_id, self::META_NEXT_PAYMENT_DATE, true);

		return is_string($value) ? $value : '';
	}

	public function setNextPaymentDate(int $user_id, string $date): void
	{
		$date = trim($date);
		if ($date === '') {
			delete_user_meta($user_id, self::META_NEXT_PAYMENT_DATE);
			return;
		}

		$this->dateValidator()->assertValidRequestDate($date);
		update_user_meta($user_id, self::META_NEXT_PAYMENT_DATE, $date);
	}

	public function advanceNextPaymentDate(int $user_id, string $date = ''): string
	{
		$current = trim($date) !== '' ? trim($date) : $this->nextPaymentDate($user_id);
		if ($current === '') {
			$current = $this->today();
		}

		$this->dateValidator()->assertValidRequestDate($current);
		$next = $this->addFourWeeks($current);
		update_user_meta($user_id, self::META_NEXT_PAYMENT_DATE, $next);

		return $next;
	}

	public function isOverdue(string $date): bool
	{
		if ($date === '') {
			return false;
		}

		try {
			$this->dateValidator()->assertValidRequestDate($date);
		} catch (InvalidArgumentException) {
			return false;
		}

		return $date < $this->today();
	}

	private function addFourWeeks(string $date): string
	{
		$current = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(self::TIMEZONE));
		if (! $current) {
			throw new InvalidArgumentException('Ongeldige datum.');
		}

		return $current->modify('+4 weeks')->format('Y-m-d');
	}

	private function today(): string
	{
		return $this->dateFactory()->now()->format('Y-m-d');
	}

	private function dateValidator(): DateValidator
	{
		return $this->date_validator ?? new DateValidator();
	}

	private function dateFactory(): DateFactory
	{
		return $this->date_factory ?? new DateFactory();
	}
}
