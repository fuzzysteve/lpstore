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
<p>LP store data is from <a href="https://forums.eveonline.com/default.aspx?g=posts&m=2523821">here</a>. It may be incorrect or incomplete. If it is, drop Sable Blitzmann the details. Or me, and I'll take it from there.</p>
<p>If your prefered corporation isn't yet marked as Confirmed, I'd appreciate it if you can have a look through and see if anything looks wrong. It doesn't mean it <em>is</em> wrong, just that it's not been doublechecked. If you have checked it, drop me a mail (Steve Ronuken in game. Sable Blitzmann would probably also like to know) and I can get it marked here. If you find an error, let me know what the id is (first column) and what it should be.<p>
</div>
</div>
<a class="btn btn-primary" id="checkbutton" onclick="document.getElementById('hidden').style.display='block';Cookies.set('checked','checked',{expires:3650})">I've read the above and understand it</a>
<div id="hidden" style='display:none'>
<form action="listcorp.php" method="post">
<select name="corpid">
<?

$sql='select itemName,itemID from  lpstore.lpcorps order by itemName Asc';

$stmt = $dbh->prepare($sql);

$stmt->execute();

while ($row = $stmt->fetchObject()){
$name=$row->itemName;
echo "<option value=";
echo  '"'.$row->itemID.'">'.$name.'</option>';
}
?>

</select>
<label for="blueprints">Blueprints?</label><input type=checkbox name=blueprints id=blueprints>
<select name="region">
<?
$sql='select regionid,regionname from eve.mapRegions order by regionname';

$stmt = $dbh->prepare($sql);

$stmt->execute();

while ($row = $stmt->fetchObject()){
echo "<option value=".$row->regionid;
if ($row->regionid==10000002)
{
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
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>
</body>
</html>
