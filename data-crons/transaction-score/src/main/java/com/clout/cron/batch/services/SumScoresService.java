package com.clout.cron.batch.services;

import java.util.Arrays;
import java.util.Iterator;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.util.DateFormatUtil;
import com.mongodb.AggregationOutput;
import com.mongodb.BasicDBObject;
import com.mongodb.BasicDBObjectBuilder;
import com.mongodb.DBCollection;
import com.mongodb.DBCursor;
import com.mongodb.DBObject;
import com.mongodb.MongoException;

@Service("sumScoresService")
@Transactional
public class SumScoresService implements SumScoresServiceInterface {
	
	private final static Logger logger = Logger.getLogger(SumScoresService.class);

	@Resource
	private JdbcTemplate jobsJdbcTemplate;
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	/**
	 * For each collection, find all user records with date range
	 * 
					"spending_pattern_stores_by_days", "spending_pattern_chains_by_days", 
					"spending_pattern_users_by_days", "spending_pattern_promo_by_days", 
					"spending_pattern_store_by_months","spending_pattern_chain_by_months", 
					"spending_pattern_user_by_months", "spending_pattern_promo_by_months", 
					"spending_pattern_stores_by_lifetime", "spending_pattern_chain_by_lifetime", 
					"spending_pattern_user_by_lifetime", "spending_pattern_promo_by_lifetime"
	 * 
	 * @param collectionName
	 */
	@Override
	public void generateScores(String collectionName, Integer frequency, Integer calendarDateType ) {
		DBCollection collection = mongoTemplate.getCollection(collectionName);

		Query query = new Query().addCriteria(Criteria.where("date").gt(Long.parseLong(DateFormatUtil.moveBy(frequency, calendarDateType))).lte(Long.parseLong(DateFormatUtil.moveBy(frequency, calendarDateType))));
		 
		BasicDBObject match = new BasicDBObject("$match", new BasicDBObject());
		match.append("date", BasicDBObjectBuilder.start("$gte", Long.parseLong(DateFormatUtil.moveBy(frequency, calendarDateType))).add("$lte", Long.parseLong(DateFormatUtil.moveBy(frequency, calendarDateType))));
		 
		BasicDBObject group = new BasicDBObject(
			    "$group", new BasicDBObject("_id", "$frequency_key")
			     //.append("date", BasicDBObjectBuilder.start("$gte", Long.parseLong(DateFormatUtil.moveDaysBy(-90))).add("$lte", Long.parseLong(DateFormatUtil.moveDaysBy(0))));				 
			    .append("total", new BasicDBObject( "$sum", "$date")
			    )
		);
		
		// run aggregation
        AggregationOutput output = null;
        try {
        	output = collection.aggregate(Arrays.asList(new DBObject[] {match, group}));
        } catch (MongoException e) {
        	e.printStackTrace();
        }
		
	
        Iterator<DBObject> it = output.results().iterator();

        while ( it.hasNext()) {
        	BasicDBObject basicDBObject = (BasicDBObject) it.next();
        	String key = basicDBObject.get("_id").toString();
       		String sum = basicDBObject.get("sum").toString();
  
       		logger.info(key + " : " + sum);
        }
        
        // insert final sum to database
	}

	@Override
	public void generateScores(String collectionName) {
		DBCollection collection = mongoTemplate.getCollection(collectionName);
		
		DBCursor result = collection.find();
		
		while(result.hasNext()) {
			DBObject object = result.next();
			System.out.println(object.toString());
		}
	
		// insert final sum to database
	}

}
