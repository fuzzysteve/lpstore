<?php
$expires = 3599;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
require_once('db.inc.php');
$pricetype='redis';
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);



require_once($pricetype.'price.php');
$corpid=0;
if (array_key_exists('corpid', $_POST) && is_numeric($_POST['corpid'])) {
    $corpid=$_POST['corpid'];
} elseif (array_key_exists('corpid', $_GET) &&is_numeric($_GET['corpid'])) {
    $corpid=$_GET['corpid'];
}

if (array_key_exists('buy', $_GET) && is_numeric($_GET['buy'])) {
    $method="buy";
} else {
    $method="sell";
}
if (isset($method2)) {
    $method=$method2;
}

if (array_key_exists('blueprints', $_GET)||array_key_exists('blueprints', $_POST)) {
    $blueprints=1;
    $urlsuffix="/withblueprints";
} else {
    $blueprints=0;
    $urlsuffix="";
}

$regionid=10000002;
if (array_key_exists('region', $_POST) && is_numeric($_POST['region'])) {
    $regionid=$_POST['region'];
} elseif (array_key_exists('corpid', $_GET) &&is_numeric($_GET['region'])) {
    $regionid=$_GET['region'];
}

if ($regionid==10000002) {
    $region="forge";
} else {
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
<title>LP Store - Return on ISK - 
<?php echo $corpname." - ".ucfirst($regionname)." ".ucfirst($method); ?>
</title>
  <link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <link href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css"
  rel="stylesheet" type="text/css"/>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  <script type="text/javascript" src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js">
  </script>

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
            "aoColumns":[
                null,
                {"sType": "currency" },
                { "sType": "currency" },
                null,
                null,
                { "sType": "currency" },
                { "sType": "currency" },
                { "sType": "currency" },
                { "sType": "currency" },
                { "sType": "currency" }
            ]
});
        try {
            var stateObj = {};
            history.pushState(stateObj,"" , "https://www.fuzzwork.co.uk/lpstore/<?php echo $method."/".$regionid."/".$corpid.$urlsuffix;?>");
       }
       catch(err) { console.log("No pushstate");}

    }
);
</script>
<link href="/lpstore/style.css" rel="stylesheet" type="text/css"/>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/header.php'); ?>
</head>
<body>
<?php include('/home/web/fuzzwork/htdocs/menu/menubootstrap.php'); ?>
<div class="container">
<h1><a href="//www.fuzzwork.co.uk/lpstore/<?php echo $method."/".$regionid."/".$corpid.$urlsuffix;?>">
<?php echo $corpname; ?></a> 
<?php echo ucfirst($regionname); ?>
<?php echo ucfirst($method);?> Prices</h1>
<table border=1 id="lp" class="tablesorter">
<thead>
<tr>
    <th>id</th>
    <th>LP</th>
    <th>Isk</th>
    <th>Item</th>
    <th>Other Requirements</th>
    <th>Other Cost</th>
    <th>Quantity</th>
    <th><?php echo ucfirst($method);?> Price</th>
    <th>5% Volume</th>
    <th>isk/lp</th>
</tr>
</thead>
<tbody>
<?php


if ($blueprints) {
    $sql=<<<EOS
    select lpOffers.offerID id,
        it1.typename,
        it1.typeid,
        lpOffers.quantity,
        lpcost,
        iskCost,
        coalesce(productTypeID,0) productTypeID
    from lpstore.lpStore 
    join lpstore.lpOffers on lpStore.offerID=lpOffers.offerID  
    join eve.invTypes it1 on (lpOffers.typeid=it1.typeid) 
    left join eve.industryActivityProducts iap on 
        (lpOffers.typeid=iap.typeID and activityid=1) where corporationID=?
EOS;
} else {
    $sql=<<<EOS
    select lpOffers.offerID id,
        it1.typename,
        it1.typeid,
        lpOffers.quantity,
        lpcost,
        iskCost,
        0 productTypeID
    from lpstore.lpStore 
    join lpstore.lpOffers on lpStore.offerID=lpOffers.offerID
    join eve.invTypes it1 on (lpOffers.typeid=it1.typeid)
    where corporationID=? and marketgroupid is not null
EOS;
}

