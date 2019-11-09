create table community(
  community_id integer generated always as identity primary key
, community_name text not null
, community_room_id integer not null references room deferrable initially deferred
, community_dark_shade bytea not null default decode('4d7ebb','hex') check(length(community_dark_shade)=3)
, community_mid_shade bytea not null default decode('d4dfec','hex') check(length(community_mid_shade)=3)
, community_light_shade bytea not null default decode('e7edf4','hex') check(length(community_light_shade)=3)
, community_highlight_color bytea not null default decode('f79804','hex') check(length(community_highlight_color)=3)
);

create type room_type_enum as enum ('public','gallery','private');

create table room(
  room_id integer generated always as identity primary key
, community_id integer not null references community deferrable initially deferred
, room_type room_type_enum not null default 'public'
, room_name text
, unique (community_id,room_id)
);

create table account(
  account_id integer generated always as identity primary key
, account_name text check (account_name~'^[A-Za-zÀ-ÖØ-öø-ÿ][ 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$')
, account_create_at timestamptz not null default current_timestamp
, account_change_at timestamptz not null default current_timestamp
, account_image bytea check(length(account_image)>0)
, account_change_id bigint generated always as identity unique
, account_uuid uuid not null default x_uuid_ossp.uuid_generate_v4()
, account_is_dev boolean default false not null
);
create unique index account_rate_limit_ind on account(account_create_at);

create table account_community(
  account_id integer references account
, community_id integer references community
, account_community_repute integer default 0 not null
, primary key (account_id,community_id)
);

create table account_history(
  account_history_id integer generated always as identity primary key
, account_id integer not null references account
, account_history_at timestamptz not null
, account_history_name text not null default ''
);

create table login(
  login_uuid uuid primary key
, account_id integer not null references account
, login_resizer_percent integer default 50 not null check (login_resizer_percent between 0 and 100)
);

create table pin(
  pin_number bigint
, account_id integer references account
, pin_at timestamptz default current_timestamp not null
, primary key (pin_number,account_id)
);

create table chat(
  chat_id bigint generated always as identity unique primary key
, community_id integer not null references community
, room_id integer not null references room
, account_id integer not null references account
, chat_reply_id integer references chat
, chat_change_id bigint generated always as identity unique
, chat_at timestamptz not null default current_timestamp
, chat_change_at timestamptz not null default current_timestamp
, chat_markdown text not null check (length(chat_markdown) between 1 and 5000)
, unique (room_id,chat_id)
, foreign key (community_id,room_id) references room(community_id,room_id)
, foreign key (room_id,chat_reply_id) references chat(room_id,chat_id)
);
create index chat_latest_ind on chat(room_id,chat_at);

create table account_room_x(
  account_id integer references account
, room_id integer references room
, primary key (account_id,room_id)
);

create table room_account_x(
, room_id integer references room
, account_id integer references account
, room_account_x_latest_chat_at timestamptz not null default current_timestamp
, primary key (room_id,account_id)
);
create index room_account_x_latest on room_account_x(room_id,room_account_x_latest_chat_at);

create table chat_notification(
  chat_id bigint references chat
, account_id integer references account
, chat_notification_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
);
create index chat_notification_latest_ind on chat_notification(account_id,chat_notification_at);

/*
create table chat_history(
  chat_history_id bigint generated always as identity unique primary key
, chat_id bigint not null references chat
, account_id integer not null references account
, chat_history_at timestamptz not null
, chat_history_markdown text not null
);
*/

create table chat_flag(
  chat_id bigint references chat
, account_id integer references account
, chat_flag_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
);

create table chat_star(
  chat_id bigint references chat
, account_id integer references account
, chat_star_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
);

create table chat_year(
  room_id integer references room
, chat_year integer
, chat_year_count integer not null
, primary key (room_id,chat_year)
);

create table chat_month(
  room_id integer
, chat_year integer
, chat_month integer
, chat_month_count integer not null
, primary key (room_id,chat_year,chat_month)
, foreign key (room_id,chat_year) references chat_year
);

create table chat_day(
  room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_day_count integer not null
, primary key (room_id,chat_year,chat_month,chat_day)
, foreign key (room_id,chat_year,chat_month) references chat_month
);

