<?php
$expires = 3599;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
require_once('db.inc.php');

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");
$corpid=0;
if (array_key_exists('corpid',$_POST) && is_numeric($_POST['corpid']))
{
    $corpid=$_POST['corpid'];
}
else if (array_key_exists('corpid',$_GET) &&is_numeric($_GET['corpid']))
{
    $corpid=$_GET['corpid'];
}

if (array_key_exists('buy',$_GET) && is_numeric($_GET['buy']))
{
    $method="buy";
}
else
{
    $method="sell";
}
if (isset($method2))
{
    $method=$method2;
}

if (array_key_exists('blueprints',$_GET)||array_key_exists('blueprints',$_POST))
{
    $blueprints=1;
    $urlsuffix="/withblueprints";
}
else
{
    $blueprints=0;
    $urlsuffix="";
}

$regionid=10000002;
if (array_key_exists('region',$_POST) && is_numeric($_POST['region']))
{
    $regionid=$_POST['region'];
}
else if (array_key_exists('corpid',$_GET) &&is_numeric($_GET['region']))
{
    $regionid=$_GET['region'];
}

if ($regionid==10000002)
{
    $region="forge";
}
else
{
    $region=$regionid;
}

$regionnamesql="select regionname from eve.mapRegions where regionid=?";
$stmt = $dbh->prepare($regionnamesql);
$stmt->execute(array($regionid));
$row = $stmt->fetchObject();
$regionname=$row->regionname;




$corpnamesql="select itemName from eve.invNames where eve.invNames.itemID=?";
$stmt = $dbh->prepare($corpnamesql);
$stmt->execute(array($corpid));
$row = $stmt->fetchObject();
$corpname=$row->itemName;

?>
<html>
<head>
<title>LP Store - Return on ISK - <? echo $corpname ?> - <? echo ucfirst($regionname); ?> <? echo ucfirst($method); ?></title>
  <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <link href="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" rel="stylesheet" type="text/css"/>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  <script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>
<script>
jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "currency-pre": function ( a ) {
        a = (a==="-") ? 0 : a.replace( /[^\d\-\.]/g, "" );
        return parseFloat( a );
    },
 
    "currency-asc": function ( a, b ) {
        return a - b;
    },
 
    "currency-desc": function ( a, b ) {
        return b - a;
    }
} );


$(document).ready(function()
    {
        $("#lp").dataTable({
            "aoColumns":[null,{"sType": "currency" },{ "sType": "currency" },null,null,{ "sType": "currency" },{ "sType": "currency" },{ "sType": "currency" },{ "sType": "currency" },{ "sType": "currency" }]
});
    }
);
</script>
<link href="/lpstore/style.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<h1><a href="http://www.fuzzwork.co.uk/lpstore/<? echo $method;?>/<? echo $regionid ?>/<? echo $corpid; echo $urlsuffix;?>"><? echo $corpname; ?></a> <? echo ucfirst($regionname); ?> <? echo ucfirst($method);?> Prices</h1>
<table border=1 id="lp" class="tablesorter">
<thead>
<tr><th>id</th><th>LP</th><th>Isk</th><th>Item</th><th>Other Requirements</th><th>Other Cost</th><th>Quantity</th><th><? echo ucfirst($method);?> Price</th><th>5% Volume</th><th>isk/lp</th></tr>
</thead>
<tbody>
<?


if ($blueprints)
{
$sql='select lpOffers.offerID id,it1.typename,it1.typeid,quantity,lpcost,iskCost,coalesce(productTypeID,0) productTypeID,(wasteFactor/100)+1 wasteFactor from lpstore.lpStore join lpstore.lpOffers on lpStore.offerID=lpOffers.offerID  join eve.invTypes it1 on (lpOffers.typeid=it1.typeid) left join eve.invBlueprintTypes on (lpOffers.typeid=eve.invBlueprintTypes.blueprintTypeID) where corporationID=?';
#select storeid id,it1.typename,it1.typeid,quantity,lpcost,iskCost,coalesce(productTypeID,0) productTypeID,(wasteFactor/100)+1 wasteFactor from evesupport.lpStore join eve.invTypes it1 on (lpStore.typeid=it1.typeid) left join eve.invBlueprintTypes on (lpStore.typeid=eve.invBlueprintTypes.blueprintTypeID) where corporationID=?';
}
else
{
$sql='select lpOffers.offerID id,it1.typename,it1.typeid,quantity,lpcost,iskCost,0 productTypeID,1 wasteFactor from lpstore.lpStore join lpstore.lpOffers on lpStore.offerID=lpOffers.offerID join eve.invTypes it1 on (lpOffers.typeid=it1.typeid) where corporationID=? and marketgroupid is not null';
#select storeid id,it1.typename,it1.typeid,quantity,lpcost,iskCost,0 productTypeID,1 wasteFactor from evesupport.lpStore join eve.invTypes it1 on (lpStore.typeid=it1.typeid) where corporationID=? and marketgroupid is not null';
}

