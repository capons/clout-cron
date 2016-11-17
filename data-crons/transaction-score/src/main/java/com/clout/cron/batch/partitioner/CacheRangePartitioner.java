package com.clout.cron.batch.partitioner;

import java.util.HashMap;
import java.util.Map;

import org.apache.log4j.Logger;
import org.springframework.batch.core.partition.support.Partitioner;
import org.springframework.batch.item.ExecutionContext;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

/**
 * Partition the number of records to process by evenly distributing 
 * them across the number of threads
 * 
 * @author Ung
 *
 */
@Component("cacheRangePartitioner")
@Scope("step")
public class CacheRangePartitioner implements Partitioner {
	private final static Logger logger = Logger.getLogger(CacheRangePartitioner.class);
	/*
	 * quota for the job
	 */
	@Value("#{jobParameters['cacheRange']}")
	private int range;
	
	
	/*
	 * last primary key from previous job
	 */
	@Value("#{jobParameters['cacheLastProcessedKey']}")
	private int lastProcessedKey;
	
	

	@Override
	public Map<String, ExecutionContext> partition(int gridSize) {

		Map<String, ExecutionContext> result 
                       = new HashMap<String, ExecutionContext>();

		
		/*
		 * using 1 thread
		 */
		if(range < gridSize) {
			ExecutionContext value = new ExecutionContext();
			
			value.put("cacheFromId", (lastProcessedKey)); // {last_key}
			value.put("cacheToId", (lastProcessedKey + range));  // 1500 + {last_key}

			// give each thread a name, thread 1,2,3
			value.put("name", "Thread" + 0);
			value.put("threadId", 0);
			
			result.put("partition" + 0, value);
			
			logger.info("Thread " + 0 + " from " + lastProcessedKey + " to " + range);
			return result;
		}
		
		
		
		int remaining = range % gridSize;
		
		logger.debug( "remaining " + remaining);

		int chunkSize = range / gridSize;
		
		if(remaining > 0 ) {
			chunkSize ++;
		}
		
		logger.info("chunkSize " + chunkSize);
		
		
		int total = 0;
		for (int i = 0; i < gridSize; i++) {
			ExecutionContext value = new ExecutionContext();
			
			logger.debug("\nStarting : Thread" + i);
			
			int cacheFromId = lastProcessedKey + (i * chunkSize) + 1;
			
			int shift = i + 1;

			int cacheToId = (shift * chunkSize) + lastProcessedKey;

			total += chunkSize;
			
			logger.debug("total " + total + " range " + range);
			
			if(total > range) {
				cacheToId = range + lastProcessedKey;
			}

			logger.debug("totalIds " + total);			
			logger.debug("cacheFromId : " + cacheFromId);
			logger.debug("cacheToId : " + cacheToId);

			value.put("cacheFromId", cacheFromId); // 1 * {last_key}
			value.put("cacheToId", cacheToId);  // 1500 * {last_key}

			// give each thread a name, thread 1,2,3
			value.put("name", "Thread" + i);
			value.put("threadId", i);
			
			result.put("partition" + i, value);
			
			logger.info("Thread " + i + " from " + cacheFromId + " to " + cacheToId);
		}
		
		return result;
	}

}

