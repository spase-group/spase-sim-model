# Build all the type of documents related to a version of the SPASE model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#
version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Make the document
./makedoc.sh $version > /var/www/spase/root/data/doc/spase-$vername-draft.pdf 

# Make the schema
./makexsd.sh $version

# Make the XMI model
./makexmi.sh $version

# Make the editor files
# ./makeeditor.sh $version

# Make the registry files
./makeregistry.sh $version

# Make XSL files
./makexsl.sh $version

# Make the parser files (classic and JAXB)
./makeparser.sh $version
./makejaxb.sh $version
