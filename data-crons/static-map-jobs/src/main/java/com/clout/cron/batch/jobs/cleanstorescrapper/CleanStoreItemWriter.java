package com.clout.cron.batch.jobs.cleanstorescrapper;

import java.util.ArrayList;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.BaseModel;
import com.clout.cron.batch.model.Store;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;

/**
 * retrieve store by name
 * retrieve store category by category store id
 * 
 * if duplicate log and skip
 * 
 * retrieve business location with geocode
 * retrieve business data with google places API
 * 
 * @author Ung
 *
 */
@Component("cleanStoreItemWriter")
@Scope("step")
public class CleanStoreItemWriter implements ItemWriter<BaseModel>{
	private final static Logger logger = Logger.getLogger(CleanStoreItemWriter.class);
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Resource(name = "insertStoreItemWriter")
	private ItemWriter<Store> insertStoreItemWriter;
	
	private final static int retry = 3;
	
	@Resource
	private JobState jobState;

	@Override
	public void write(List<? extends BaseModel> items) throws Exception {
		logger.info("items to write " + items.size());
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		/**
		 * TODO 
		 * 
		 * Chagne this from update to insert
		 * 
		 */
		List<Store> stores = new ArrayList<Store>();
		for(int i = 0; i < items.size(); i ++) {
			Store store = ((Store)items.get(i));
			bulk.find(new BasicDBObject("store_id", store.getStoreId()))
			.update(new BasicDBObject("$set", 
					new BasicDBObject("public_store_key", store.getPublicStoreKey() ).append("key_words", store.getKeyWords() ) ));

			try {
				BasicDBObject searchObject = new BasicDBObject();
				searchObject.put("store_id", store.getName());

				DBObject modifiedObject =new BasicDBObject();
				modifiedObject.put("$set", new BasicDBObject()
					.append("name", store.getName())
					.append("address_line_1", store.getAddressLine1())
					.append("address_line_2", store.getAddressLine2())
					.append("zipcode", store.getZipcode())
					.append("city", store.getCity())
					.append("state", store.getState())
					.append("phone_number", store.getPhoneNumber())
					.append("website", store.getWebsite())
					.append("longitude", store.getLongitude())
					.append("latitude", store.getLatitude())
				);
						
				bulk.find(searchObject).
				upsert().update(modifiedObject);
			}
			catch(Exception e) {
				logger.error("Mongo query failed " + e.getMessage());
			}
			
			stores.add(store);
		}
		
		
		
		
		/*
		 * Mongo
		 */
		boolean success = false;
		try {
			if(items.size() > 0) {
				BulkWriteResult writeResult = bulk.execute();
				success = true;
				logger.info("DONE with MONGODB UPSERT " + items.size());
			}
		}
		catch(Exception e) {
			for(int i = 0;  i < retry; i ++) {
				try {
					if(items.size() > 0) {
						BulkWriteResult writeResult = bulk.execute();
						logger.info("DONE with MONGODB UPSERT " + items.size());
					}
					success = true;
					break;
				}catch(Exception ex) { 
				}
			}
		}
		
		
		
		
		/*
		 * DB
		 */
		success = false;
		try {
			insertStoreItemWriter.write(stores);
			success = true;
		}catch(Exception e) {
			for(int i = 0;  i < retry; i ++) {
				try {
					if(items.size() > 0) {
						insertStoreItemWriter.write(stores);
						logger.info("DONE with MONGODB UPSERT " + items.size());
					}
					success = true;
					break;
				}catch(Exception ex) { 
				}				
			}
		}
		
		/*
		 * fail/success count on mongo only
		 */
		if(success == false) {
			for(int j = 0; j < items.size();j ++) {
				jobState.incrementFailed();
			}
		}
		else {
			for(int i = 0; i < items.size(); i ++) {
				jobState.incrementSuccess();
			}
		}
		logger.info("Done ");
	}
}
