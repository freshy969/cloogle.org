<?php
require_once('./conf.php');

$sql =
	"SELECT
		count(*),
		sum(case when `query` LIKE '%::%' then 1 else null end),
		sum(case when `query` LIKE 'type %' then 1 else null end),
		sum(case when `query` LIKE 'class %' then 1 else null end),
		sum(case when `query` LIKE 'using %' then 1 else null end)
	FROM `log`
	WHERE
		" . SQL_NOT_SILLYUSER . " AND
		`date` BETWEEN timestamp('$startTime') AND timestamp('$endTime')";

$stmt = $db->stmt_init();
if (!$stmt->prepare($sql))
	var_dump($stmt->error);
$stmt->execute();
$stmt->bind_result($total, $unify, $type, $class, $using);
$stmt->fetch();
$stmt->close();

$results = [
	[ 'name' => 'Unification', 'y' => (int) $unify ],
	[ 'name' => 'Type definition', 'y' => (int) $type ],
	[ 'name' => 'Class', 'y' => (int) $class ],
	[ 'name' => 'Using', 'y' => (int) $using ],
	[ 'name' => 'Name only', 'y' => $total - $unify - $type - $class - $using ]
];

header('Content-Type: text/javascript');
echo "$callback(" . json_encode($results) . ");";
