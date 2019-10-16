/*
\i ~/git/world.sql
*/
begin;
--
drop schema if exists world cascade;
drop role if exists world;
create user world;
create schema world;
grant usage on schema world to world;
set local schema 'world';
--
create function _error(text) returns void language plpgsql as $$begin raise exception '%', $1 using errcode='H0403'; end;$$;
--
create view community as select community_name from db.community;
create view chat as select chat_at,chat_markdown from db.chat;
--
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,world,pg_temp as $$
declare
  rid integer;
  cid integer;
begin
  insert into room(community_id) values(0) returning room_id into rid;
  insert into community(community_name,community_room_id) values(cname,rid) returning community_id into cid;
  update room set community_id=cid where room_id=rid;
  return cid;
end$$;
--
create function new_chat(luuid uuid, roomid integer, msg text) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select * from room where room_id=roomid);
  select _error('not authorised to chat in this room') where (select room_type<>'public' from room where room_id=roomid) and not exists (select * from room_account_x natural join login where room_id=roomid and login_uuid=luuid);
  select _error('message too long') where length(msg)>500;
  insert into chat(community_id,room_id,chat_markdown) select community_id,roomid,msg from room where room_id=roomid returning chat_id;
$$;
--
revoke all on all functions in schema world from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to world;', E'\n') from pg_views where schemaname='world' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to world;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='world' and proname!~'^_' );
end$$;
--
commit;