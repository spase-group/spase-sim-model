# Make the JAXB generated java classes to support a version of i
# the SPASE data model.
# Designed for the SPASE website envronment.
#
# Author: Todd King
#
version=${1:-1.2.0}
vername=`echo $version | sed 's/\./_/g'`
verpack=`echo $version | sed 's/\.//g'`

# Path to JAXB
export JAXB_HOME=/var/www/spase/root/WEB-INF/tools/jaxb

# Make the parser files
cd /var/www/temp/spase/build
/bin/rm -R -f parser$verpack
/bin/rm -R -f META-INF
mkdir META-INF
mkdir parser$verpack
cd parser$verpack

/var/www/spase/root/WEB-INF/tools/jaxb/bin/xjc.sh -episode ../META-INF/episode.xml -p org.spase.parser$verpack /var/www/spase/root/data/schema/spase-$vername.xsd -d .  

# Now compile
javac -Djava.ext.dirs=/var/www/spase/root/WEB-INF/lib:$CLASSPATH:/var/www/spase/root/WEB-INF/tools/jaxb/lib -d .. org/spase/parser$verpack/*.java

# Build documentation
javadoc -extdirs /var/www/spase/root/WEB-INF/lib:$CLASSPATH:/var/www/spase/root/WEB-INF/tools/jaxb/lib -d ../api/parser$verpack org/spase/parser$verpack/*.java

# Build JAR file
cd ..
jar cf spase-jaxb-parser$verpack.jar META-INF parser$verpack org/spase/parser$verpack api/parser$verpack

# Distribute
cp spase-jaxb-parser$verpack.jar /var/www/spase/root/tools/parser
mkdir /var/www/spase/root/tools/parser/jaxb/api$verpack
/bin/rm -R -f /var/www/spase/root/tools/parser/jaxb/api$verpack/*
(cd api/parser$verpack; tar cf - .) | (cd /var/www/spase/root/tools/parser/jaxb/api$verpack; tar xf - .)

