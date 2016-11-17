package com.clout.cron.batch.services;

import java.util.List;
import java.util.Map;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.util.AddressSanitizationUtil;
import com.clout.cron.batch.util.StringSanitizerUtil;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;


@Component("storeKeyService")
public class StoreKeyService implements StoreKeyServiceInterface {
	private final static Logger logger = Logger.getLogger(StoreKeyService.class);
	
	@Resource
	private MongoTemplate mongoTemplate;

	public void write(Map<String, String> storeIds) throws Exception {
		logger.info("cache new store keys " + storeIds.size());
		 
		DBCollection collection = mongoTemplate.getCollection("store_keys");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		for(Map.Entry<String, String> entry: storeIds.entrySet()) {
			createMongoUpsert(bulk, entry.getKey(), entry.getValue());
		}
		
		try {
			BulkWriteResult writeResult = bulk.execute();
			logger.info("DONE with MONGODB UPSERT");
		}
		catch(Exception e) {
			logger.error("Error creating store keys in mongodb");
		}
	}
	
	private void createMongoUpsert(BulkWriteOperation bulk, String key, String storeId) {
		BasicDBObject searchObject = new BasicDBObject();
		searchObject.put("name",  key);

		DBObject modifiedObject =new BasicDBObject();
		modifiedObject.put("$set", new BasicDBObject()
			.append("name", key)
			.append("store_id", storeId)
		);
				
		bulk.find(searchObject).
		upsert().update(modifiedObject);	
		logger.info("INSERTING TO MONGODB storeId " + storeId);
	}
}