$stmt = $dbh->prepare($sql);
$requiredsql=<<<EOS
    select typename,
        invTypes.typeid,
        quantity 
    from eve.invTypes ,lpstore.lpOfferRequirements 
    where offerid=? and invTypes.typeid=lpOfferRequirements.typeid
EOS;
$stmt2 = $dbh->prepare($requiredsql);
$basicmaterials=<<<EOS
    SELECT typename,it.typeid,quantity*:quantity quantity
    FROM eve.industryActivityMaterials iam
    JOIN eve.invTypes it on iam.materialtypeid=it.typeid
    where iam.typeid=:typeid
EOS;
$basicstmt=$dbh->prepare($basicmaterials);


$stmt->execute(array($corpid));

while ($row = $stmt->fetchObject()) {
    $cost=0;
    $otherprice=0;

    if ($row->productTypeID>0) {
        # it's a blueprint! grab the price of the final thing
        $typeid=$row->productTypeID;
    } else {
        $typeid=$row->typeid;
    }
    $actualtypeid=$row->typeid;
    list($price, $pricebuy)=returnprice($typeid, $region);
    list($volume, $fivebuy)=returnvolume($typeid, $region);
    if ($method=='buy') {
        $price=$pricebuy;
        $volume=$fivebuy;
    }
    if ($price=="") {
        $price=0;
    }
    $cost=$row->iskCost;

    $stmt2->execute(array($row->id));


    $other="<table  class='tablesorter'><tr><th colspan=2>LP Store</th></tr>";
    while ($row2 = $stmt2->fetchObject()) {
        $other.="<tr><td>".$row2->quantity."</td><td>".$row2->typename."</td></tr>";
        list($innerprice, $pricebuy)=returnprice($row2->typeid, $region);
        if ($innerprice=="") {
            $innerprice=0;
        }

        $otherprice+=$innerprice*$row2->quantity;


    }
    $other.="</table>";

    if ($row->productTypeID>0) {
        $materials=array();
        $other.="<table  class='tablesorter'><tr><th colspan=2>Materials to build</th></tr>";
        $basicstmt->execute(array(':typeid'=>$actualtypeid,':quantity'=>$row->quantity));
        while ($basic = $basicstmt->fetchObject()) {
            if (array_key_exists($basic->typeid.'|'.$basic->typename, $materials)) {
                $materials[$basic->typeid.'|'.$basic->typename]+=$basic->quantity;
            } else {
                $materials[$basic->typeid.'|'.$basic->typename]=$basic->quantity;
            }
        }
        foreach ($materials as $key => $quantity) {
            $matdetails=explode("|", $key);
            $other.="<tr><td>".$quantity."</td><td>".$matdetails[1]."</td></tr>";
            list($innerprice, $pricebuy)=returnprice($matdetails[0], $region);
            if ($innerprice=="") {
                $innerprice=0;
            }

            $otherprice+=$innerprice*$quantity;


        }

        $other.="</table>";
    }

    $cost+=$otherprice;
    $ratio=(($row->quantity*$price)-$cost)/$row->lpcost;
    $class="bad";
    if ($ratio>1000) {
        $class="good";
    } elseif ($ratio>500) {
        $class="ok";
    }
    echo "<tr><td>$row->id</td><td>".number_format($row->lpcost)."</td>";
    echo "<td>".number_format($row->iskCost)."</td><td data-typeid='".$row->typeid."'>";
    echo "<a href='https://www.fuzzwork.co.uk/market/marketdisplay.php?typeid=".$row->typeid."&regionid=".$regionid."' target='_blank'>";
    echo $row->typename."</a></td><td>".$other."</td><td>".number_format($otherprice)."</td><td>".$row->quantity."</td><td>".number_format($price, 2)."</td><td>".$volume."</td><td class=\"$class\">".$ratio."</td></tr>\n";
}
?>
</tbody>
</table>
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>

<!-- Generated <?php echo date(DATE_RFC822);?> -->
</body>
</html>

