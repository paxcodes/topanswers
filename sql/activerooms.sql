create schema activerooms;
grant usage on schema activerooms to get;
set local search_path to activerooms,api,pg_temp;
--
--
create view room with (security_barrier) as
with w as (select room_id,participant_latest_chat_at
                , coalesce(participant_chat_count,0) participant_chat_count
                , coalesce(listener_latest_read_chat_id,0) listener_latest_read_chat_id
                , case when room_can_listen then (select count(1) from (select 1 from db.chat c where c.room_id=l.room_id and c.chat_id>coalesce(l.listener_latest_read_chat_id,0) limit 99) z) else 0 end listener_unread
           from db.listener l natural join db.room r natural left join db.participant p
           where account_id=get_account_id())
select room_id,room_derived_name,room_question_id,community_name,listener_unread,listener_latest_read_chat_id,participant_chat_count,participant_latest_chat_at
from w natural join api._room natural join db.community
where listener_unread>0 or participant_latest_chat_at+make_interval(hours=>60+least(participant_chat_count,182)*12)>current_timestamp;
--
create view one with (security_barrier) as select community_name,community_language from api._community natural join db.community where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;

--
--
revoke all on all functions in schema activerooms from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='activerooms' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='activerooms' and proname!~'^_' );
end$$;