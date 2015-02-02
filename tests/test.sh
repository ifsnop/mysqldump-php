#!/bin/bash

function checksum_test001() {
for i in 000 001 002 003 010 011 015 027 029 033 200; do
    mysql -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

function checksum_test002() {
for i in 201; do
    mysql --default-character-set=utf8mb4 -B -e "CHECKSUM TABLE test${i}" test002 | grep -v -i checksum
done
}

for i in $(seq 0 20) ; do
    ret[$i]=0
done

mysql -e "CREATE USER 'travis'@'localhost' IDENTIFIED BY '';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test001.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test002.* TO 'travis'@'localhost';" 2> /dev/null

mysql -uroot < test001.src.sql; ret[0]=$?

mysql -uroot --default-character-set=utf8mb4 < test002.src.sql; ret[1]=$?

checksum_test001 > test001.src.checksum
checksum_test002 > test002.src.checksum

mysqldump -uroot test001 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    > mysqldump_test001.sql
ret[2]=$?

mysqldump -uroot test002 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    --default-character-set=utf8mb4 \
    > mysqldump_test002.sql
ret[3]=$?

php test.php
ret[4]=$?

mysql -uroot test001 < mysqldump-php_test001.sql
ret[5]=$?
mysql -uroot test002 < mysqldump-php_test002.sql
ret[6]=$?

checksum_test001 > mysqldump-php_test001.checksum
checksum_test002 > mysqldump-php_test002.checksum

cat test001.src.sql | grep ^INSERT > test001.filtered.sql
cat test002.src.sql | grep ^INSERT > test002.filtered.sql
cat mysqldump_test001.sql | grep ^INSERT > mysqldump_test001.filtered.sql
cat mysqldump_test002.sql | grep ^INSERT > mysqldump_test002.filtered.sql
cat mysqldump-php_test001.sql | grep ^INSERT > mysqldump-php_test001.filtered.sql
cat mysqldump-php_test002.sql | grep ^INSERT > mysqldump-php_test002.filtered.sql

diff test001.filtered.sql mysqldump_test001.filtered.sql
ret[7]=$?
diff test002.filtered.sql mysqldump_test002.filtered.sql
ret[8]=$?

diff test001.filtered.sql mysqldump-php_test001.filtered.sql
ret[9]=$?
diff test002.filtered.sql mysqldump-php_test002.filtered.sql
ret[10]=$?

diff test001.src.checksum mysqldump-php_test001.checksum
ret[11]=$?
diff test002.src.checksum mysqldump-php_test002.checksum
ret[12]=$?

rm *.checksum 2> /dev/null
rm *.filtered.sql 2> /dev/null
rm mysqldump* 2> /dev/null

total=0
for i in $(seq 0 20) ; do
    total=$((${ret[$i]} + $total))
done

exit $total
