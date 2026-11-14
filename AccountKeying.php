<?php

declare(strict_types=1);

namespace Simbiat\BIC;

/**
 * Implementation of Central Bank of Russia's logic in account keying, which allows validation of account numbers.
 */
final class AccountKeying
{
    /**
     * Check if a provided account belongs to a respective bank code.
     *
     * @param int|string $bic_num   Bank Identification Code.
     * @param int|string $account   Account number.
     * @param int|null   $bic_check Pre-computed control digit. Calculated automatically, if null (default).
     *
     * @return bool|int
     */
    public static function accCheck(int|string $bic_num, int|string $account, ?int $bic_check = null): bool|int
    {
        $bic_num = (string) $bic_num;
        $account = (string) $account;
        // Validate values
        if (
            \preg_match('/^\d{9}$/u', $bic_num) !== 1
            || \preg_match('/^\d{5}[\dАВСЕНКМРТХавсенкмртх]\d{14}$/u', $account) !== 1
        ) {
            return false;
        }

        $vk = [7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1];
        // Strings to arrays
        $bic_num_split = \mb_str_split($bic_num, 1, 'UTF-8');
        $account_split = \mb_str_split(\mb_strtoupper($account, 'UTF-8'), 1, 'UTF-8');
        // Runtime-redundant (lengths are guaranteed by the regexes above), but comparing
        // against exact counts narrows the types to fixed-size tuples for static analysis,
        // making the offset accesses below provably safe.
        if (
            \count($bic_num_split) !== 9
            || \count($account_split) !== 20
        ) {
            return false;
        }

        // Get the current key
        $curr_key = $account_split[8];
        // Some special accounts can have letters in them (although I have not seen any myself).
        // They need to be replaced with regular numbers as per specification.
        $account_split[5] = match ($account_split[5]) {
            'A', 'а' => 0,
            'B', 'в' => 1,
            'C', 'с' => 2,
            'E', 'е' => 3,
            'H', 'н' => 4,
            'K', 'к' => 5,
            'M', 'м' => 6,
            'P', 'р' => 7,
            'T', 'т' => 8,
            'X', 'х' => 9,
            default => $account_split[5],
        };
        // RKC
        $rkc_num = self::generateRKC($bic_num_split);
        if ($bic_check === null) {
            $account_split[8] = 0;
        }

        // Full string (3 RKC digits + 20 account characters)
        $full_str = \array_merge($rkc_num, $account_split);

        // Weighted sum of the last digits of each product (steps 3 of key generation
        // and step 1 of key validation). Equivalent to splitting every product into
        // characters and adding the last one, but without array accesses.
        $sum = 0;
        for ($iteration = 0; $iteration < 23; ++$iteration) {
            $sum += (((int) $full_str[$iteration]) * $vk[$iteration]) % 10;
        }

        // Second character: the last digit of the sum
        $sec_ch = $sum % 10;
        if ($bic_check === null) {
            $sec_ch = ($sec_ch * 3) % 10;

            return self::accCheck($bic_num, $account, $sec_ch);
        }

        if (
            $curr_key === (string) $bic_check
            && $sec_ch === 0
        ) {
            return true;
        }

        return $bic_check;
    }

    /**
     * Generates RKC number in an array format based on BIC number.
     *
     * @param array{string, string, string, string, string, string, string, string, string} $bic_num_split BIC number split into an array
     *
     * @return array{int, int, int}
     */
    private static function generateRKC(array $bic_num_split): array
    {
        $tail = $bic_num_split[6].$bic_num_split[7].$bic_num_split[8];
        if ((int) $tail <= 2) {
            return [0, (int) $bic_num_split[4], (int) $bic_num_split[5]];
        }

        return [(int) $bic_num_split[6], (int) $bic_num_split[7], (int) $bic_num_split[8]];
    }
}
