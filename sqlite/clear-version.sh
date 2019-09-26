# Remove all entries for a given version number

version=$1
dbname=${2:-spase-model}

# Create SQL commands
echo "delete from dictionary where Version = '$version';" > temp.sqlite
echo "delete from list where Version = '$version';" >> temp.sqlite
echo "delete from member where Version = '$version';" >> temp.sqlite
echo "delete from type where Version = '$version';" >> temp.sqlite
echo "delete from ontology where Version = '$version';" >> temp.sqlite

# Run SQL commands
cat temp.sqlite | sqlite3 $dbname".db"

# Clean-up
rm temp.sqlite

