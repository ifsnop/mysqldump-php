#!/bin/bash

mysql -e "DROP DATABASE test001;"
mysql -e "DROP DATABASE test002;"
mysql -e "DROP DATABASE test005;"
mysql -e "DROP DATABASE test006a;"
mysql -e "DROP DATABASE test006b;"
mysql -e "DROP DATABASE test008;"
mysql -e "DROP DATABASE test009;"

mysql -e "DROP USER 'travis'";

mysql -e "FLUSH PRIVILEGES;"
