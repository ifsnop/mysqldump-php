#!/bin/bash

for i in $(seq 0 20) ; do
    ret[i]=0
done

mysql -uroot < original.sql
ret[0]=$?

mysqldump -uroot test001 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    > mysqldump.sql
ret[1]=$?

php test.php
ret[2]=$?

mysql -uroot test001 < mysqldump-php.sql
ret[3]=$?

cat original.sql | grep ^INSERT > original.filtered.sql
cat mysqldump.sql | grep ^INSERT > mysqldump.filtered.sql
cat mysqldump-php.sql | grep ^INSERT > mysqldump-php.filtered.sql

diff original.filtered.sql mysqldump.filtered.sql
ret[4]=$?

diff original.filtered.sql mysqldump-php.filtered.sql
ret[5]=$?

rm original.filtered.sql
rm mysqldump.filtered.sql
rm mysqldump-php.filtered.sql
rm mysqldump-php.sql
rm mysqldump.sql

total=0
for i in $(seq 0 20) ; do
    total=(${ret[i]} + $total)
done

exit $total
