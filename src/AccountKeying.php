<?php
declare(strict_types = 1);

namespace Simbiat\BIC;

use function count;

/**
 * Implementation of Central Bank of Russia's logic in account keying, which allows validation of account numbers.
 */
class AccountKeying
{
    /**
     * Check if a provided account belongs to a respective bank code
     *
     * @param int|string $bic_num Bank Identification Code
     * @param int|string $account Account number
     * @param int|null   $bic_check
     *
     * @return int|bool
     */
    public static function accCheck(int|string $bic_num, int|string $account, ?int $bic_check = null): int|bool
    {
        $bic_num = (string)$bic_num;
        $account = (string)$account;
        #Validate values
        if (preg_match('/^\d{9}$/', $bic_num) !== 1 || preg_match('/^\d{5}[\dАВСЕНКМРТХавсенкмртх]\d{14}$/u', $account) !== 1) {
            return false;
        }
        $vk = [7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1];
        $multi = [];
        $sum = 0;
        #Strings to arrays
        $bic_num_split = mb_str_split($bic_num, 1, 'UTF-8');
        $account_split = mb_str_split(mb_strtoupper($account, 'UTF-8'), 1, 'UTF-8');
        #Get the current key
        $currKey = $account_split[8];
        #Some special accounts can have letters in them (although I have not seen any myself). They need to be replaced with regular numbers as per specification
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
        #RKC
        $rkcNum = self::generateRKC($bic_num_split);
        if ($bic_check === null) {
            $account_split[8] = 0;
        }
        #Full string
        $fullStr = array_merge($rkcNum, $account_split);
        #Multiplication
        for ($i = 0; $i < 23; $i++) {
            $multi[$i] = (int)$fullStr[$i] * $vk[$i];
        }
        #Summing
        $sum = self::sumNumbers($multi, $sum);
        #Second character
        $secCh = (int)mb_str_split((string)$sum, 1, 'UTF-8')[(count(mb_str_split((string)$sum, 1, 'UTF-8')) - 1)];
        if ($bic_check === null) {
            $secCh *= 3;
            $secCh = (int)mb_str_split((string)$secCh, 1, 'UTF-8')[(count(mb_str_split((string)$secCh, 1, 'UTF-8')) - 1)];
            return self::accCheck($bic_num, $account, $secCh);
        }
        if ($currKey === (string)$bic_check && $secCh === 0) {
            return true;
        }
        return $bic_check;
    }
    
    /**
     * Generates RKC number in an array format based on BIC number
     * @param array $bic_num_split BIC number split into an array
     *
     * @return array
     */
    private static function generateRKC(array $bic_num_split): array
    {
        if ((int)($bic_num_split[6].$bic_num_split[7].$bic_num_split[8]) <= 2) {
            $rkcNum[0] = 0;
            $rkcNum[1] = (int)$bic_num_split[4];
            $rkcNum[2] = (int)$bic_num_split[5];
        } else {
            $rkcNum[0] = (int)$bic_num_split[6];
            $rkcNum[1] = (int)$bic_num_split[7];
            $rkcNum[2] = (int)$bic_num_split[8];
        }
        return $rkcNum;
    }
    
    /**
     * Implementing sum operation for all digits of the key (step 3 of key generation and step 2 of key validation)
     * @param array $multi
     * @param int   $sum
     * @return int
     */
    private static function sumNumbers(array $multi, int $sum): int
    {
        for ($i = 0; $i < 23; $i++) {
            $sum += (int)mb_str_split((string)$multi[$i], 1, 'UTF-8')[(count(mb_str_split((string)$multi[$i], 1, 'UTF-8')) - 1)];
        }
        return $sum;
    }
}
