<?php
declare(strict_types=1);
namespace Simbiat;


class AccountKeying
{
    public function accCheck(string $newnum, string $account, ?int $bic_check = null): int|bool
    {
        #Validate values
        if (preg_match('/^[0-9]{9}$/', $newnum) !== 1) {
            return false;
        }
        if (preg_match('/^[0-9]{5}[0-9АВСЕНКМРТХавсенкмртх][0-9]{14}$/', $account) !== 1) {
            return false;
        }
        $VK = [7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1];
        $rkcNum = [];
        $mult = [];
        $summ = 0;
        #Strings to arrays
        $newnum_split = str_split($newnum);
        $account_split = str_split(strtoupper($account));
        #Get current key
        $currKey = $account_split[8];
        #Some special accounts can have letters in them (although I have not seen any myself). They need to be replaced with regulat numbers as per specification
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
        if(intval($newnum_split[6].$newnum_split[7].$newnum_split[8]) <= 2) {
            $rkcNum[0] = 0;
            $rkcNum[1] = intval($newnum_split[4]);
            $rkcNum[2] = intval($newnum_split[5]);
        } else {
            $rkcNum[0] = intval($newnum_split[6]);
            $rkcNum[1] = intval($newnum_split[7]);
            $rkcNum[2] = intval($newnum_split[8]);
        }
        if (is_null($bic_check)) {
            $account_split[8] = 0;
        }
        #Full string
        $fullStr = array_merge($rkcNum, $account_split);
        #Multiplication
        for ($i = 0; $i < 23; $i++) {
            $mult[$i]=intval($fullStr[$i])*$VK[$i];
        }
        #Summing
        for ($i = 0; $i < 23; $i++) {
            $summ += intval(str_split(strval($mult[$i]))[(count(str_split(strval($mult[$i]))) - 1)]);
        }
        #Second character
        $secCh = intval(str_split(strval($summ))[(count(str_split(strval($summ))) - 1)]);
        if (is_null($bic_check)) {
            $secCh = $secCh*3;
            $secCh= intval(str_split(strval($secCh))[(count(str_split(strval($secCh))) - 1)]);
            return $this->accCheck($newnum, $account, $secCh);
        } else {
            if ($currKey == $bic_check && $secCh == 0) {
                return true;
            } else {
                return $bic_check;
            }
        }
    }
}
