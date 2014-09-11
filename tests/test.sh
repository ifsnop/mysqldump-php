mysql -uroot < original.sql
mysqldump -uroot test001 --extended-insert=false --hex-blob=true > mysqldump.sql

cat original.sql | grep ^INSERT > original.filtered.sql
cat mysqldump.sql | grep ^INSERT > mysqldump.filtered.sql

diff original.filtered.sql mysqldump.filtered.sql

ret=$?

rm original.filtered.sql
rm mysqldump.filtered.sql
rm mysqldump.sql

echo $ret




