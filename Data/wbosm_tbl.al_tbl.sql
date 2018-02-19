SET CLIENT_ENCODING TO UTF8;
SET STANDARD_CONFORMING_STRINGS TO ON;
BEGIN;
CREATE TABLE "al_tbl" (gid serial,
"id" int4,
"country" varchar(254),
"name" varchar(254),
"enname" varchar(254),
"locname" varchar(254),
"offname" varchar(254),
"boundary" varchar(254),
"adminlevel" int4,
"wikidata" varchar(254),
"wikimedia" varchar(254),
"timestamp" varchar(254),
"note" varchar(254),
"rpath" varchar(254));
ALTER TABLE "al_tbl" ADD PRIMARY KEY (gid);
SELECT AddGeometryColumn('','al_tbl','geom','4326','MULTIPOLYGON',2);
CREATE INDEX ON "al_tbl" USING GIST ("geom");
COMMIT;
ANALYZE "al_tbl";
