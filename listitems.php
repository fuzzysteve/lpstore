<?php
$expires = 3599;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
require_once('db.inc.php');


if (array_key_exists('item', $_POST)) {
    $item=$_POST['item'];
} else {
    exit;
}

 $sql="select typename,typeid from eve.invTypes where lower(typename)=lower(?)";

$stmt = $dbh->prepare($sql);
 $stmt->execute(array($item));
if ($row = $stmt->fetchObject()) {
    $itemname=$row->typename;
    $itemid=$row->typeid;
} else {
    exit;
}

$sql="select distinct corp.itemname,lpOffers.corporationID,faction.itemname faction
    from lpstore2.lpOffers 
    join eve.invNames corp on lpOffers.corporationID=corp.itemid
join lpstore2.crpNPCCorporations on lpOffers.corporationID=crpNPCCorporations.corporationID
join lpstore.invNames faction on crpNPCCorporations.factionID=faction.itemid
where lpOffers.typeid=?
order by corp.itemname asc";
$stmt = $dbh->prepare($sql);
$stmt->execute(array($itemid));



?>
<html>
<head>
<title>LP Store - Store Finder</title>
  <link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <link href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css"
  rel="stylesheet" type="text/css"/>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  <script type="text/javascript" src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js">
  </script>

<script>
$(document).ready(function()
    {
        $("#lp").dataTable({
});
    }
);
</script>
<link href="/lpstore/style.css" rel="stylesheet" type="text/css"/>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/header.php'); ?>
</head>
<body>
<?php include('/home/web/fuzzwork/htdocs/menu/menubootstrap.php'); ?>
<div class="container">
<h1><?php 
echo $itemname;
if (strpos($itemname,"Blueprint")) {
$blueprint="/withblueprints";
} else {
$blueprint="";
}


 ?></h1>
<table border=1 id="lp" class="tablesorter">
<thead>
<tr><th>Corporation</th><th>faction</th><th>buy/sell</th></tr>
</thead>
<tbody>
<?php

while ($row = $stmt->fetchObject()) {
    echo "<tr><td>".$row->itemname."</td><td>".$row->faction."</td><td>";
    echo "<a href='https://www.fuzzwork.co.uk/lpstore/sell/10000002/".$row->corporationID.$blueprint."'>Sell</a>/";
    echo "<a href='https://www.fuzzwork.co.uk/lpstore/buy/10000002/".$row->corporationID.$blueprint."'>Buy</a>";
}
?>
</tbody>
</table>
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>

<!-- Generated <?php echo date(DATE_RFC822);?> -->
</body>
</html>

