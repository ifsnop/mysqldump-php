#!/bin/bash

function checksum_test001() {
for i in 000 001 002 003 010 011 015 027 029 033 200; do
    mysql -utravis -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

function checksum_test002() {
for i in 201; do
    mysql -utravis --default-character-set=utf8mb4 -B -e "CHECKSUM TABLE test${i}" test002 | grep -v -i checksum
done
}

function checksum_test005() {
for i in 000; do
    mysql -utravis -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

for i in $(seq 0 35) ; do
    ret[$i]=0
done

index=0

mysql -utravis < test001.src.sql; ret[((index++))]=$?
mysql -utravis --default-character-set=utf8mb4 < test002.src.sql; ret[((index++))]=$?
mysql -utravis < test005.src.sql; ret[((index++))]=$?
mysql -utravis < test006.src.sql; ret[((index++))]=$?
mysql -utravis < test008.src.sql; ret[((index++))]=$?
mysql -utravis < test009.src.sql; ret[((index++))]=$?
mysql -utravis < test010.src.sql; ret[((index++))]=$?
mysql -utravis < test011.src.sql; ret[((index++))]=$?

checksum_test001 > test001.src.checksum
checksum_test002 > test002.src.checksum
checksum_test005 > test005.src.checksum
mysqldump -utravis test001 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    --routines=true \
    > mysqldump_test001.sql
ret[((index++))]=$?

mysqldump -utravis test002 \
    --no-autocommit \
    --extended-insert=false \
    --complete-insert=true \
    --hex-blob=true \
    --default-character-set=utf8mb4 \
    > mysqldump_test002.sql
ret[((index++))]=$?

mysqldump -utravis test005 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    > mysqldump_test005.sql
ret[((index++))]=$?

php test.php
ret[((index++))]=$?

mysql -utravis test001 < mysqldump-php_test001.sql
ret[((index++))]=$?
mysql -utravis test002 < mysqldump-php_test002.sql
ret[((index++))]=$?
mysql -utravis test005 < mysqldump-php_test005.sql
ret[((index++))]=$?
mysql -utravis test006b < mysqldump-php_test006.sql
ret[((index++))]=$?
mysql -utravis test009 < mysqldump-php_test009.sql
ret[((index++))]=$?

checksum_test001 > mysqldump-php_test001.checksum
checksum_test002 > mysqldump-php_test002.checksum
checksum_test005 > mysqldump-php_test005.checksum

cat test001.src.sql | grep ^INSERT > test001.filtered.sql
cat test002.src.sql | grep ^INSERT > test002.filtered.sql
cat test005.src.sql | grep ^INSERT > test005.filtered.sql
cat test008.src.sql | grep FOREIGN > test008.filtered.sql
cat test010.src.sql | grep CREATE | grep EVENT > test010.filtered.sql
cat test011.src.sql | grep INSERT > test011.filtered.sql
cat mysqldump_test001.sql | grep ^INSERT > mysqldump_test001.filtered.sql
cat mysqldump_test002.sql | grep ^INSERT > mysqldump_test002.filtered.sql
cat mysqldump_test005.sql | grep ^INSERT > mysqldump_test005.filtered.sql
cat mysqldump-php_test001.sql | grep ^INSERT > mysqldump-php_test001.filtered.sql
cat mysqldump-php_test002.sql | grep ^INSERT > mysqldump-php_test002.filtered.sql
cat mysqldump-php_test005.sql | grep ^INSERT > mysqldump-php_test005.filtered.sql
cat mysqldump-php_test008.sql | grep FOREIGN > mysqldump-php_test008.filtered.sql
cat mysqldump-php_test010.sql | grep CREATE | grep EVENT > mysqldump-php_test010.filtered.sql
cat mysqldump-php_test011a.sql | grep INSERT > mysqldump-php_test011a.filtered.sql
cat mysqldump-php_test011b.sql | grep INSERT > mysqldump-php_test011b.filtered.sql

diff test001.filtered.sql mysqldump_test001.filtered.sql
ret[((index++))]=$?
diff test002.filtered.sql mysqldump_test002.filtered.sql
ret[((index++))]=$?

diff test001.filtered.sql mysqldump-php_test001.filtered.sql
ret[((index++))]=$?
diff test002.filtered.sql mysqldump-php_test002.filtered.sql
ret[((index++))]=$?

diff test001.src.checksum mysqldump-php_test001.checksum
ret[((index++))]=$?
diff test002.src.checksum mysqldump-php_test002.checksum
ret[((index++))]=$?
diff test005.src.checksum mysqldump-php_test005.checksum
ret[((index++))]=$?

diff mysqldump_test005.filtered.sql mysqldump-php_test005.filtered.sql
ret[((index++))]=$?

diff test008.filtered.sql mysqldump-php_test008.filtered.sql
ret[((index++))]=$?

#test reset-auto-increment
test009=`cat mysqldump-php_test009.sql | grep -i ENGINE | grep AUTO_INCREMENT`
if [[ -z $test009 ]]; then ret[((index++))]=0; else ret[((index++))]=1; fi

# test backup events
diff test010.filtered.sql mysqldump-php_test010.filtered.sql
ret[((index++))]=$?

# test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
diff test011.filtered.sql mysqldump-php_test011a.filtered.sql
ret[((index++))]=$?
diff test011.filtered.sql mysqldump-php_test011b.filtered.sql
ret[((index++))]=$?

rm *.checksum 2> /dev/null
rm *.filtered.sql 2> /dev/null
rm mysqldump* 2> /dev/null

echo "Done $index tests"

retvalue=0
for i in $(seq 0 35) ; do
    if [[ ${ret[$i]} -ne 0 ]]; then
        echo "test $i returned ${ret[$i]}"
        retvalue=${ret[$i]}
    fi
    # total=$((${ret[$i]} + $total))
done

echo "Exiting with code $retvalue"

exit $retvalue
