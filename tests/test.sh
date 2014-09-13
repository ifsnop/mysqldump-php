#!/bin/bash

for i in $(seq 0 20) ; do
    ret[i]=-1
done

mysql -uroot < original.sql
ret[0]=$?

mysqldump -uroot test001 --extended-insert=false --hex-blob=true > mysqldump.sql
ret[1]=$?

php test.php
ret[2]=$?

cat original.sql | grep ^INSERT > original.filtered.sql
cat mysqldump.sql | grep ^INSERT > mysqldump.filtered.sql
cat mysqldump-php.sql | grep ^INSERT > mysqldump-php.filtered.sql

diff original.filtered.sql mysqldump.filtered.sql
ret[3]=$?

diff original.filtered.sql mysqldump-php.filtered.sql
ret[4]=$?

rm original.filtered.sql
rm mysqldump.filtered.sql
rm mysqldump-php.filtered.sql
rm mysqldump-php.sql
rm mysqldump.sql

total=0
for i in $(seq 0 4) ; do
    total=(${ret[i]} + $total)
done

exit $total
