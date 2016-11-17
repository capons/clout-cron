nohup java -jar  -Xmx2048m -Xms1027m -Dlog4j.configuration="file:/opt/clout/log4j.xml"  target/data-processing-cron-executable.jar staticMapJob spring/batch/jobs/staticmap.xml >/dev/null 2>&1 &
