<?php
/*
 * JSON API for LP store offers.
 *
 * Parameters (GET or POST):
 *   corpid     - required, corporation ID owning the LP store
 *   region     - optional, region ID for prices (default 10000002, The Forge)
 *   blueprints - optional, if present include blueprint offers (priced on their product)
 *
 * Lookup lists (no other parameters needed):
 *   list=corporations - corporations with an LP store: [{corporationID, Corporation}]
 *   list=regions      - regions prices can be requested for: [{regionID, Region}]
 *   list=items        - items offered in any LP store: [{typeID, Item}]
 */
$expires = 3599;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once('db.inc.php');
require_once('redisprice.php');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function apierror($code, $message)
{
    http_response_code($code);
    echo json_encode(array('error' => $message));
    exit;
}

$list=$_GET['list'] ?? $_POST['list'] ?? null;
if ($list!==null) {
    $output=array();
    if ($list=='corporations') {
        $sql=<<<EOS
        select distinct itemID, itemName
        from lpstore2.lpOffers
        join eve.invNames on corporationID=itemID
        order by itemName
EOS;
        foreach ($dbh->query($sql) as $row) {
            $output[]=array('corporationID'=>(int)$row['itemID'], 'Corporation'=>$row['itemName']);
        }
    } elseif ($list=='regions') {
        $sql='select regionid, regionname from eve.mapRegions where regionid<11000000 order by regionname';
        foreach ($dbh->query($sql) as $row) {
            $output[]=array('regionID'=>(int)$row['regionid'], 'Region'=>$row['regionname']);
        }
    } elseif ($list=='items') {
        $sql=<<<EOS
        select distinct invTypes.typeid, typename
        from lpstore2.lpOffers
        join eve.invTypes on lpOffers.typeid=invTypes.typeid
        order by typename
EOS;
        foreach ($dbh->query($sql) as $row) {
            $output[]=array('typeID'=>(int)$row['typeid'], 'Item'=>$row['typename']);
        }
    } else {
        apierror(400, 'list must be corporations, regions or items');
    }
    echo json_encode($output, JSON_UNESCAPED_SLASHES);
    exit;
}

$corpid=0;
if (array_key_exists('corpid', $_POST) && is_numeric($_POST['corpid'])) {
    $corpid=(int)$_POST['corpid'];
} elseif (array_key_exists('corpid', $_GET) && is_numeric($_GET['corpid'])) {
    $corpid=(int)$_GET['corpid'];
}

$regionid=10000002;
if (array_key_exists('region', $_POST) && is_numeric($_POST['region'])) {
    $regionid=(int)$_POST['region'];
} elseif (array_key_exists('region', $_GET) && is_numeric($_GET['region'])) {
    $regionid=(int)$_GET['region'];
}

$blueprints=array_key_exists('blueprints', $_GET)||array_key_exists('blueprints', $_POST);

if (!$corpid) {
    apierror(400, 'corpid is required');
}

$stmt = $dbh->prepare("select regionname from eve.mapRegions where regionid=?");
$stmt->execute(array($regionid));
if (!$stmt->fetchObject()) {
    apierror(404, 'Unknown region');
}

$stmt = $dbh->prepare("select 1 from lpstore2.lpOffers where corporationID=? limit 1");
$stmt->execute(array($corpid));
if (!$stmt->fetchObject()) {
    apierror(404, 'Unknown corporation, or it has no LP store');
}

if ($blueprints) {
    $sql=<<<EOS
    select lpOffers.offerID id,
        it1.typename,
        it1.typeid,
        lpOffers.quantity,
        lpcost,
        iskCost,
        coalesce(productTypeID,0) productTypeID
    from lpstore2.lpOffers
    join eve.invTypes it1 on (lpOffers.typeid=it1.typeid)
    left join eve.industryActivityProducts iap on
        (lpOffers.typeid=iap.typeID and activityid=1) where corporationID=? and akcost=0
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
    from lpstore2.lpOffers
    join eve.invTypes it1 on (lpOffers.typeid=it1.typeid)
    where corporationID=? and marketgroupid is not null and akcost=0
EOS;
}
$stmt = $dbh->prepare($sql);

$requiredsql=<<<EOS
    select typename,
        invTypes.typeid,
        quantity
    from eve.invTypes ,lpstore2.lpOfferRequirements
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

function iskperlp($quantity, $price, $cost, $lpcost)
{
    if (!$lpcost) {
        return 0;
    }
    return round((($quantity*$price)-$cost)/$lpcost, 2);
}

$output=array();
$stmt->execute(array($corpid));
while ($row = $stmt->fetchObject()) {
    # blueprints are priced on the item they build
    $typeid=($row->productTypeID>0)?(int)$row->productTypeID:(int)$row->typeid;
    list($sell, $buy)=returnprice($typeid, $regionid);
    list($sellvolume, $buyvolume)=returnvolume($typeid, $regionid);

    # Other costs are always priced at sell, matching the web page
    $otherprice=0;
    $requirements=array();
    $stmt2->execute(array($row->id));
    while ($row2 = $stmt2->fetchObject()) {
        list($innerprice, $innerbuy)=returnprice($row2->typeid, $regionid);
        $otherprice+=$innerprice*$row2->quantity;
        $requirements[]=array(
            'typeID'=>(int)$row2->typeid,
            'Item'=>$row2->typename,
            'Quantity'=>(int)$row2->quantity,
            'Sell Price'=>round($innerprice, 2)
        );
    }

    $materials=array();
    if ($row->productTypeID>0) {
        $basicstmt->execute(array(':typeid'=>$row->typeid,':quantity'=>$row->quantity));
        while ($basic = $basicstmt->fetchObject()) {
            if (!array_key_exists($basic->typeid, $materials)) {
                $materials[$basic->typeid]=array(
                    'typeID'=>(int)$basic->typeid,
                    'Item'=>$basic->typename,
                    'Quantity'=>0,
                    'Sell Price'=>0.0
                );
            }
            $materials[$basic->typeid]['Quantity']+=(int)$basic->quantity;
        }
        foreach ($materials as $mattypeid => $material) {
            list($innerprice, $innerbuy)=returnprice($mattypeid, $regionid);
            $materials[$mattypeid]['Sell Price']=round($innerprice, 2);
            $otherprice+=$innerprice*$material['Quantity'];
        }
    }

    $cost=$row->iskCost+$otherprice;
    $offer=array(
        'id'=>(int)$row->id,
        'typeID'=>(int)$row->typeid,
        'Item'=>$row->typename,
        'LPCost'=>(int)$row->lpcost,
        'IskCost'=>(float)$row->iskCost,
        'OtherCostIsk'=>round($otherprice, 2),
        'Quantity'=>(int)$row->quantity,
        'Sell Price'=>round($sell, 2),
        'Buy Price'=>round($buy, 2),
        'Sell 5% Volume'=>(float)$sellvolume,
        'Buy 5% Volume'=>(float)$buyvolume,
        'isk/lp sell'=>iskperlp($row->quantity, $sell, $cost, $row->lpcost),
        'isk/lp buy'=>iskperlp($row->quantity, $buy, $cost, $row->lpcost),
        'Other Requirements'=>$requirements
    );
    if ($row->productTypeID>0) {
        $offer['productTypeID']=(int)$row->productTypeID;
        $offer['Materials']=array_values($materials);
    }
    $output[]=$offer;
}

echo json_encode($output, JSON_UNESCAPED_SLASHES);
