<?php
declare(strict_types=1);
namespace AccountKeying;

class AccountKeying
{    
    public function accCheck(string $newnum, string $account, $bic_check = null): bool
    {
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
        switch($account_split[5]) {
            case 'A':$account_split[5]=0;break;
            case 'B':$account_split[5]=1;break;
            case 'C':$account_split[5]=2;break; 
            case 'E':$account_split[5]=3;break;
            case 'H':$account_split[5]=4;break;
            case 'K':$account_split[5]=5;break; 
            case 'M':$account_split[5]=6;break;
            case 'P':$account_split[5]=7;break;
            case 'T':$account_split[5]=8;break;
            case 'X':$account_split[5]=9;break; 
        }
        #RKC
        if((int)($newnum_split[6].$newnum_split[7].$newnum_split[8]) <= 2) {
            $rkcNum[0]=0;
            $rkcNum[1]=(int)$newnum_split[4];
            $rkcNum[2]=(int)$newnum_split[5];
        } else {
            $rkcNum[0]=(int)$newnum_split[6];
            $rkcNum[1]=(int)$newnum_split[7];
            $rkcNum[2]=(int)$newnum_split[8];
        }
        if (is_null($bic_check)) {
            $account_split[8] = 0;
        }
        #Full string
        $fullStr = array_merge($rkcNum, $account_split);
        #Multiplication
        for ($i = 0; $i < 23; $i++) {
            $mult[$i]=((int)$fullStr[$i])*$VK[$i];
        }
        #Summing
        for ($i = 0; $i < 23; $i++) {
            $summ += (int)str_split($mult[$i])[(count(str_split($mult[$i])) - 1)];
        }
        #Second character
        $secCh = (int)str_split($summ)[(count(str_split($summ)) - 1)];
        if (is_null($bic_check)) {
            $secCh = $secCh*3;
            $secCh= (int)str_split($secCh)[(count(str_split($secCh)) - 1)];
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