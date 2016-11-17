java -jar -Xmx2048m -Xms1024m -Dlog4j.configuration="file:/opt/clout/log4j.xml" target/data-processing-cron-executable.jar yellowpagesDataJob spring/batch/jobs/yellowpages-data.xml --city=los angeles --state=ca

