mysql -uroot < original.sql
mysqldump -uroot test001 --extended-insert=false --hex-blob=true > mysqldump.sql
php test.php

cat original.sql | grep ^INSERT > original.filtered.sql
cat mysqldump.sql | grep ^INSERT > mysqldump.filtered.sql
cat mysqldump-php.sql | grep ^INSERT > mysqldump-php.filtered.sql

diff original.filtered.sql mysqldump.filtered.sql
ret1=$?

diff original.filtered.sql mysqldump-php.filtered.sql
ret2=$?

rm original.filtered.sql
rm mysqldump.filtered.sql
rm mysqldump-php.filtered.sql
rm mysqldump-php.sql
rm mysqldump.sql

exit $ret1



