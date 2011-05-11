<?php
/*!
 * Schedule builder
 *
 * Copyright (c) 2011, Edwin Choi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

include "./helpers.php";

allow_get_only();
require_once "./dbconnect.php";

if ( !$conn ) { set_status_and_exit(400, "Failed to connect to DB: " . $conn->connect_error); }

if (!isset($_GET['q']))
	set_status_and_exit(400);

$seq = -1;
if (isset($_GET['seq']))
	$seq = $_GET['seq'];

$q = $_GET['q']; // query
//$csv = isset($_GET['csv']) && $_GET['csv'] == 1; // comma-separated values?

$q = trim($q);

if (strncmp($q, '@', 1) === 0) {
	$q = trim(substr($q, 1));
	if (strlen($q) == 0) {
		echo "{}";
		return;
	}
	$cond = "title LIKE '%$q%'";
} else {
	$cond = "course LIKE '$q%'";
}

$query = <<<END
	SELECT	DISTINCT c.subject, CONCAT(c.number, c.suffix) AS name, c.title
	FROM	N_COURSE c, NX_COURSE x
	WHERE   c.crs_id = x.crs_id AND x.$cond
	LIMIT	0, 20
END;
$res = $conn->query($query);
if (!$res)
	set_status_and_exit(400, "MySQL Error: " . $conn->error);

$data = array();
$q = strtoupper($q);
while ($row = $res->fetch_row()) {
	$data[] = array(
		"title" => "$row[2]",
		"value" => "$row[0]$row[1]",
		"path" => "$row[0]/$row[1]"
	);
}

ob_start("ob_gzhandler");
header("Content-Type: application/json");
echo json_encode(array("seq" => $seq, "data" => &$data));

?>
