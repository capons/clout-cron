#!/bin/sh

# SCRIPT TO REGULARLY CLEAN UP RUNDECK JOBS:

# keep last 5 executions for each job
KEEP=5


cd /etc/rundeck/var/logs/rundeck

JOBS=`find . -maxdepth 3 -path "*/job/*" -type d`

for j in $JOBS ; do
        echo "Processing job $j"
        ids=`find $j -iname "*.rdlog" | sed -e "s/.*\/\([0-9]*\)\.rdlog/\1/" | sort -n -r`
        declare -a JOBIDS=($ids)

        if [ ${#JOBIDS[@]} -gt $KEEP ]; then
          for job in ${JOBIDS[@]:$KEEP};do
             echo " * Deleting job: $job"
             echo "   rm -rf $j/logs/$job.*"
             #rm -rf $j/logs/$job.*
          done
        fi
done