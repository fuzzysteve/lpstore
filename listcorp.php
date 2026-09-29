<?php
$expires = 3599;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

$corpid=0;
if (array_key_exists('corpid', $_POST) && is_numeric($_POST['corpid'])) {
    $corpid=(int)$_POST['corpid'];
} elseif (array_key_exists('corpid', $_GET) && is_numeric($_GET['corpid'])) {
    $corpid=(int)$_GET['corpid'];
}

if (array_key_exists('buy', $_GET) && is_numeric($_GET['buy'])) {
    $method="buy";
} else {
    $method="sell";
}
if (isset($method2)) {
    $method=$method2;
}

$blueprints=array_key_exists('blueprints', $_GET)||array_key_exists('blueprints', $_POST);

$regionid=0;
if (array_key_exists('region', $_POST) && is_numeric($_POST['region'])) {
    $regionid=(int)$_POST['region'];
} elseif (array_key_exists('region', $_GET) && is_numeric($_GET['region'])) {
    $regionid=(int)$_GET['region'];
}

if (!$regionid || !$corpid) {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

$config=array(
    'corpid'=>$corpid,
    'region'=>$regionid,
    'method'=>$method,
    'blueprints'=>$blueprints
);
?>
<html>
<head>
<title>LP Store - Return on ISK</title>
  <link href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css"
  rel="stylesheet" type="text/css"/>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script type="text/javascript" src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js">
  </script>

<script>
var config=<?php echo json_encode($config); ?>;
var method=config.method.charAt(0).toUpperCase()+config.method.slice(1);
var priceKey=method+' Price';
var volumeKey=method+' 5% Volume';
var ratioKey='isk/lp '+config.method;
var pageUrl='https://www.fuzzwork.co.uk/lpstore/'+config.method+'/'+config.region+'/'+config.corpid
    +(config.blueprints?'/withblueprints':'');
var apiUrl='/lpstore/api.php';

var regionNames={};
// Regions shown; the page's own region is always first
var loadedRegions=[config.region];
// Offer rows for every loaded region, each tagged with its regionID and Region
var rows=[];
var table=null;

// [data key, min input id, max input id]
var rangeFilters=[
    [ratioKey, 'ratiomin', 'ratiomax'],
    ['LPCost', 'lpmin', 'lpmax'],
    [volumeKey, 'volmin', 'volmax']
];

function escapeHtml(text) {
    return $('<div>').text(text).html();
}

function formatNumber(value, decimals) {
    return Number(value).toLocaleString('en-US',
        {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
}

// Show formatted numbers, but sort on the raw value
function numberColumn(decimals) {
    return function(data, type) {
        return (type==='display'||type==='filter')?formatNumber(data, decimals):data;
    };
}

function requirementsTable(title, items) {
    if (!items || !items.length) {
        return '';
    }
    var html="<table class='tablesorter'><tr><th colspan=2>"+title+"</th></tr>";
    $.each(items, function(i, item) {
        html+='<tr><td>'+formatNumber(item.Quantity, 0)+'</td><td>'+escapeHtml(item.Item)+'</td></tr>';
    });
    return html+'</table>';
}

function regionRows(regionid, offers) {
    $.each(offers, function(i, offer) {
        offer.regionID=regionid;
        offer.Region=regionNames[regionid];
    });
    return offers;
}

function offerParams(regionid) {
    var params={corpid: config.corpid, region: regionid};
    if (config.blueprints) {
        params.blueprints=1;
    }
    return params;
}

function boundValue(id) {
    var value=$('#'+id).val();
    return value===''?null:parseFloat(value);
}

$.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
    if (oSettings.nTable.id!=='lp') {
        return true;
    }
    var offer=oSettings.aoData[iDataIndex]._aData;
    for (var i=0; i<rangeFilters.length; i++) {
        var value=offer[rangeFilters[i][0]];
        var min=boundValue(rangeFilters[i][1]);
        var max=boundValue(rangeFilters[i][2]);
        if ((min!==null && value<min) || (max!==null && value>max)) {
            return false;
        }
    }
    return true;
});

// Keep the filters and extra regions in the query string so views can be linked
function updateUrl() {
    var params=[];
    $.each(rangeFilters, function(i, filter) {
        $.each([filter[1], filter[2]], function(j, id) {
            var value=$('#'+id).val();
            if (value!=='') {
                params.push(id+'='+encodeURIComponent(value));
            }
        });
    });
    if (loadedRegions.length>1) {
        params.push('regions='+loadedRegions.slice(1).join(','));
    }
    try {
        history.replaceState({}, '', pageUrl+(params.length?'?'+params.join('&'):''));
    } catch (err) {
        console.log("No replaceState");
    }
}

// Sets the filter inputs from the query string, and returns the extra regions asked for
function loadFromUrl() {
    var regions=[];
    $.each(window.location.search.substring(1).split('&'), function(i, pair) {
        var parts=pair.split('=');
        if (parts.length!=2) {
            return;
        }
        var value=decodeURIComponent(parts[1]);
        if (/^(ratio|lp|vol)(min|max)$/.test(parts[0])) {
            $('#'+parts[0]).val(value);
        } else if (parts[0]=='regions') {
            regions=$.map(value.split(','), function(id) {
                return /^\d+$/.test(id)?parseInt(id, 10):null;
            });
        }
    });
    return regions;
}

function showStatus(message) {
    $('#status').removeClass('alert-danger').addClass('alert-info').text(message).show();
}

function showError(jqXHR) {
    var message='Could not load LP store data.';
    try {
        message=JSON.parse(jqXHR.responseText).error;
    } catch (err) {}
    $('#status').removeClass('alert-info').addClass('alert-danger').text(message).show();
}

function renderRegionControls() {
    var select=$('#addregion').empty();
    $('<option>').val('').text('Add region…').appendTo(select);
    $.each(regionNames, function(regionid, name) {
        if ($.inArray(parseInt(regionid, 10), loadedRegions)==-1) {
            $('<option>').val(regionid).text(name).appendTo(select);
        }
    });
    var list=$('#loadedregions').empty();
    $.each(loadedRegions.slice(1), function(i, regionid) {
        $('<button type="button" class="btn btn-default btn-sm removeregion">')
            .attr('data-region', regionid).attr('title', 'Remove '+regionNames[regionid])
            .html(escapeHtml(regionNames[regionid])+' &times;').appendTo(list);
    });
}

function loadRegions(regionids) {
    regionids=$.grep(regionids, function(regionid, i) {
        return regionNames[regionid]!==undefined && $.inArray(regionid, loadedRegions)==-1
            && $.inArray(regionid, regionids)==i;
    });
    if (!regionids.length) {
        return;
    }
    showStatus('Loading '+$.map(regionids, function(regionid) {
        return regionNames[regionid];
    }).join(', ')+'…');
    $('#addregion').prop('disabled', true);

    var pending=regionids.length, failure=null;
    $.each(regionids, function(i, regionid) {
        $.getJSON(apiUrl, offerParams(regionid)).done(function(offers) {
            var added=regionRows(regionid, offers);
            rows=rows.concat(added);
            loadedRegions.push(regionid);
            table.fnAddData(added);
        }).fail(function(jqXHR) {
            failure=jqXHR;
        }).always(function() {
            if (--pending) {
                return;
            }
            renderRegionControls();
            updateUrl();
            $('#addregion').prop('disabled', false);
            if (failure) {
                showError(failure);
            } else {
                $('#status').hide();
            }
        });
    });
}

function removeRegion(regionid) {
    loadedRegions=$.grep(loadedRegions, function(id) {
        return id!=regionid;
    });
    rows=$.grep(rows, function(row) {
        return row.regionID!=regionid;
    });
    table.fnClearTable(false);
    table.fnAddData(rows);
    renderRegionControls();
    updateUrl();
}

$(document).ready(function() {
    $('#pricehead').text(method+' Price');
    var extraRegions=loadFromUrl();
    updateUrl();

    $.when(
        $.getJSON(apiUrl, offerParams(config.region)),
        $.getJSON(apiUrl, {list: 'corporations'}),
        $.getJSON(apiUrl, {list: 'regions'})
    ).done(function(offers, corporations, regions) {
        var corpname='';
        $.each(corporations[0], function(i, corp) {
            if (corp.corporationID==config.corpid) {
                corpname=corp.Corporation;
            }
        });
        $.each(regions[0], function(i, region) {
            regionNames[region.regionID]=region.Region;
        });
        document.title='LP Store - Return on ISK - '+corpname+' - '+regionNames[config.region]+' '+method;
        $('#corpname').text(corpname).attr('href', pageUrl);
        $('#regionname').text(regionNames[config.region]);
        $('#status').hide();

        rows=regionRows(config.region, offers[0]);
        table=$('#lp').dataTable({
            "aaData": rows,
            "bDeferRender": true,
            // by offer, then region, so each offer's regions sit together
            "aaSorting": [[0, "asc"], [1, "asc"]],
            "aoColumns": [
                {"mData": "id"},
                {"mData": "Region"},
                {"mData": "LPCost", "sType": "numeric", "mRender": numberColumn(0)},
                {"mData": "IskCost", "sType": "numeric", "mRender": numberColumn(0)},
                {"mData": "Item", "mRender": function(data, type, offer) {
                    if (type!=='display') {
                        return data;
                    }
                    return "<a href='https://market.fuzzwork.co.uk/region/"+offer.regionID+"/type/"
                        +offer.typeID+"/' target='_blank'>"+escapeHtml(data)+"</a>";
                }},
                {"mData": "Other Requirements", "mRender": function(data, type, offer) {
                    if (type!=='display') {
                        return $.map(data.concat(offer.Materials||[]), function(item) {
                            return item.Item;
                        }).join(' ');
                    }
                    return requirementsTable('LP Store', data)
                        +requirementsTable('Materials to build', offer.Materials);
                }},
                {"mData": "OtherCostIsk", "sType": "numeric", "mRender": numberColumn(0)},
                {"mData": "Quantity", "sType": "numeric"},
                {"mData": priceKey, "sType": "numeric", "mRender": numberColumn(2)},
                {"mData": volumeKey, "sType": "numeric", "mRender": numberColumn(0)},
                {"mData": ratioKey, "sType": "numeric", "mRender": numberColumn(2),
                    // the second argument is the formatted string, so use the raw row value
                    "fnCreatedCell": function(cell, display, offer) {
                        var ratio=offer[ratioKey];
                        $(cell).addClass(ratio>1000?'good':(ratio>500?'ok':'bad'));
                    }}
            ]
        });
        renderRegionControls();
        $('#regionpicker').show();
        loadRegions(extraRegions);

        $('#filters input').on('input change', function() {
            table.fnDraw();
            updateUrl();
        });
        $('#resetfilters').on('click', function() {
            $('#filters input').val('');
            table.fnDraw();
            updateUrl();
        });
        $('#addregion').on('change', function() {
            if (this.value) {
                loadRegions([parseInt(this.value, 10)]);
            }
        });
        $('#loadedregions').on('click', '.removeregion', function() {
            removeRegion(parseInt($(this).attr('data-region'), 10));
        });
    }).fail(showError);
});
</script>
<link href="/lpstore/style.css?v=3" rel="stylesheet" type="text/css"/>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/header.php'); ?>
</head>
<body>
<?php include('/home/web/fuzzwork/htdocs/menu/menubootstrap.php'); ?>
<div class="container">
<h1><a id="corpname"></a> <span id="regionname"></span> <?php echo ucfirst($method);?> Prices</h1>
<form id="filters" class="form-inline" onsubmit="return false;">
    <div class="form-group">
        <label>isk/lp</label>
        <input type="number" class="form-control input-sm" id="ratiomin" placeholder="min">
        &ndash;
        <input type="number" class="form-control input-sm" id="ratiomax" placeholder="max">
    </div>
    <div class="form-group">
        <label>LP</label>
        <input type="number" class="form-control input-sm" id="lpmin" placeholder="min" min="0">
        &ndash;
        <input type="number" class="form-control input-sm" id="lpmax" placeholder="max" min="0">
    </div>
    <div class="form-group">
        <label>5% Volume</label>
        <input type="number" class="form-control input-sm" id="volmin" placeholder="min" min="0">
        &ndash;
        <input type="number" class="form-control input-sm" id="volmax" placeholder="max" min="0">
    </div>
    <button type="button" class="btn btn-default btn-sm" id="resetfilters">Clear filters</button>
</form>
<form id="regionpicker" class="form-inline" onsubmit="return false;" style="display:none">
    <select class="form-control input-sm" id="addregion"></select>
    <span id="loadedregions"></span>
</form>
<div id="status" class="alert alert-info">Loading&hellip;</div>
<table border=1 id="lp" class="tablesorter">
<thead>
<tr>
    <th>id</th>
    <th>Region</th>
    <th>LP</th>
    <th>Isk</th>
    <th>Item</th>
    <th>Other Requirements</th>
    <th>Other Cost</th>
    <th>Quantity</th>
    <th id="pricehead">Price</th>
    <th>5% Volume</th>
    <th>isk/lp</th>
</tr>
</thead>
<tbody>
</tbody>
</table>
</div>
<?php include('/home/web/fuzzwork/htdocs/bootstrap/footer.php'); ?>

<!-- Generated <?php echo date(DATE_RFC822);?> -->
</body>
</html>
