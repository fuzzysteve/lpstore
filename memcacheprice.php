<?php

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");

function returnprice($typeid=34,$regionid='forge')
{
        global $memcache;
        $pricedatasell=$memcache->get($regionid.'sell-'.$typeid);
        $pricedatabuy=$memcache->get($regionid.'buy-'.$typeid);
        $values=explode("|",$pricedatasell);
        $price=$values[0];
        if (!(is_numeric($price)))
        {
            $price=0;
        }
        $values=explode("|",$pricedatabuy);
        $pricebuy=$values[0];
        if (!(is_numeric($pricebuy)))
        {
            $pricebuy=0;
        }

        return array($price,$pricebuy);

}

function returnvolume($typeid=34,$regionid='forge')
{
        global $memcache;
        $pricedatasell=$memcache->get($regionid.'sell-'.$typeid);
        $pricedatabuy=$memcache->get($regionid.'buy-'.$typeid);
        $values=explode("|",$pricedatasell);
        $fivesell=$values[2];
        if (!(is_numeric($fivesell)))
        {
            $fivesell=0;
        }
        $values=explode("|",$pricedatabuy);
        $fivebuy=$values[2];
        if (!(is_numeric($fivebuy)))
        {
            $fivebuy=0;
        }

        return array($fivesell,$fivebuy);

}


?>