create table chat_hour(
  room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_hour integer
, chat_hour_count integer not null
, primary key (room_id,chat_year,chat_month,chat_day,chat_hour)
, foreign key (room_id,chat_year,chat_month,chat_day) references chat_day
);

create type question_type_enum as enum ('question','meta','blog');

create table question(
  question_id integer generated always as identity unique primary key
, community_id integer not null references community
, account_id integer not null references account
, question_type question_type_enum not null default 'question'
, question_at timestamptz not null default current_timestamp
, question_title text not null check (length(question_title) between 5 and 200)
, question_markdown text not null check (length(question_markdown) between 1 and 50000)
, question_room_id integer not null references room deferrable initially deferred
, question_change_at timestamptz not null default current_timestamp
, question_votes integer default 0 not null
, question_repute integer default 0 not null
, unique (community_id,question_id)
, foreign key (community_id,question_room_id) references room(community_id,room_id)
);
create unique index question_rate_limit_ind on question(account_id,question_at);

create table question_history(
  question_history_id bigint generated always as identity unique primary key
, question_id integer not null references question
, account_id integer not null references account
, question_history_at timestamptz not null
, question_history_title text not null
, question_history_markdown text not null
);
create unique index question_history_rate_limit_ind on question_history(account_id,question_history_at);

create table answer(
  answer_id integer generated always as identity unique primary key
, question_id integer not null references question
, account_id integer not null references account
, answer_at timestamptz not null default current_timestamp
, answer_markdown text not null check (length(answer_markdown) between 1 and 50000)
, answer_change_at timestamptz not null default current_timestamp
);
create unique index answer_rate_limit_ind on answer(account_id,answer_at);

create table answer_history(
  answer_history_id bigint generated always as identity unique primary key
, answer_id integer not null references answer
, account_id integer not null references account
, answer_history_at timestamptz not null
, answer_history_markdown text not null
);
create unique index answer_history_rate_limit_ind on answer_history(account_id,answer_history_at);

create table tag(
  tag_id integer generated always as identity primary key
, community_id integer not null references community
, tag_at timestamptz not null default current_timestamp
, tag_name text not null check (tag_name~'^[a-z][-0-9a-z]{1,18}[0-9a-z]$')
, tag_description text default '' not null check (length(tag_description)<101)
, tag_implies_id integer
, unique (community_id,tag_id)
, unique (community_id,tag_name)
, foreign key (community_id,tag_implies_id) references tag (community_id,tag_id)
);

create table question_tag_x(
  question_id integer
, tag_id integer
, community_id integer not null
, account_id integer not null references account
, question_tag_x_at timestamptz not null default current_timestamp
, primary key (question_id,tag_id)
, foreign key (community_id,question_id) references question (community_id,question_id)
, foreign key (community_id,tag_id) references tag (community_id,tag_id)
);

create table question_tag_x_history(
  question_tag_x_history_id integer generated always as identity primary key
, question_id integer
, tag_id integer
, community_id integer not null
, question_tag_x_history_added_by_account_id integer not null references account
, question_tag_x_history_removed_by_account_id integer not null references account
, question_tag_x_added_at timestamptz not null
, question_tag_x_removed_at timestamptz not null default current_timestamp
, foreign key (community_id,question_id) references question (community_id,question_id)
, foreign key (community_id,tag_id) references tag (community_id,tag_id)
);

create table question_vote(
  question_id integer references question
, account_id integer references account
, question_vote_at timestamptz default current_timestamp not null
, question_vote_direction integer not null check (question_vote_direction in(-1,0,1))
, question_vote_repute integer not null check (question_vote_repute>=0)
, primary key (question_id,account_id)
, unique (account_id,question_id)
);

create table question_vote_history(
  question_vote_history_id integer generated always as identity primary key
, question_id integer not null
, account_id integer not null
, question_vote_history_at timestamptz not null
, question_vote_history_direction integer not null check (question_vote_history_direction in(-1,0,1))
, question_vote_history_repute integer not null check (question_vote_history_repute>=0)
, foreign key(question_id,account_id) references question_vote
);



