<?php
$expires = 14400;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
header('Content-Type: application/javascript');

require_once('db.inc.php');

$sql='select distinct typename from lpstore.lpOffers join eve.invTypes on lpOffers.typeid=invTypes.typeid order by typename asc';

$stmt = $dbh->prepare($sql);

$stmt->execute();

echo "source=[";
$row = $stmt->fetchObject();
echo  '"'.$row->typename.'"';
while ($row = $stmt->fetchObject()){
echo ',"'.$row->typename.'"';
}
echo "];\n";
?>

