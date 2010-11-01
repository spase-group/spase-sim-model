# Make the UML (xmi) files for a version of the SPASE data model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#
version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Make the UML xmi file
cd /var/www/spase/root/data/model
/var/www/spase/root/WEB-INF/runjava.sh org.spase.model.util.MakeXMI $version > spase-$vername.xmi
