<?php
declare(strict_types=1);
namespace AccountKeying;

class AccountKeying
{    
    public function accCheck(string $newnum, string $account, $bic_check = null): bool
    {
        #Validate values
        if (preg_match('/^[0-9]{9}$/', $newnum) !== 1) {
            throw new \UnexpectedValueException('Wrong BIC format provided');
        }
        if (preg_match('/^[0-9АВСЕНКМРТХавсенкмртх]{20}$/', $account) !== 1) {
            throw new \UnexpectedValueException('Wrong account format provided');
        }
        $VK = [7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7,1];
        $rkcNum = [];
        $fullStr = [];
        $mult = [];
        $summ = 0;
        #Strings to arrays
        $newnum_split = str_split($newnum);
        $account_split = str_split(strtoupper($account));
        #Get current key
        $currKey = $account_split[8];
        #Some special accounts can have letters in them (although I have not seen any myself)
        switch($account_split[5]) {
            case 'A': $account_split[5] = 0; break;
            case 'B': $account_split[5] = 1; break;
            case 'C': $account_split[5] = 2; break; 
            case 'E': $account_split[5] = 3; break;
            case 'H': $account_split[5] = 4; break;
            case 'K': $account_split[5] = 5; break; 
            case 'M': $account_split[5] = 6; break;
            case 'P': $account_split[5] = 7; break;
            case 'T': $account_split[5] = 8; break;
            case 'X': $account_split[5] = 9; break; 
        }
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
                return false;
            }
        }
    }
}
?>