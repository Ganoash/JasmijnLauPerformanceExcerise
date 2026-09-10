<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class HeartRateZones
{
	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly string $lactateTestDate,
		public readonly ?int $zone1Upper,
		public readonly ?int $zone2Upper,
		public readonly ?int $zone3Upper,
		public readonly ?int $zone4Upper,
		public readonly string $createdAt,
		public readonly string $updatedAt,
	) {
	}

	/**
	 * @param array<string,mixed> $row
	 */
	public static function fromRow(array $row): self
	{
		return new self(
			(int) $row['id'],
			(int) $row['user_id'],
			(string) $row['lactate_test_date'],
			self::nullableInt($row['zone_1_upper'] ?? null),
			self::nullableInt($row['zone_2_upper'] ?? null),
			self::nullableInt($row['zone_3_upper'] ?? null),
			self::nullableInt($row['zone_4_upper'] ?? null),
			(string) $row['created_at'],
			(string) $row['updated_at']
		);
	}

	/**
	 * @return array<int,array{zone:int,label:string,color:string}>
	 */
	public function displayRanges(): array
	{
		if ($this->zone1Upper === null) {
			return [];
		}

		$ranges = [
			[
				'zone'  => 1,
				'label' => sprintf('Zone 1: tot en met %d bpm', $this->zone1Upper),
				'color' => 'grey',
			],
		];

		if ($this->zone2Upper === null) {
			return $ranges;
		}

		$ranges[] = [
			'zone'  => 2,
			'label' => sprintf('Zone 2: %d-%d bpm', $this->zone1Upper + 1, $this->zone2Upper),
			'color' => 'blue',
		];

		if ($this->zone3Upper === null) {
			return $ranges;
		}

		$ranges[] = [
			'zone'  => 3,
			'label' => sprintf('Zone 3: %d-%d bpm', $this->zone2Upper + 1, $this->zone3Upper),
			'color' => 'green',
		];

		if ($this->zone4Upper === null) {
			return $ranges;
		}

		$ranges[] = [
			'zone'  => 4,
			'label' => sprintf('Zone 4: %d-%d bpm', $this->zone3Upper + 1, $this->zone4Upper),
			'color' => 'orange',
		];
		$ranges[] = [
			'zone'  => 5,
			'label' => sprintf('Zone 5: vanaf %d bpm', $this->zone4Upper + 1),
			'color' => 'red',
		];

		return $ranges;
	}

	private static function nullableInt(mixed $value): ?int
	{
		return $value === null ? null : (int) $value;
	}
}
