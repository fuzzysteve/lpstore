<?php
require 'vendor/autoload.php';
$redis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
));


function returnprice($typeid=34,$regionid='10000002')
{
        global $redis;
        $pricedatasell=$redis->get($regionid.'|'.$typeid.'|false');
        $pricedatabuy=$redis->get($regionid.'|'.$typeid.'|true');
        $values=explode("|",$pricedatasell);
        $price=$values[7];
        if (!(is_numeric($price)))
        {
            $price=0;
        }
        $values=explode("|",$pricedatabuy);
        $pricebuy=$values[7];
        if (!(is_numeric($pricebuy)))
        {
            $pricebuy=0;
        }

        return array($price,$pricebuy);

}

function returnvolume($typeid=34,$regionid='10000002')
{
        global $redis;
        $pricedatasell=$redis->get($regionid.'|'.$typeid.'|false');
        $pricedatabuy=$redis->get($regionid.'|'.$typeid.'|true');
        if (isset($pricedatasell))
        {
            $values=explode("|",$pricedatasell);
            $fivesell=$values[5];
            if (!(is_numeric($fivesell)))
            {
                $fivesell=0;
            }
        }
        else { $fivesell=0;}
        if (isset($pricedatabuy))
        {
            $values=explode("|",$pricedatabuy);
            $fivebuy=$values[5];
            if (!(is_numeric($fivebuy)))
            {
                $fivebuy=0;
            }
        }
        else { $fivebuy=0;}

        return array($fivesell,$fivebuy);

}


?>
