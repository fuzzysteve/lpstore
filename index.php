<html>
<head>
<title>LP Store - Return on ISK</title>
<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script src="/lpstore/js.cookie.js"></script>

<script>
var apiUrl='/lpstore/api.php';

function fillSelect(select, rows, idKey, nameKey, selected) {
    $.each(rows, function(i, row) {
        $('<option>').val(row[idKey]).text(row[nameKey])
            .prop('selected', row[idKey]==selected).appendTo(select);
    });
}

$(document).ready(function() {
    if (Cookies.get('checked') == "checked" ){
        document.getElementById('hidden').style.display='block'
        document.getElementById('checkbutton').style.display='none'
    }
    $.when(
        $.getJSON(apiUrl, {list: 'corporations'}),
        $.getJSON(apiUrl, {list: 'regions'}),
        $.getJSON(apiUrl, {list: 'items'})
    ).done(function(corporations, regions, items) {
        fillSelect($('select[name=corpid]'), corporations[0], 'corporationID', 'Corporation');
        fillSelect($('select[name=region]'), regions[0], 'regionID', 'Region', 10000002);
        $("input#item").autocomplete({ source: $.map(items[0], function(item) { return item.Item; }) });
    }).fail(function() {
        $('#loaderror').show();
    });
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
<div id="loaderror" class="alert alert-danger" style="display:none">Could not load the corporation and region lists.</div>
<form action="listcorp.php" method="post">
<select name="corpid">
</select>
<label for="blueprints">Blueprints?</label><input type=checkbox name=blueprints id=blueprints>
<select name="region">
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
<div>
<h3>API</h3>
<p>The same data is available as JSON, for use in your own tools. Same caveats as above: prices are a guide, not gospel.
Responses are cached for an hour, and cross-origin requests are allowed.</p>
<p><code>https://www.fuzzwork.co.uk/lpstore/api.php?corpid=<em>corporationID</em>&amp;region=<em>regionID</em></code></p>
<ul>
<li><code>corpid</code> - required. The corporation whose LP store you want.</li>
<li><code>region</code> - optional, defaults to 10000002 (The Forge). The region prices are taken from.</li>
<li><code>blueprints</code> - optional. If present, blueprint offers are included, priced on the item they build, with their build materials listed.</li>
</ul>
<p>Example: <a href="api.php?corpid=1000182&amp;region=10000002">api.php?corpid=1000182&amp;region=10000002</a> (Tribal Liberation Force, The Forge).</p>
<p>It returns an array with one entry per offer, containing the offer id, <code>typeID</code> and <code>Item</code> name,
<code>LPCost</code>, <code>IskCost</code>, <code>Quantity</code>, <code>OtherCostIsk</code> (the other required items, at sell price),
sell and buy prices, sell and buy 5% volumes, isk/lp for both sell and buy, <code>Price Date</code> (UTC), and the list of
<code>Other Requirements</code>. Blueprint offers also have <code>productTypeID</code> and <code>Materials</code>.</p>
<p>To find IDs: <a href="api.php?list=corporations">api.php?list=corporations</a>,
<a href="api.php?list=regions">api.php?list=regions</a> and <a href="api.php?list=items">api.php?list=items</a>.</p>
<p>Errors come back as <code>{"error": "..."}</code> with a 400 or 404 status.</p>
</div>
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>
</body>
</html>
