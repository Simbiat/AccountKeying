<?php
declare(strict_types=1);
namespace Simbiat;

class AccountKeying
{
    public function accCheck(string $bic_num, string $account, ?int $bic_check = null): int|bool
    {
        #Validate values
        if (preg_match('/^\d{9}$/', $bic_num) !== 1) {
            return false;
        }
        if (preg_match('/^\d{5}[\dАВСЕНКМРТХавсенкмртх]\d{14}$/', $account) !== 1) {
            return false;
        }
        $VK = [7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1];
        $rkcNum = [];
        $multi = [];
        $sum = 0;
        #Strings to arrays
        $bic_num_split = str_split($bic_num);
        $account_split = str_split(strtoupper($account));
        #Get current key
        $currKey = $account_split[8];
        #Some special accounts can have letters in them (although I have not seen any myself). They need to be replaced with regular numbers as per specification
        $account_split[5] = match($account_split[5]) {
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
        if(intval($bic_num_split[6].$bic_num_split[7].$bic_num_split[8]) <= 2) {
            $rkcNum[0] = 0;
            $rkcNum[1] = intval($bic_num_split[4]);
            $rkcNum[2] = intval($bic_num_split[5]);
        } else {
            $rkcNum[0] = intval($bic_num_split[6]);
            $rkcNum[1] = intval($bic_num_split[7]);
            $rkcNum[2] = intval($bic_num_split[8]);
        }
        if (is_null($bic_check)) {
            $account_split[8] = 0;
        }
        #Full string
        $fullStr = array_merge($rkcNum, $account_split);
        #Multiplication
        for ($i = 0; $i < 23; $i++) {
            $multi[$i]=intval($fullStr[$i])*$VK[$i];
        }
        #Summing
        for ($i = 0; $i < 23; $i++) {
            $sum += intval(str_split(strval($multi[$i]))[(count(str_split(strval($multi[$i]))) - 1)]);
        }
        #Second character
        $secCh = intval(str_split(strval($sum))[(count(str_split(strval($sum))) - 1)]);
        if (is_null($bic_check)) {
            $secCh = $secCh*3;
            $secCh= intval(str_split(strval($secCh))[(count(str_split(strval($secCh))) - 1)]);
            return $this->accCheck($bic_num, $account, $secCh);
        } else {
            if ($currKey == $bic_check && $secCh == 0) {
                return true;
            } else {
                return $bic_check;
            }
        }
    }
}
