package com.clout.cron.batch.jobs.cache;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.core.StepExecution;
import org.springframework.batch.core.annotation.BeforeStep;
import org.springframework.batch.item.ExecutionContext;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.util.AddressSanitizationUtil;
import com.clout.cron.batch.util.StringSanitizerUtil;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;

/**
 * 
 * @author Ung
 *
 */
@Component("cacheItemWriter")
@Scope("step")
public class CustomCacheItemWriter implements ItemWriter<Store>{
	private final static Logger logger = Logger.getLogger(CustomCacheItemWriter.class);
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	private StepExecution stepExecution;
	
	@Resource(name = "jobState")
	private JobState jobState;
	
	private final static int retry = 3;

	@Override
	public void write(List<? extends Store> items) throws Exception {
		logger.info("in item writer");
		 
		DBCollection collection = mongoTemplate.getCollection("store_keys");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		StringBuilder builder = new StringBuilder();
		for(Store store : items) {
			builder.append(store.getStoreId() + ",");
			jobState.setLastCachedStoreKey(store.getStoreId());
			if(store.getName() == null) {
				logger.info("Store name is null " + store.getStoreId());
				continue;
			}

			String storeName = StringSanitizerUtil.sanitize(store.getName().trim().toLowerCase());
			
			logger.debug("store is " + storeName);
			
			String key = storeName;
			logger.debug("Working on storeId " + store.getStoreId());
			
			if(store.getZipcode() != null && !store.getZipcode().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(store.getZipcode());
			}
			
			if(store.getCity() != null && !store.getCity().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(store.getCity());
			}
			
			if(store.getState() != null && !store.getState().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(store.getState());
			}
			
			if(store.getAddressLine1() != null && !store.getAddressLine1().isEmpty()) {
				key += "," + AddressSanitizationUtil.normalizeAddress(StringSanitizerUtil.sanitize(store.getAddressLine1()));
			}
			
			if(store.getAddressLine2() != null && !store.getAddressLine2().isEmpty()) {
				key += "," + AddressSanitizationUtil.normalizeAddress(StringSanitizerUtil.sanitize(store.getAddressLine2()));
			}
			
			key = key.toLowerCase();				
						
			createMongoUpsert(bulk, key, store.getStoreId().toString());
			logger.info("Caching [" + store.getStoreId() + "] ["+ key + "]");
		}
		
		try {
			if(items.size() > 0) {
				BulkWriteResult writeResult = bulk.execute();
				logger.info("DONE with MONGODB UPSERT (" + items.size() + ") ids: [" + builder.toString() + "]");
			}
		}
		catch(Exception e) {			
			synchronized(this) {
				failedStoreIds(builder.toString().substring(0, builder.toString().length() - 1));
			}
			
			logger.error("Error creating store keys in mongodb [" + builder.toString() + "]");
		}
	}
	
	private void createMongoUpsert(BulkWriteOperation bulk, String key, String storeId) throws CronJobException {
		try {
			BasicDBObject searchObject = new BasicDBObject();
			searchObject.put("name",  key);
	
			DBObject modifiedObject =new BasicDBObject();
			modifiedObject.put("$set", new BasicDBObject()
				.append("name", key)
				.append("store_id", storeId)
			);
					
			bulk.find(searchObject).
			upsert().update(modifiedObject);
			logger.debug("INSERTING TO MONGODB storeId " + storeId);
		}catch(Exception e) {
			logger.error("Unable to INSERT " + e.getMessage());
			StringBuilder builder = new StringBuilder();
			builder.append(storeId + ",");
			synchronized(this) {
				failedStoreIds(builder.toString().substring(0, builder.toString().length() - 1));
			}
		}
	}
	
	@BeforeStep
	public void getInterstepData(StepExecution stepExecution) {
		this.stepExecution = stepExecution;
		failedStoreIds("");
	}

	private synchronized void failedStoreIds(String newFailedStoreIds) {
		String failedStoreIds = (String) stepExecution.getJobExecution().getExecutionContext().get("failedStoreIds");
		if(failedStoreIds == null || failedStoreIds.isEmpty()) {
			stepExecution.getJobExecution().getExecutionContext().put("failedStoreIds", newFailedStoreIds);
		}
		else {
			if(newFailedStoreIds != null && !newFailedStoreIds.isEmpty()) {
				stepExecution.getJobExecution().getExecutionContext().put("failedStoreIds", failedStoreIds + "," + newFailedStoreIds);
			}
		}
	}
}