$stmt = $dbh->prepare($sql);
$requiredsql='select typename,invTypes.typeid,quantity from eve.invTypes ,lpstore.lpOfferRequirements where offerid=? and invTypes.typeid=lpOfferRequirements.typeid';
$stmt2 = $dbh->prepare($requiredsql);
$basicmaterials='SELECT typeid,name typename,round(greatest(0,SUM(quantity))*:waste*:quantity) quantity FROM (
  SELECT invTypes.typeid typeid,invTypes.typeName name,quantity
  FROM invTypes,invTypeMaterials
  WHERE invTypeMaterials.materialTypeID=invTypes.typeID
   AND invTypeMaterials.TypeID=:typeid
  UNION
  SELECT invTypes.typeid typeid,invTypes.typeName name,
         invTypeMaterials.quantity*r.quantity*-1 quantity 
  FROM invTypes,invTypeMaterials,ramTypeRequirements r,invBlueprintTypes bt
  WHERE invTypeMaterials.materialTypeID=invTypes.typeID 
   AND invTypeMaterials.TypeID =r.requiredTypeID
   AND r.typeID = bt.blueprintTypeID
   AND r.activityID = 1 AND bt.productTypeID=:typeid AND r.recycle=1
) t GROUP BY typeid,name';
$basicstmt=$dbh->prepare($basicmaterials);
$extramaterials='SELECT t.typeName typename, r.quantity*r.damagePerJob*:quantity quantity,t.typeID typeid
FROM ramTypeRequirements r,invTypes t,invBlueprintTypes bt,invGroups g
WHERE r.requiredTypeID = t.typeID AND r.typeID = bt.blueprintTypeID
AND r.activityID = 1 AND bt.productTypeID=:typeid AND g.categoryID != 16
AND t.groupID = g.groupID';
$extrastmt=$dbh->prepare($extramaterials);


$stmt->execute(array($corpid));

while ($row = $stmt->fetchObject()){
    $cost=0;
    $otherprice=0;

    if ($row->productTypeID>0)
    {
        # it's a blueprint! grab the price of the final thing
        $typeid=$row->productTypeID;
    }
    else
    {
    $typeid=$row->typeid;
    }
    $pricedatasell=$memcache->get($region.$method.'-'.$typeid);
    $values=explode("|",$pricedatasell);
    $price=$values[0];
    if (array_key_exists(2,$values))
    {
        $volume=$values[2];
    }
    else
    {$volume=0;}
    if ($price=="")
    {
        $price=0;
    }
    $cost=$row->iskCost;

    $stmt2->execute(array($row->id));


    $other="<table  class='tablesorter'><tr><th colspan=2>LP Store</th></tr>";
    while ($row2 = $stmt2->fetchObject()){
        $other.="<tr><td>".$row2->quantity."</td><td>".$row2->typename."</td></tr>";
        $pricedatasell=$memcache->get($region.'sell-'.$row2->typeid);
        $values=explode("|",$pricedatasell);
        $innerprice=$values[0];
        if ($innerprice=="")
        {
            $innerprice=0;
        }

        $otherprice+=$innerprice*$row2->quantity;


    }
    $other.="</table>";

    if ($row->productTypeID>0)
    {
        $materials=array();
        $other.="<table  class='tablesorter'><tr><th colspan=2>Materials to build</th></tr>";
        $basicstmt->execute(array(':typeid'=>$typeid,':waste'=>$row->wasteFactor,':quantity'=>$row->quantity));
        while ($basic = $basicstmt->fetchObject()){
            if ($basic->quantity==0){continue;};
            if (array_key_exists($basic->typeid.'|'.$basic->typename,$materials))
            {
                $materials[$basic->typeid.'|'.$basic->typename].=$basic->quantity;
            }
            else
            {
                $materials[$basic->typeid.'|'.$basic->typename]=$basic->quantity;
            }
        }
        $extrastmt->execute(array(':typeid'=>$typeid,':quantity'=>$row->quantity));
        while ($extra = $extrastmt->fetchObject()){
            if (array_key_exists($extra->typeid.'|'.$extra->typename,$materials))
            {
                $materials[$extra->typeid.'|'.$extra->typename].=$extra->quantity;
            }
            else
            {
                $materials[$extra->typeid.'|'.$extra->typename]=$extra->quantity;
            }
        }
        foreach ($materials as $key => $quantity){
            $matdetails=explode("|",$key);
            $other.="<tr><td>".$quantity."</td><td>".$matdetails[1]."</td></tr>";
            $pricedatasell=$memcache->get($region.'sell-'.$matdetails[0]);
            $values=explode("|",$pricedatasell);
            $innerprice=$values[0];
            if ($innerprice=="")
            {
                $innerprice=0;
            }

            $otherprice+=$innerprice*$quantity;


    }




        $other.="</table>";
    }

    $cost+=$otherprice;
    $ratio=(($row->quantity*$price)-$cost)/$row->lpcost;
    $class="bad";
    if ($ratio>1000){
        $class="good";
    }
    else if  ($ratio>500){
        $class="ok";
    }
    echo "<tr><td>$row->id</td><td>".number_format($row->lpcost)."</td><td>".number_format($row->iskCost)."</td><td>".$row->typename."</td><td>".$other."</td><td>".number_format($otherprice)."</td><td>".$row->quantity."</td><td>".number_format($price,2)."</td><td>".$volume."</td><td class=\"$class\">".$ratio."</td></tr>\n";
}
?>
</tbody>
</table>
<?php include('/home/web/fuzzwork/analytics.php'); ?>

<!-- Generated <? echo date(DATE_RFC822);?> -->
</body>
</html>

