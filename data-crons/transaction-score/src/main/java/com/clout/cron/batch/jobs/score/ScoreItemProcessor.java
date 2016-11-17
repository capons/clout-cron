package com.clout.cron.batch.jobs.score;

import java.text.SimpleDateFormat;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemProcessor;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.jobitems.ScoreItem;
import com.clout.cron.batch.model.Transactions;

import net.spy.memcached.MemcachedClient;

/**
 * 
 * @author Ung
 *
 */
@Component("scoreItemProcessor")
@Scope("step")
@Transactional("readStoreTransactionManager")
public class ScoreItemProcessor implements ItemProcessor<Transactions, ScoreItem> { 
	private final static Logger logger = Logger.getLogger(ScoreItemProcessor.class);
	
	@Resource
	private MongoTemplate mongoTemplate;

	@Resource
	private MemcachedClient memcachedClient;
	
	@Resource
	private JobState jobState;
	
	
	@Override
	public ScoreItem process(Transactions item) throws Exception {
		logger.info("process item ");
		ScoreItem scoreItem = new ScoreItem();
		
		if(item.getStoreId() == null) {
			// or skip because these are the reject trans
			throw new CronJobException("Store id cannot be null");
		}

		// can't calculate this transaction
		if(item.getTransactionType().toLowerCase().equals("buy")) {
			logger.info("Unable to calculate this transaction");
			scoreItem.setUnqualified(true);
			return scoreItem;
		}

		SimpleDateFormat dayDateFormat = new SimpleDateFormat("yyyyMMdd");
		SimpleDateFormat monthDateFormat = new SimpleDateFormat("yyyyMM");

		scoreItem.setTransactionDateByDays(Long.parseLong(dayDateFormat.format(item.getStartDate())));
		scoreItem.setTransactionDateByMonths(Long.parseLong(monthDateFormat.format(item.getStartDate())));
		
		if(item.getStoreId() != null && item.getStoreId().intValue() > 0) {
			// store sum by days
			scoreItem.setUserStoreTransactionKeyByDays(item.getUserId().toString()+"-"+item.getStoreId().toString()+"-"+item.getTransactionType()+"-StoresByDays");
			scoreItem.setUserStoreTransactionKeyByMonths(item.getUserId().toString()+"-"+item.getStoreId().toString()+"-"+item.getTransactionType()+"-StoresByMonths");
			scoreItem.setUserStoreTransactionKeyByLifetime(item.getUserId().toString()+"-"+item.getStoreId().toString()+"-"+item.getTransactionType()+"-StoresByLifetime");
		}
		
		// used for chain spending lifetime as well as 90 day spending and 12 months spending
		if(item.getChainId() != null && item.getChainId().intValue() > 0) {
			scoreItem.setUserChainTransactionKeyByDays(item.getUserId().toString()+"-"+item.getChainId().toString()+"-"+item.getTransactionType()+"-ChainsByDays");
			scoreItem.setUserChainTransactionKeyByMonths(item.getUserId().toString()+"-"+item.getChainId().toString()+"-"+item.getTransactionType()+"-ChainsByMonths");
			scoreItem.setUserChainTransactionKeyByLifetime(item.getUserId().toString()+"-"+item.getChainId().toString()+"-"+item.getTransactionType()+"-ChainsByLifetime");
		}
		
		scoreItem.setUserTransactionKeyByDays(item.getUserId().toString()+"-"+item.getTransactionType()+"-UserSpendingByDays");
		scoreItem.setUserTransactionKeyByMonths(item.getUserId().toString()+"-"+item.getTransactionType()+"-UserSpendingByMonths");
		scoreItem.setUserTransactionKeyByLifetime(item.getUserId().toString()+"-"+item.getTransactionType()+"-UserSpendingByLifetime");
		
		if(item.getRelatedPromotionId() != null && item.getRelatedPromotionId().intValue() > 0) {
			scoreItem.setUserRelatedPromoTransactionKeyByDays(item.getUserId().toString()+"-"+item.getRelatedPromotionId().toString()+"-"+item.getTransactionType()+"-UserRelatedPromoByDays");
			scoreItem.setUserRelatedPromoTransactionKeyByMonths(item.getUserId().toString()+"-"+item.getRelatedPromotionId().toString()+"-"+item.getTransactionType()+"-UserRelatedPromoByMonths");
			scoreItem.setUserRelatedPromoTransactionKeyByLifetime(item.getUserId().toString()+"-"+item.getRelatedPromotionId().toString()+"-"+item.getTransactionType()+"-UserRelatedPromoByLifetime");
		}
		
		
		
		return scoreItem;
	}
		
	/*
	 * get the store id from the business id
	 */
	private String findFromMongo(String key) {
		
		Query query = new Query();
		query.addCriteria(Criteria.where("name").is(key));
		
		List<String> keys = mongoTemplate.find(query, String.class, "store_business_ids");
		
		if(keys != null) {
			logger.info("Found from mongo keys : " + keys.size());
			if(keys.get(0) != null) {
				return keys.get(0);
			}
		}
		logger.info("Key from mongo is null");
		return null;
	}

}
