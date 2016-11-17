#!/bin/sh
# filename: queue_manager.sh
# Limits the number of processors to those given. 
# If at any time the processor/listener fails, it is restored by the cron job.

PROCESSORS=1;
x=0
 
while [ "$x" -lt "$PROCESSORS" ];
do
        PROCESS_COUNT=`pgrep -f schedule_next_job | wc -l`
        if [ $PROCESS_COUNT -ge $PROCESSORS ]; then
                exit 0
        fi
        x=`expr $x + 1`
        php /var/www/html/index.php queue/schedule_next_job/code/scoring &
done
exit 0