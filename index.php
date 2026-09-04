<?php

require_once('db.inc.php');
?>
<html>
<head>
<title>LP Store - Return on ISK</title>
<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script src="/lpstore/items.php"></script>
<script src="/lpstore/js.cookie.js"></script>

<script>
$(document).ready(function() {
    $("input#item").autocomplete({ source: source });
    if (Cookies.get('checked') == "checked" ){
        document.getElementById('hidden').style.display='block'
        document.getElementById('checkbutton').style.display='none'
    }
});
</script>

<?php include('/home/web/fuzzwork/htdocs/bootstrap/header.php'); ?>
</head>
<body>
<?php include('/home/web/fuzzwork/htdocs/menu/menubootstrap.php'); ?>
<div class="container">
<div class='row'><div class="span10">
<p>Select your corporation, to see what ratio of isk to lp you can get with your corporation's LP. No warranty is given for any purpose. Any figures here are merely a guide and should be treated with appropriate caution, ideally being checked before you blow all your LP buying something that's been manipulated. No, the Zainou 'Gypsy' Weapon Disruption WD-903 is not worth 36,000 ISK per LP. Best thing I'd suggest? Look at the jita volume column for a high value. High numbers here are less likely to be manipulated values.</p>
<p>All the blueprints assume that you have production efficiency 5. If you don't, they will not be as profitable, as an extra 25% or so materials will be required.</p>
<p>Prices are as per a simulated 5% buy from the Jita market. The (jita buy) option uses Jita sell prices for all the components, but the price for the final item is the buy price. (In case you just want to dump it). Keep an eye on the volume, to see if the market can easily absorb the number you're thinking about, if you don't want to sell them yourself. Prices can be manipulated, so watch out for that.</p>
<P>You can now pick the region you want to see prices from. Completeness of price data is not guaranteed. There's a reason people use Jita</p>
</div>
</div>
<a class="btn btn-primary" id="checkbutton" onclick="document.getElementById('hidden').style.display='block';Cookies.set('checked','checked',{expires:3650})">I've read the above and understand it</a>
<div id="hidden" style='display:none'>
<form action="listcorp.php" method="post">
<select name="corpid">
<?php

$sql='select distinct itemName,itemID from lpstore2.lpOffers join eve.invNames on corporationID=itemid order by itemName Asc';

$stmt = $dbh->prepare($sql);

$stmt->execute();

while ($row = $stmt->fetchObject()) {
    $name=$row->itemName;
    echo "<option value=";
    echo  '"'.$row->itemID.'">'.$name.'</option>';
}
?>

</select>
<label for="blueprints">Blueprints?</label><input type=checkbox name=blueprints id=blueprints>
<select name="region">
<?php
$sql='select regionid,regionname from eve.mapRegions where regionid< 11000000 order by regionname';

$stmt = $dbh->prepare($sql);

$stmt->execute();

while ($row = $stmt->fetchObject()) {
    echo "<option value=".$row->regionid;
    if ($row->regionid==10000002) {
        echo " selected";
    }
    echo ">".$row->regionname.'</option>';
}
?>
</select>

<input type=submit value="Select Corporation (Sell prices)" onclick="this.form.action='listcorp.php'">
<input type=submit value="Select Corporation (Buy prices)" onclick="this.form.action='listcorpbuy.php'">
</form>
</div>
<div>
<p>To find what stores an item is in, type the name below, and hit the search button</p>
<form method="post" action="listitems.php">
<input type="text" name="item" id="item" length=50>
<input type="submit" value="Find Store">
</form>
</div>
<div>
<p>Database export: <a href="data/lpOffers.csv">lpOffers.csv</a> / <a href="data/lpOfferRequirements.csv">lpOfferRequirements.csv</a></p>
</div>
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>
</body>
</html>
