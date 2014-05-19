#
# Check the content of the metadata model tab files for consistency.
#
# Version check
#
echo "Versions"
echo -n "   dictionary.tab: "
grep -v '^#' ../database/dictionary.tab | cut -f 1 | uniq
echo -n "   list.tab: "
grep -v '^#'  ../database/list.tab | cut -f 1 | uniq
echo -n "   member.tab: "
grep -v '^#'  ../database/member.tab | cut -f 1 | uniq
echo -n "   ontology.tab: "
grep -v '^#'  ../database/ontology.tab | cut -f 1 | uniq
echo -n "   type.tab: "
grep -v '^#'  ../database/type.tab | cut -f 1 | uniq
#
# Structure check
#
echo "Fields"
echo -n "   dictionary.tab: "
awk --field-separator '\t' '{print NF;}' ../database/dictionary.tab | uniq
echo -n "   list.tab: "
awk --field-separator '\t' '{print NF;}' ../database/list.tab | uniq
echo -n "   member.tab: "
awk --field-separator '\t' '{print NF;}' ../database/member.tab | uniq
echo -n "   ontology.tab: "
awk --field-separator '\t' '{print NF;}' ../database/ontology.tab | uniq
echo -n "   type.tab: "
awk --field-separator '\t' '{print NF;}' ../database/type.tab | uniq
echo -n "   history.tab: "
awk --field-separator '\t' '{print NF;}' ../database/history.tab | uniq
#
# Duplicates
#
echo "Duplicates in dictionary.tab"
cut -f 1,3 ../database/dictionary.tab | sort | uniq -d
echo "Duplicates in list.tab"
cut -f 1,3 ../database/list.tab | sort | uniq -d
echo "Duplicates in member.tab"
cut -f 1,3,4 ../database/member.tab | sort | uniq -d
echo "Duplicates in ontology.tab"
cut -f 1,3,4 ../database/ontology.tab | sort | uniq -d
echo "Duplicates in type.tab"
cut -f 1,3 ../database/type.tab | sort | uniq -d
echo "Duplicates in history.tab"
cut -f 1 ../database/history.tab | sort | uniq -d
#
# Ontology order
#
echo "Ontology order duplicates"
cut -f 1,3,5 ../database/ontology.tab | sort | uniq -d
#
# Type definitions
#
echo "Undefined types (found in dictionary.tab)"
cut -f 4 ../database/dictionary.tab | sort | uniq  > $$.tmp
while read term
do
   xx=`grep -c "	$term	" ../database/type.tab`
   if [ $xx -eq 0 ]; then 
      echo $term
   fi
done < $$.tmp
rm $$.tmp
#
# List definitions
#
echo "Undefined lists (found in dictionary.tab)"
grep '	Enumeration	' ../database/dictionary.tab | cut -f 5 | sort | uniq  > $$.tmp
while read term
do
   xx=`grep -c "	$term	" ../database/list.tab`
   if [ $xx -eq 0 ]; then 
      echo $term
   fi
done < $$.tmp
rm $$.tmp
#
# Term definitions
#
echo "Undefined terms (found in ontology.tab)"
cut -f 4 ../database/ontology.tab | sort | uniq  > $$.tmp
while read term
do
   xx=`grep -c "	$term	" ../database/dictionary.tab`
   if [ $xx -eq 0 ]; then 
      echo $term
   fi
done < $$.tmp
rm $$.tmp
#
# Term definitions
#
echo "Undefined terms (found in member.tab)"
cut -f 4 ../database/member.tab | sort | uniq  > $$.tmp
while read term
do
   xx=`grep -c "	$term	" ../database/dictionary.tab`
   if [ $xx -eq 0 ]; then 
      echo $term
   fi
done < $$.tmp
rm $$.tmp
#
# Unused dictionary term
#
echo "Unused dictionary terms"
grep -v '^#' ../database/ontology.tab | cut -f 4 > $$.tmp
grep -v '^#'  ../database/ontology.tab | cut -f 3 >> $$.tmp
grep -v '^#' ../database/member.tab | cut -f 4 >> $$.tmp
sort $$.tmp | uniq > $$-2.tmp
grep -v '^#' ../database/dictionary.tab | cut -f 3 | sort | uniq > $$.tmp
diff $$.tmp $$-2.tmp
rm $$.tmp $$-2.tmp

