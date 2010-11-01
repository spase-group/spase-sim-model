# Make the XML stylesheet (XSL) files for a version of the SPASE data model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#
version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Make the editor XSL
cd /var/www/temp/spase/build/xsl
mkdir edit$verpack
cd edit$verpack
/var/www/spase/root/WEB-INF/runjava.sh org.spase.model.util.MakeXSL $version edit

# Make the display XSL
cd /var/www/temp/spase/build/xsl
mkdir display$verpack
cd display$verpack
/var/www/spase/root/WEB-INF/runjava.sh org.spase.model.util.MakeXSL $version display

# Package XSL files
cd /var/www/temp/spase/build
zip -r spase-xsl-$verpack.zip xsl/editor$verpack xsl/display$verpack
cp spase-xsl-$verpack.zip /var/www/spase/root/tools/stylesheet/html
