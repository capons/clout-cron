java -jar -Xmx2048m -Xms1024m -Dlog4j.configuration="file:/opt/clout/log4j.xml" target/data-processing-cron-executable.jar yelpDataJob spring/batch/jobs/yelps-data.xml --city=los angeles --state=ca

