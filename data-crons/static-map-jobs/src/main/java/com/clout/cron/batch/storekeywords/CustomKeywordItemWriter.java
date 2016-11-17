package com.clout.cron.batch.storekeywords;

import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.data.mongodb.core.query.Update;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.model.Store;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteRequestBuilder;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;

/**
 * 
 * @author Ung
 *
 */
@Component("customKeywordItemWriter")
@Scope("step")
public class CustomKeywordItemWriter implements ItemWriter<StoreKeywords>{
	private final static Logger logger = Logger.getLogger(CustomKeywordItemWriter.class);
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Resource(name = "storeKeywordUpdateItemWriter")
	private ItemWriter<StoreKeywords> storeKeywordUpdateItemWriter;
	
	private final static int retry = 3;

	@Override
	@Transactional(value="transactionManager", timeout=60000, isolation=Isolation.READ_COMMITTED, propagation=Propagation.REQUIRES_NEW)
	public void write(List<? extends StoreKeywords> items) throws Exception {
		logger.info("In writer with " + items.size());
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			try {
				BasicDBObject searchObject = new BasicDBObject();
				searchObject.put("store_id",  items.get(i).getStoreId());
		
				DBObject modifiedObject =new BasicDBObject();
				modifiedObject.put("$set", new BasicDBObject()
					.append("public_store_key", items.get(i).getPublicStoreKey())
					.append("key_words", items.get(i).getKeyWords())
				);
						
				bulk.find(searchObject).
				upsert().update(modifiedObject);
			}catch(Exception e) {
				e.printStackTrace();
			}
		}
		
		try {
			if(items.size() > 0) {
				BulkWriteResult writeResult = bulk.execute();
				logger.info("DONE with MONGODB UPSERT " + items.size());
			}
		}
		catch(Exception e) {
			StringBuilder b = new StringBuilder();
			for(int i = 0;  i < items.size(); i ++) {
				b.append(items.get(i).getStoreId() + ",");
			}
			logger.info("Failed " + b.toString());
		}

		if(items.size() > 0) {
			storeKeywordUpdateItemWriter.write(items);
		}
		logger.info("Written " + items.size());
	}

}
