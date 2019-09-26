# Run a set of sqlite3 commands on the database containing SPASE data model.
# $1: Database file name.

dbname=${1:-spase-model}

# Create tables (if needed)
cat CreateTables.sqlite | sqlite3 $dbname".db"

# Remove header lines from tables - create local copy
grep -v '^#' ../database/dictionary.tab > dictionary.tab
grep -v '^#' ../database/history.tab > history.tab
grep -v '^#' ../database/list.tab > list.tab
grep -v '^#' ../database/member.tab > member.tab
grep -v '^#' ../database/ontology.tab > ontology.tab
grep -v '^#' ../database/type.tab > type.tab

# load data
cat load-model.sqlite | sqlite3 $dbname".db"

# Clean-up
rm dictionary.tab
rm history.tab
rm list.tab
rm member.tab
rm ontology.tab
rm type.tab
