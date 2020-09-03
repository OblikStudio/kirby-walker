<?php

namespace Oblik\Walker\Util;

/**
 * Recursively returns the differences between two input arrays. Attempts to
 * compare arrays with IDs according to those IDs.
 */
class Diff
{
	/**
	 * Invokes a callback to replace entries in input A using any matching ones
	 * in input B. Missing entries are left as-is.
	 */
	public static function processKeyedArray(array $inputA, array $inputB, callable $handler, $key = 'id')
	{
		$inputAKeyed = array_column($inputA, null, $key);
		$inputBKeyed = array_column($inputB, null, $key);
		$result = [];

		foreach ($inputAKeyed as $id => $entryA) {
			$entryB = $inputBKeyed[$id] ?? null;

			if ($entryB) {
				$data = $handler($entryA, $entryB);

				if (is_array($data)) {
					// Make sure the ID sticks with the entry so it still
					// remains identifiable.
					$data[$key] = $id;

					array_push($result, $data);
				}
			}
		}

		return $result;
	}

	/**
	 * Recursively iterates $input, compares it to $snapshot, and returns only
	 * values from $input that were different in $snapshot.
	 */
	public static function process($input, $snapshot)
	{
		$result = null;

		if (is_array($input) && is_array($snapshot)) {
			$ids = array_column($input, 'id');

			if (count($ids) === count($input)) {
				// If all entries have IDs, assume the array is keyed and
				// process entries according to their IDs.
				$result = static::processKeyedArray($input, $snapshot, [static::class, 'process']);
			} else {
				foreach ($input as $key => $entry) {
					$snapshotEntry = $snapshot[$key] ?? null;
					$result[$key] = static::process($entry, $snapshotEntry);
				}
			}

			$result = array_filter($result);

			if (count($result) === 0) {
				$result = null;
			}
		} else if ($input !== $snapshot) {
			$result = $input;
		}

		return $result;
	}
}
