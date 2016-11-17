package com.clout.cron.batch.jobs.score;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.jobitems.BaseItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.jobitems.ScoreItem;
import com.clout.cron.batch.model.Transactions;
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
@Component("scoreItemWriter")
@Scope("step")
@Transactional("writeStoreTransactionManager")
public class ScoreItemWriter implements ItemWriter<BaseItem>{
	private final static Logger logger = Logger.getLogger(ScoreItemWriter.class);
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Resource(name="readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;

	@Resource(name = "transactionUpdateItemWriter")
	private ItemWriter<Transactions> transactionUpdateItemWriter;

	
	@Resource
	private JobState jobState;
	

	@Override
	public void write(List<? extends BaseItem> items) throws Exception {
		logger.info("items to write " + items.size());		

		List<Transactions> transactions = new ArrayList<Transactions>();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			
			if(item.getTransactions() != null) {
				logger.info("inserting transactions " + item.getTransactions().getRawStoreName());
				transactions.add(item.getTransactions());				
			}
		}
		/*
		DBCollection storeSpendingByDaysCollection = mongoTemplate.getCollection("spending_pattern_stores_by_days");
		BulkWriteOperation bulk = storeSpendingByDaysCollection.initializeOrderedBulkOperation();

		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserStoreTransactionKeyByDays() != null && !item.getUserStoreTransactionKeyByDays().isEmpty()) {
				updateFrequency(bulk, item.getUserStoreTransactionKeyByDays(), item.getAmount(), item.getTransactionDateByDays());
			}
		}
		
		try {
			logger.info("Bulk update mongodb stores by days");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert stores by days to mongodb due to " + e.getMessage());
		}
		
		
		DBCollection chainSpendingByDaysCollection = mongoTemplate.getCollection("spending_pattern_chains_by_days");
		bulk = chainSpendingByDaysCollection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserChainTransactionKeyByDays() != null && !item.getUserChainTransactionKeyByDays().isEmpty()) {
				updateFrequency(bulk, item.getUserChainTransactionKeyByDays(), item.getAmount(), item.getTransactionDateByDays());
			}
		}
		
		
		try {
			logger.info("Bulk update mongodb chain by days");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert chain by days to mongodb due to " + e.getMessage());
		}

		
		DBCollection userSpendingByDaysCollection = mongoTemplate.getCollection("spending_pattern_users_by_days");
		bulk = userSpendingByDaysCollection.initializeOrderedBulkOperation();
		
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserTransactionKeyByDays() != null && !item.getUserTransactionKeyByDays().isEmpty()) {
				updateFrequency(bulk, item.getUserTransactionKeyByDays(), item.getAmount(), item.getTransactionDateByDays());
			}
		}
		
		
		try {
			logger.info("Bulk update mongodb users by days");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert users by days to mongodb due to " + e.getMessage());
		}
		
		DBCollection promoSpendingByDaysCollection = mongoTemplate.getCollection("spending_pattern_promo_by_days");
		bulk = promoSpendingByDaysCollection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserRelatedPromoTransactionKeyByDays() != null && !item.getUserRelatedPromoTransactionKeyByDays().isEmpty()) {
				updateFrequency(bulk, item.getUserRelatedPromoTransactionKeyByDays(), item.getAmount(), item.getTransactionDateByDays());
			}
		}
		
		try {
			logger.info("Bulk update mongodb promo by days");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert promo by days to mongodb due to " + e.getMessage());
		}
		
		
		DBCollection collection = mongoTemplate.getCollection("spending_pattern_store_by_months");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified()  && item.getUserStoreTransactionKeyByMonths() != null && !item.getUserStoreTransactionKeyByMonths().isEmpty()) {
				updateFrequency(bulk, item.getUserStoreTransactionKeyByMonths(), item.getAmount(), item.getTransactionDateByMonths());
			}
		}
		
		try {
			logger.info("Bulk update mongodb stores by months");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert stores by months to mongodb due to " + e.getMessage());
		}		
		
		
		
		collection = mongoTemplate.getCollection("spending_pattern_chain_by_months");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserChainTransactionKeyByMonths() != null && !item.getUserChainTransactionKeyByMonths().isEmpty()) {
				updateFrequency(bulk, item.getUserChainTransactionKeyByMonths(), item.getAmount(), item.getTransactionDateByMonths());
			}
		}
		
		try {
			logger.info("Bulk update mongodb chains by months");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert chains by months to mongodb due to " + e.getMessage());
		}		
		
		
		collection = mongoTemplate.getCollection("spending_pattern_user_by_months");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserTransactionKeyByMonths() != null && !item.getUserTransactionKeyByMonths().isEmpty()) {
				updateFrequency(bulk, item.getUserTransactionKeyByMonths(), item.getAmount(), item.getTransactionDateByMonths());
			}
		}
		
		try {
			logger.info("Bulk update mongodb users by months");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert users by months to mongodb due to " + e.getMessage());
		}		
			
		collection = mongoTemplate.getCollection("spending_pattern_promo_by_months");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserRelatedPromoTransactionKeyByMonths() != null && !item.getUserRelatedPromoTransactionKeyByMonths().isEmpty()) {
				updateFrequency(bulk, item.getUserRelatedPromoTransactionKeyByMonths(), item.getAmount(), item.getTransactionDateByMonths());
			}
		}
		
		try {
			logger.info("Bulk update mongodb promo by months");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert promo by months to mongodb due to " + e.getMessage());
		}		
		
		collection = mongoTemplate.getCollection("spending_pattern_stores_by_lifetime");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserStoreTransactionKeyByLifetime() != null && !item.getUserStoreTransactionKeyByLifetime().isEmpty()) {
				updateFrequency(bulk, item.getUserStoreTransactionKeyByLifetime(), item.getAmount(), null);
			}
		}
		
		try {
			logger.info("Bulk update mongodb stores by lifetime");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert stores by lifetime to mongodb due to " + e.getMessage());
		}		
		
		collection = mongoTemplate.getCollection("spending_pattern_chain_by_lifetime");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserChainTransactionKeyByLifetime() != null && !item.getUserChainTransactionKeyByLifetime().isEmpty()) {
				updateFrequency(bulk, item.getUserChainTransactionKeyByLifetime(), item.getAmount(), null);
			}
		}
		
		try {
			logger.info("Bulk update mongodb chains by lifetime");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert chains by lifetime to mongodb due to " + e.getMessage());
		}		
		*/
		
		DBCollection collection = mongoTemplate.getCollection("spending_pattern_user_by_lifetime");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserTransactionKeyByLifetime() != null && !item.getUserTransactionKeyByLifetime().isEmpty()) {
				updateFrequency(bulk, item.getUserTransactionKeyByLifetime(), item.getAmount(), null);
			}
		}
		
		try {
			logger.info("Bulk update mongodb users by lifetime");
			BulkWriteResult writeResult = bulk.execute();	
			logger.info("SUCCESS");
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert users by lifetime to mongodb due to " + e.getMessage());
		}		
		
		/*
		collection = mongoTemplate.getCollection("spending_pattern_promo_by_lifetime");
		bulk = collection.initializeOrderedBulkOperation();
		
		for(int i = 0; i < items.size(); i ++) {
			ScoreItem item = ((ScoreItem)items.get(i));
			if(!item.isUnqualified() && item.getUserRelatedPromoTransactionKeyByLifetime() != null && !item.getUserRelatedPromoTransactionKeyByLifetime().isEmpty()) {
				updateFrequency(bulk, item.getUserRelatedPromoTransactionKeyByLifetime(), item.getAmount(), null);
			}
		}
		
		try {
			logger.info("Bulk update mongodb promo by lifetime");
			BulkWriteResult writeResult = bulk.execute();	
			//logger.info("mongodb inserted " + writeResult.getInsertedCount() + " updated " + writeResult.getModifiedCount());
		}catch(Exception e) {
			e.printStackTrace();
			logger.error("Unable to insert promo by lifetime to mongodb due to " + e.getMessage());
		}		
		*/	
				

		try {
			try {
				if(transactions.size() > 0) {
					logger.info("Update transactions: " + transactions.size());
					transactionUpdateItemWriter.write(transactions);
					
					// for each transaction pull the store by business id and update transaction with store id
					for(int i = 0; i < transactions.size(); i ++) {
						synchronized(jobState) {
							jobState.incrementSuccess();
						}
					}
				}
			}catch(Exception e) {
				e.printStackTrace();
				logger.error("Unable to insert transactions");
			}
		}catch(Exception e) {
			// catch any db exception log it and skip it for now
			e.printStackTrace();
		}
		logger.info("Done");
	}
	
	
	/**
	 * 
	 * @param bulk
	 * @param frequencyName
	 * @param transactionAmount
	 * @param frequencyDate
	 */
	private void updateFrequency(BulkWriteOperation bulk, String frequencyName, Double transactionAmount, Long frequencyDate){		
		logger.info("Search mongo for " + frequencyName + " date: " + frequencyDate);
		
		BasicDBObject searchObject = new BasicDBObject();
		DBObject modifiedObject =new BasicDBObject();
		
		searchObject.put("frequency_key",  frequencyName);
		
		modifiedObject.put("$inc", new BasicDBObject()
			.append("sum", transactionAmount)
		);	
		
		if(frequencyDate != null) {
			searchObject.put("frequency_key",  frequencyName);
			searchObject.put("date",  frequencyDate);
			
			modifiedObject.put("$inc", new BasicDBObject()
				.append("date", frequencyDate)
				.append("sum", transactionAmount)
			);
		}
		
		bulk.find(searchObject).
		upsert().update(modifiedObject);	
		logger.info("INSERTING TO MONGODB key " + frequencyName);
	}

}
