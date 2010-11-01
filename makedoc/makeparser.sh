# Make the parser files for a version of the SPASE data model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#
version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Make the parser files
cd /var/www/temp/spase/build/
mkdir parser$verpack
cd parser$verpack
/var/www/spase/root/WEB-INF/runjava.sh org.spase.model.util.MakeParser $version

# Now compile
javac -Djava.ext.dirs=/var/www/spase/root/WEB-INF/lib:$CLASSPATH -d .. *.java

# Build documentation
javadoc -d ../api/parser$verpack *.java

# Build JAR file
cd ..
jar cf parser$verpack.jar parser$verpack spase/parser$verpack api/parser$verpack

# Distribute
cp parser$verpack.jar /var/www/spase/root/tools/parser
mkdir /var/www/spase/root/tools/parser/api$verpack
/bin/rm -R -f /var/www/spase/root/tools/parser/api$verpack/*
(cd api/parser$verpack; tar cf - .) | (cd /var/www/spase/root/tools/parser/api$verpack; tar xf - .)

