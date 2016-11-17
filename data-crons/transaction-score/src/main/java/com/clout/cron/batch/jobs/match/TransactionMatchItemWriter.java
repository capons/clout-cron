package com.clout.cron.batch.jobs.match;

import java.util.ArrayList;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.BaseItem;
import com.clout.cron.batch.jobitems.BusinessItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.TransactionSubCategories;
import com.clout.cron.batch.model.Transactions;
import com.clout.cron.batch.model.TransactionsRaw;
import com.clout.cron.batch.services.TransactionRawServiceInterface;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;

/**
 * ALERT: This is not used due to only need to insert directly to DB
 * 
 * 
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
@Component("businessDataItemWriter")
@Scope("step")
@Transactional(value = "writeTransactionDataSource", rollbackFor = Exception.class, propagation = Propagation.REQUIRED, isolation =Isolation.SERIALIZABLE )
public class TransactionMatchItemWriter implements ItemWriter<BaseItem>{
	private final static Logger logger = Logger.getLogger(TransactionMatchItemWriter.class);
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Resource(name="readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;
	
	@Resource(name = "insertStoreItemWriter")
	private ItemWriter<Store> insertStoreItemWriter;
	
	@Resource(name = "insertTransactionItemWriter")
	private ItemWriter<Transactions> insertTransactionItemWriter;
	
	@Resource(name = "storeUpdateItemWriter")
	private ItemWriter<Store> storeUpdateItemWriter;
	
	@Resource(name = "insertTransactionSubCategoriesItemWriter")
	private ItemWriter<TransactionSubCategories> insertTransactionSubCategoriesItemWriter;
	
	@Resource(name = "updateTransactionRawItemWriter")
	private ItemWriter<TransactionsRaw> updateTransactionRawItemWriter;
	
	@Resource
	private JobState jobState;
	

	@Override
	public void write(List<? extends BaseItem> items) throws Exception {
		logger.info("items to write " + items.size());
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		List<Store> updateStores = new ArrayList<Store>();
		List<Store> insertStores = new ArrayList<Store>();
		List<Transactions> transactions = new ArrayList<Transactions>();
		List<TransactionSubCategories> categories = new ArrayList<TransactionSubCategories>();
		List<TransactionsRaw> raws = new ArrayList<TransactionsRaw>();

		StringBuilder storeIds = new StringBuilder();
		StringBuilder transactionIds = new StringBuilder();

		for(int i = 0; i < items.size(); i ++) {
			BusinessItem item = ((BusinessItem)items.get(i));
			raws.add(item.getRaw());
			
			if(item.getStore() != null) {
				storeIds.append(item.getStore().getStoreId() + ",");
				if(item.isUpdateStore() == true) {
					logger.info("updating store " + item.getStore().getName());
					updateStores.add(item.getStore());
				}
				else {
					logger.info("inserting store " + item.getStore().getName());
					insertStores.add(item.getStore());
				} 
				
				try {
					BasicDBObject searchObject = new BasicDBObject();
					searchObject.put("name", item.getStore().getName());
					if(item.getStore().getStoreId() != null) {
						searchObject.put("store_id", item.getStore().getStoreId().intValue());
					}

					DBObject modifiedObject =new BasicDBObject();
					modifiedObject.put("$set", new BasicDBObject()
						.append("store_id", item.getStore().getStoreId().intValue())
						.append("name", item.getStore().getName())
						.append("address_line_1", item.getStore().getAddressLine1())
						.append("address_line_2", item.getStore().getAddressLine2())
						.append("phone_number", item.getStore().getPhoneNumber())
						.append("website", item.getStore().getWebsite())
						.append("zipcode", item.getStore().getZipcode())
						.append("city", item.getStore().getCity())
						.append("state", item.getStore().getState())
						.append("public_store_key", item.getStore().getPublicStoreKey())
						.append("key_words", item.getStore().getKeyWords())
					);
							
					bulk.find(searchObject).
					upsert().update(modifiedObject);
				}
				catch(Exception e) {
					logger.error("Mongo query failed " + e.getMessage());
				}				
			}
			
			if(item.getTransactions() != null) {
				logger.info("inserting transactions " + item.getTransactions().getRawStoreName());
				transactions.add(item.getTransactions());
				transactionIds.append(item.getTransactions().getId() + ",");
				if(item.getCategories() != null) {
					categories.add(item.getCategories());
				}
			}
		}
		
		try {
			if(!insertStores.isEmpty() || !updateStores.isEmpty()) {
				logger.info("Bulk update mongodb");
				BulkWriteResult writeResult = bulk.execute();	
			}
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert stores to mongodb due to " + e.getMessage() + " storeIds [" + storeIds.toString() + "]");
		}

		logger.info("Inserting stores " + insertStores.size() + " Updating Stores: " + updateStores.size() + " inserting transactions: " + transactions.size());

		try {
			if(!insertStores.isEmpty()) {
				insertStoreItemWriter.write(insertStores);
			}
			
			if(!updateStores.isEmpty()) {
				storeUpdateItemWriter.write(updateStores);
			}
	
			logger.info("Writing transactions to db " + transactions.size());
			// if transaction fails no categories should be created
			insertTransactionItemWriter.write(transactions);
			
			if(!categories.isEmpty()) {
				logger.info("Writing transaction sub categories to DB " + categories.size());
				insertTransactionSubCategoriesItemWriter.write(categories);
			}
	
			for(Transactions t : transactions) {
				jobState.incrementSuccess();
				jobState.setLastProcessedKey(t.getRawId());				
			}
			
			updateTransactionRawItemWriter.write(raws);
		}catch(Exception e) {
			e.printStackTrace();
			throw new CronJobException("Unable to save data to database due to " + e.getMessage() + " storeIds [" + storeIds.toString() + "]" + " transIds [" + transactionIds.toString() + "]");
		}
		logger.info("Done");
	}
}
