#!/bin/bash
cd "$(dirname "$0")"
cd ../../../

echo "Converting TBS/INPUT/tbs.db to CSV..."

# input file
DB=GDO/TBS/INPUT/TBS.db

# output dir
mkdir GDO/TBS/INPUT/CSV

# obtains all data tables from database
TS=`sqlite3 $DB "SELECT tbl_name FROM sqlite_master WHERE type='table' and tbl_name not like 'sqlite_%';"`

# exports each table to csv
for T in $TS; do

OUT=GDO/TBS/INPUT/CSV/`echo $T`_not_filtered.csv
OUT2=GDO/TBS/INPUT/CSV/`echo $T`.csv

echo $OUT

sqlite3 $DB <<!
.headers on
.separator "," "\n"
.mode csv
.output $OUT
select * from $T;
!

php GDO/TBS/bin/filter_sqlite_csv.php $OUT $OUT2

#rm $OUT

done
