# Create SPASE database and tables.
# Change "spase" to the desired database name and the password (indentified by)
# strings to reflect your system installtion. 
# This database name and passwords are set in the registry configuration file
# located in WEB-INF/conf
create database spase;
use spase;
# Allow local host access
grant select on spase.* to 'spase-user'@'127.0.0.1' identified by 'spase123';
grant select,insert,update,delete on spase.* to 'spase-admin'@'127.0.0.1' identified by 'secret123';
# Create tables
source CreateTables.sql;
