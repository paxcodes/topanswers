<?    
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid']??'';
ccdb("select login($1)",$uuid);
$id = $_GET['id']??$_POST['id']??'0';
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'new':
      $id=ccdb("select new_answer($1,$2,$3,$4)",$_POST['question'],$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
      if($id){?>
        <!doctype html>
        <html>
        <head>
          <script>
            localStorage.removeItem('<?=$_POST['community']?>.answer.<?=$_POST['question']?>');
            window.location.href = '/<?=$_POST['community']?>?q=<?=$_POST['question']?>';
          </script>
        </head>
        </html><?}
      exit;
    case 'change':
      db("select change_answer($1,$2)",$id,$_POST['markdown']);
      header('Location: /'.ccdb("select community_name from answer natural join (select question_id,community_id from question) z natural join community where answer_id=$1",$id).'?q='.ccdb("select question_id from answer where answer_id=$1",$id));
      exit;
    case 'vote': exit(ccdb("select vote_answer($1,$2)",$_POST['id'],$_POST['votes']));
    default: fail(400,'unrecognized action');
  }
}
if($id) {
  ccdb("select count(*) from answer where answer_id=$1",$id)==='1' || die('invalid answer id');
  extract(cdb("select community_name community, question_id question, question_title, question_markdown, answer_markdown
                    , license_name||(case when codelicense_id<>1 then ' + '||codelicense_name else '' end) license
               from answer natural join (select question_id,community_id,question_title,question_markdown from question natural join license natural join codelicense) z natural join community natural join license natural join codelicense
               where answer_id=$1",$id));
}else{
  if(!isset($_GET['question'])) die('question not set');
  $question = $_GET['question'];
  ccdb("select count(*) from question where question_id=$1",$question)==='1' or die('invalid question');
  extract(cdb("select community_name community, question_title, question_markdown from question natural join community where question_id=$1",$question));
}
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
extract(cdb("select account_license_id,account_codelicense_id from my_account"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .answer { margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1rem; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    .markdown { overflow-wrap: break-word; }
    .markdown :first-child { margin-top: 0; }
    .markdown :last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 2em; }
    .markdown li { margin: 0.5em 0; }
    .markdown img { max-height: 20em; max-width: 100%; }
    .markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
    .markdown table { border-collapse: collapse; table-layout: fixed; }
    .markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
    .markdown td, .markdown th { white-space: nowrap; border: 1px solid black; padding: 0.2em; }
    .markdown blockquote { padding: 0.5rem; margin-left: 0.7rem; margin-right: 0; border-left: 0.3rem solid #<?=$colour_mid?>; background-color: #<?=$colour_light?>40; }
    .markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }
    #markdown-editor-buttons i { padding: 0.2em; margin-bottom: 0.3em; text-align: center; }
    #markdown-editor-buttons i:hover { color: #<?=$colour_highlight?>; cursor: pointer; background-color: #<?=$colour_light?>; border-radius: 0.2rem; }
    #markdown-editor-buttons i:last-child { margin-bottom: 0; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0 0.2rem 0.2rem 0.2rem; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lodash.js"></script>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-inject-linenumbers.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/markdown-it-emoji.js"></script>
  <script src="/markdown-it-footnote.js"></script>
  <script src="/markdown-it-deflist.js"></script>
  <script src="/markdown-it-abbr.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script src="/favico.js"></script>
  <script src="codemirror/codemirror.js"></script>
  <script src="codemirror/markdown.js"></script>
  <script src="codemirror/placeholder.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitEmoji).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr).use(window.markdownitInjectLinenumbers);
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, extraKeys: {
        Home: "goLineLeft",
        End: "goLineRight",
        'Ctrl-B': function(cm){ $('.button.fa-bold').click(); },
        'Ctrl-I': function(cm){ $('.button.fa-italic').click(); },
        'Ctrl-G': function(cm){ $('.button.fa-picture-o').click(); }
      } });
      var map;

      function render(){
        $('#answer').html(md.render(cm.getValue()));
        $('#answer table').wrap('<div class="tablewrapper" tabindex="-1">');
        map = [];
        $('#answer [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$id){?>localStorage.setItem('<?=$community?>.answer.<?=$question?>',cm.getValue());<?}?>
      }

      $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, top: '4px', left: '10px' }).attr('tabindex','-1');
      $('#community').change(function(){ window.location = '?community='+$(this).val().toLowerCase(); });
      cm.on('change',function(){
        render();
        $('textarea[name="markdown"]').val(cm.getValue()).show();
      });
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) $('#answer').animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#answer').animate({ scrollTop: $('#answer').prop("scrollHeight")-$('#answer').height() });
        else $('#answer [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
      <?if(!$id){?>
        if(localStorage.getItem('<?=$community?>.answer.<?=$question?>')) cm.setValue(localStorage.getItem('<?=$community?>.answer.<?=$question?>'));
      <?}?>
      $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $(this).closest('form').submit(); }; });
      $('#uploadfile').closest('form').submit(function(){
        var d = new FormData($(this)[0]);
        $.ajax({ url: "/upload", type: "POST", data: d, processData: false, cache: false, contentType: false }).done(function(r){
          var selectionStart = cm.getCursor(), selectionEnd = cm.getCursor();
          cm.replaceSelection('!['+d.get('image').name+'](/image?hash='+r+')');
          cm.focus();
          cm.setSelection({ ch: selectionStart.ch, line: selectionStart.line },{ ch: selectionEnd.ch, line: selectionEnd.line });
        }).fail(function(r){
          alert(r.status+' '+r.statusText+'\n'+r.responseText);
        });
        return false;
      });
      $('.button.fa-bold').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('**'+cm.getSelection()+'**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionEnd.ch+2), line: selectionEnd.line });
        }else{ cm.replaceSelection('**bold**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionStart.ch+6), line: selectionStart.line }); }
      });
      $('.button.fa-italic').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('*'+cm.getSelection()+'*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionEnd.ch+1), line: selectionEnd.line });
        }else{ cm.replaceSelection('*italic*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionStart.ch+7), line: selectionStart.line }); }
      });
      $('.button.fa-picture-o').click(function(){ $('#uploadfile').click(); cm.focus(); });
      render();
      $('#license').change(function(){ $('input[name="license"').val($(this).val()); });
      $('#codelicense').change(function(){ $('input[name="codelicense"').val($(this).val()); });
      $('#question .markdown').html(md.render($('#question .markdown').data('markdown')));
      $('#question .markdown table').wrap('<div class="tablewrapper" tabindex="-1">');
    });
  </script>
  <title><?=$id?'Edit Answer':'Answer Question'?> | <?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <?if(!$id){?>
        <select name="license" form="form">
          <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
            <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
          <?}?>
        </select>
        <select name="codelicense" form="form">
          <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
            <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
          <?}?>
        </select>
      <?}?>
      <input id="submit" type="submit" form="form" value="<?=$id?'update answer under '.$license:'post answer'?>" style="margin: 0.5rem;">
      <a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a>
    </div>
  </header>
  <form id="form" method="POST" action="/answer" style="display: flex; flex-direction: column; flex: 1 0 0; padding: 2vmin; overflow-y: hidden;">
    <?if($id){?>
      <input type="hidden" name="action" value="change">
      <input type="hidden" name="id" value="<?=$id?>">
    <?}else{?>
      <input type="hidden" name="action" value="new">
      <input type="hidden" name="community" value="<?=$community?>">
      <input type="hidden" name="question" value="<?=$question?>">
    <?}?>
    <main style="display: flex; position: relative; justify-content: center; flex: 1 0 0; overflow-y: auto;">
      <div style="flex: 0 1.5 50em; max-width: 20vw; overflow: hidden;">
        <div id="question" style="display: flex; flex-direction: column; background-color: white; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow: hidden;">
          <div style="flex: 0 0 auto; padding: 0.6rem; font-size: larger; text-shadow: 0.1em 0.1em 0.1em lightgrey; border-bottom: 1px solid #<?=$colour_dark?>;"><?=htmlspecialchars($question_title)?></div>
          <div class="markdown" data-markdown="<?=htmlspecialchars($question_markdown)?>" style="flex: 1 0 auto; overflow-y: auto; padding: 0.6em;"></div>
        </div>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div style="flex: 0 0 1.6em;">
        <div id="markdown-editor-buttons" style="display: flex; flex-direction: column; background: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem 0 0 0.2rem; border-right: none; padding: 0.3em;">
          <form action="/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
          <i title="Bold (Ctrl + B)" class="button fa fw fa-bold"></i>
          <i title="Italic (Ctrl + I)" class="button fa fw fa-italic"></i>
          <i title="Upload Image (Ctrl + G)" class="button fa fw fa-picture-o"></i>
        </div>
      </div>
      <div style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); position: relative;">
        <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" autofocus required placeholder="your answer"><?=$id?htmlspecialchars($answer_markdown):''?></textarea>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div id="answer" class="markdown" style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); background-color: white; padding: 0.6rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow-y: auto;"></div>
    </main>
  </form>
</body>   
</html>   
