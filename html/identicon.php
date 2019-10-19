<?php
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

isset($_GET['id']) or die('id not set');
$id = intval($_GET['id']);
$id>0 or die('id not positive integer');

if(ccdb("select account_image is null from account where account_id=$1",$id)==='f'){
  header("Content-Type: image/jpeg");
  echo pg_unescape_bytea(ccdb("select account_image from account where account_id=$1",$id));
  exit;
}

// Settings
define('MARGIN_X', 0);        // Margin on the left and right edge in px
define('MARGIN_Y', 0);        // Margin on the upper and lower edge in px
define('BOX_SIZE_W', 4);      // Width of the individual "pixels" in px
define('BOX_SIZE_H', 4);      // Height of the individual "pixels" in px
define('GRID_COUNT_W', 7);     // Horizontal "pixel"-count
define('GRID_COUNT_H', 7);     // Vertical "pixel"-count
define('BG_COLOR', '#FFFFFF'); // Background color as 6-digit hexadecimal rgb code

// calculate image dimensions based on settings
$sizeX = 2*MARGIN_X + GRID_COUNT_W*BOX_SIZE_W;
$sizeY = 2*MARGIN_Y + GRID_COUNT_H*BOX_SIZE_H;

// calculate significant pixel count and the highest presentable number
$pixelCount = GRID_COUNT_H * ceil(GRID_COUNT_W/2);
$max = pow(2, $pixelCount) - 1;

// normalize (modulo) the passed id
$id = (crc32($id) % $max) + 1;

// create image canvas and fill with default background color
$im = imagecreatetruecolor($sizeX, $sizeY);
$colBG_r = hexdec(substr(BG_COLOR, -6, 2));
$colBG_g = hexdec(substr(BG_COLOR, -4, 2));
$colBG_b = hexdec(substr(BG_COLOR, -2, 2));
$colBG = imagecolorallocate($im, $colBG_r, $colBG_g, $colBG_b);
$colTransparentBG = imagecolortransparent($im, $colBG);
//imagefill($im, 0, 0, $colBG);
imagefill($im, 0, 0, $colTransparentBG);
$col = imagecolorallocate($im, $id%128, intdiv($id,128)%128, intdiv($id,16384)%128);

// build pixels as long there are bits in
for($i = 0; $i < $pixelCount; $i++) {

	// get least significant bit
	$onoff = $id & 1;

	// shift that bit out
	$id = $id >> 1;
	
	// if the least significant bit is 1, draw that pixel
	if($onoff > 0) {

		// calculate upper left corner of pixel
		$x1 = MARGIN_X + floor($i / GRID_COUNT_W) * BOX_SIZE_W;
		$y1 = MARGIN_Y + ($i % GRID_COUNT_H) * BOX_SIZE_H;
		// calculate lower right corner of pixel
		$x2 = $x1 + BOX_SIZE_W;
		$y2 = $y1 + BOX_SIZE_H;
		// draw pixel
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);

		// draw mirrored pixel with same y-coordinates, different
		// x-coordinates for left and right edge
		$x1 = MARGIN_X + (GRID_COUNT_W-1)*BOX_SIZE_W - floor($i / GRID_COUNT_W) * BOX_SIZE_W;
		$x2 = $x1 + BOX_SIZE_W;
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);
	}
}

// output that image
header("Content-Type: image/png");
imagepng($im);