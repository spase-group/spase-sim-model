# Remove all entries for a given version number

# Create SQL commands
echo "delete from dictionary where Version = '$1';" > temp.sqlite
echo "delete from list where Version = '$1';" >> temp.sqlite
echo "delete from member where Version = '$1';" >> temp.sqlite
echo "delete from type where Version = '$1';" >> temp.sqlite
echo "delete from ontology where Version = '$1';" >> temp.sqlite

# Run SQL commands
cat temp.sqlite | sqlite3 spase-model.db

# Clean-up
rm temp.sqlite

