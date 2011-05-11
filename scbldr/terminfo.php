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

require_once "./dbconnect.php";

$current_term_label = "";
$current_term_value = "";
$last_update_timestamp = 0;
$last_run_timestamp = 0;
$incomplete_data = 0;

if ( $conn ) {
	$res = $conn->query(<<<_
		SELECT semester, disp_name, UNIX_TIMESTAMP(last_updated), UNIX_TIMESTAMP(last_run), updating, incomplete
		  FROM N_TERMINFO
		 WHERE active = 1
_
	);
	if ( $row = $res->fetch_row() ) {
		$current_term_value = $row[0];
		$current_term_label = $row[1];
		$last_update_timestamp = $row[2];
		$last_run_timestamp = $row[3];
		$updating_data = $row[4];
		$incomplete_data = $row[5];
	}
}

?>
