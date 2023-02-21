create table import_package
(
    id             uuid         not null
        primary key,
    status         varchar(20)  not null,
    entity         varchar(255) not null,
    completed_date timestamp(0) default NULL::timestamp without time zone,
    amount         integer,
    created_at     timestamp(0) not null,
    updated_at     timestamp(0) not null,
    started_date   timestamp(0) default NULL::timestamp without time zone,
    last_run_date  timestamp(0) default NULL::timestamp without time zone
);

comment on column import_package.id is '(DC2Type:uuid)';

alter table import_package
    owner to passport;

