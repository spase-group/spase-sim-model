# Make the registry files for a version of the SPASE data model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#

version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Make the registry files
cd /var/www/spase/root/tools/registry
/var/www/spase/root/WEB-INF/runjava.sh org.spase.model.util.MakeXSL $version display
