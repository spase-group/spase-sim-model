#
# Load data
#
load data local infile 'dictionary.tab' replace into table dictionary ignore 1 lines;
load data local infile 'list.tab' replace into table list ignore 1 lines;
load data local infile 'member.tab' replace into table member ignore 1 lines;
load data local infile 'type.tab' replace into table type ignore 1 lines;
load data local infile 'ontology.tab' replace into table ontology ignore 1 lines;
#
# Load history
#
truncate history;
load data local infile 'history.tab' replace into table history ignore 1 lines;
