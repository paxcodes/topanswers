<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to activerooms,pg_temp");
ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']) || fail(403,'access denied');
extract(cdb("select community_name,community_language
                  , (select coalesce(jsonb_agg(z),'[]'::jsonb)
                     from (select room_group, jsonb_agg(z order by rn) rooms
                           from (select room_id,room_derived_name,room_question_id,room_image_url,community_display_name,community_name,community_rgb_light,listener_latest_read_chat_id,listener_unread
                                      , case when room_question_id is null then 1 else 2 end room_group
                                      , row_number() over (order by participant_chat_count desc, participant_latest_chat_at desc) rn
                                 from room(('{'||$1||'}')::integer[])) z
                           group by room_group) z) grps
             from one",isset($_GET['read'])?implode(',',$_GET['read']):''));
$_GET['community']===$community_name || fail(400,'invalid community');
include '../lang/activerooms.'.$community_language.'.php';
?>
<?foreach($grps as $g){?>
  <div>
    <?foreach($g['rooms'] as $r){ extract($r);?>
      <a class="frame"
         style="--rgb-light: <?=$community_rgb_light?>"
         href="/<?=$community_name?>?<?=$room_question_id?'q='.$room_question_id:'room='.$room_id?>"
         data-room="<?=$room_id?>"
         data-latest="<?=$listener_latest_read_chat_id?>"
         <?if($listener_unread>0){?>data-unread="<?=$listener_unread?>" data-unread-lang="<?=$l_num($listener_unread)?>"<?}?>>
        <img title="<?=($community_name===$_GET['community'])?'':$community_display_name.': '?><?=$room_derived_name?><?=$listener_unread?' ('.$listener_unread.' unread)':''?>" class="icon roomicon" data-id="<?=$room_id?>" data-name="<?=$room_derived_name?>" src="<?=$room_image_url?>">
      </a>
    <?}?>
  </div>
<?}?>
