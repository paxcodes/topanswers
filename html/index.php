<?    
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
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = $_COOKIE['uuid'] ?? false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  db("select new_chat($1,$2,$3)",$uuid,1,$_POST['msg']);
  exit;
}
?>
<!doctype html>
<html style="box-sizing: border-box;">
<head>
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid black; }
  </style>
  <script src="jquery.js"></script>
  <script src="markdown-it.js"></script>
  <script src="markdown-it-sup.js"></script>
  <script src="js.cookie.js"></script>
  <script>
    $(function(){
      var md = window.markdownit().use(window.markdownitSup);
      $('#register').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }); location.reload(true); } });
      $('#send').click(function(){ $.ajax({ type: "POST", url: '/', data: { msg: $('textarea').val() }, async: false }); location.reload(true); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).data('markdown'))); });
    });
  </script>
</head>
<body style="display: flex; background-color: red;">
  <main style="background-color: lightgreen; display: flex; flex-direction: column; flex: 0 0 70%;">
    <header style="background-color: brown; padding: 0.5em; border-bottom: 2px solid black;">
      <span>TopAnswers: Meta</span>
    </header>
    <div id="qa" style="background-color: goldenrod; overflow-y: scroll; padding: 0.5em;">
      <?for($x = 0; $x<100; $x++){?>
        <div class="question"><?=$x?>) Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tristique placerat felis vitae cursus.</div>
      <?}?>
    </div>
  </main>
  <div id="chat" style="background-color: darkslateblue; flex: 0 0 30%; display: flex; flex-direction: column; justify-content: flex-end; border-left: 2px solid black;">
    <header style="background-color: seagreen; padding: 0.5em; border-bottom: 2px solid black;">
      <span>The Tavern</span>
    </header>
    <div style="display: flex; flex-direction: column-reverse; overflow-y: scroll; padding: 0.5em;">
      <?foreach(db("select chat_markdown from chat order by chat_at desc") as $r){ extract($r);?>
        <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
      <?}?>
    </div>
    <div style="padding: 0.5em;">
      <?if(!$uuid){?><input id="register" type="button" value="register"><?}?>
      <textarea></textarea>
      <input id="send" type="button" value="send">
    </div>
  </div>
</body>   
</html>   