package com.clout.test.tasklet;

import java.util.Arrays;
import java.util.Iterator;
import java.util.List;

import javax.annotation.Resource;

import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.test.context.ContextConfiguration;
import org.springframework.test.context.junit4.SpringJUnit4ClassRunner;

import com.clout.cron.batch.model.Store;
import com.mongodb.AggregationOutput;
import com.mongodb.BasicDBList;
import com.mongodb.BasicDBObject;
import com.mongodb.BasicDBObjectBuilder;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;
import com.mongodb.DBObject;
import com.mongodb.MongoException;

@RunWith(SpringJUnit4ClassRunner.class)
@ContextConfiguration({"classpath:/spring/batch/jobs/common-context.xml",  "classpath:/spring/batch/database.xml", "classpath:/spring/batch/jobs/transaction-match.xml"})
public class MongoTest {
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Test
	public void testSimpleWrite() {
		DBCollection collection = mongoTemplate.getCollection("store_keys");
	}

	@Test
	public void testStoreUpdate() {
		Query query = new Query();
		query.addCriteria(Criteria.where("name").is("khim1"));

		List<Store> stores = mongoTemplate.find(query, Store.class, "bname");
		
		if(stores != null) {
			for(Store s : stores) {
				System.out.println("store city " + s.getCity());
				System.out.println("store name " + s.getName());
				System.out.println("store state " + s.getState());
				System.out.println("store zipcode " + s.getZipcode());
				System.out.println("store address " + s.getAddressLine1());
			}
		}
		

		
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		

		for(int i = 0; i < 3; i ++) {

			BasicDBObject searchObject = new BasicDBObject();
			searchObject.put("name", "khim" + i);

					
			DBObject modifiedObject =new BasicDBObject();
			modifiedObject.put("$set", new BasicDBObject()
				.append("name", "khim" + i)
				.append("address_line_1", "123 khim dr suite " + i)
				.append("address_line_2", "KHIM " + i)
				.append("city", "los angeles")
				.append("state", "ca")
			);
					
			bulk.find(searchObject).
			upsert().update(modifiedObject);
		}
		
		BulkWriteResult writeResult = bulk.execute();

		stores = mongoTemplate.find(query, Store.class, "bname");
		
		for(Store s : stores) {
			System.out.println("store city " + s.getCity());
		}
	}
	
	@Test
	public void testFindFromMongo() {
		String testString = "james j davis elementary school,29940,seabrook,sc,364 kean neck rd";
		Query query = new Query();
		query.addCriteria(Criteria.where("name").is(testString));
		
		List<String> keys = mongoTemplate.find(query, String.class, "store_keys");
		
		if(keys != null) {
			System.out.println("Found from mongo keys : " + keys.size());
			if(keys.size() > 0 && keys.get(0) != null) {
				System.out.println(" key " + keys.get(0));
			}
		}
		System.out.println("Key from mongo is null");
	}
	
	@Test
	public void testMongoUpsertStore() {
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		BasicDBObject searchObject = new BasicDBObject();
		searchObject.put("store_id",  new Long(1));

		DBObject modifiedObject =new BasicDBObject();
		modifiedObject.put("$set", new BasicDBObject()
			.append("public_store_key", "test")
			.append("key_words", "test")
		);
				
		bulk.find(searchObject).
		upsert().update(modifiedObject);
	
		BulkWriteResult writeResult = bulk.execute();
	}
	
	/**
	 * creates array of {frequency_key: xxxxx, dates: {20160501: 3}, {20160503: 4}}
	 */
	@Test
	public void testMongoUpsert() {
		DBCollection collection = mongoTemplate.getCollection("spending_pattern_promo_by_lifetime");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();

		DBObject modifiedObject =new BasicDBObject();

	    BasicDBObject query = new BasicDBObject();
	    query.put("frequency_key", "1-deposit-UserSpendingByLifetime");

	    BasicDBObject incValue = new BasicDBObject("dates.20160508", 5f);	    

	    modifiedObject.put("$set",  new BasicDBObject().append("frequency_key", "1-deposit-UserSpendingByLifetime"));
	    modifiedObject.put("$inc", incValue);
	    
	    // single update works
	    //collection.update(query, modifiedObject, true, false, WriteConcern.SAFE);

	    // multi update
		bulk.find(query).
		upsert().update(modifiedObject);	
		
		BulkWriteResult writeResult = bulk.execute();
	}
	
	/*
	 * 
	 */
	@Test
	public void testMongoUpsert2() {
		DBCollection collection = mongoTemplate.getCollection("spending_pattern_promo_by_lifetime");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();

		DBObject modifiedObject =new BasicDBObject();

	    BasicDBObject query = new BasicDBObject();
	    query.put("frequency_key", "1-deposit-UserSpendingByLifetime");

	    BasicDBList list = new BasicDBList();
	    BasicDBObject incValue = new BasicDBObject("date", "20160508").append("value", 5f);
	    list.add(incValue);
	    //query.put("$push", list);

	    modifiedObject.put("$set",  new BasicDBObject().append("frequency_key", "1-deposit-UserSpendingByLifetime"));
	    modifiedObject.put("$push", list);
	    modifiedObject.put("$inc", new BasicDBObject("date", "20160508").append("value", 5f));
	    
	    // multi update
		bulk.find(query).
		upsert().update(modifiedObject);	
		
		BulkWriteResult writeResult = bulk.execute();
	}
	
	
	@Test
	public void testFlattenScore() {
		DBCollection collection = mongoTemplate.getCollection("spending_pattern_promo_by_lifetime");

		DBObject object = BasicDBObjectBuilder.start("$gte", "20160501").add("$lte", "20160509").get();
		
		BasicDBObject match = new BasicDBObject("$match", new BasicDBObject());
		match.append("dates.date", object);
		 
		
		BasicDBObject group = new BasicDBObject(
			    "$group", new BasicDBObject("_id", "$frequency_key")
			     //.append("date", BasicDBObjectBuilder.start("$gte", Long.parseLong(DateFormatUtil.moveDaysBy(-90))).add("$lte", Long.parseLong(DateFormatUtil.moveDaysBy(0))));				 
			    .append("total", new BasicDBObject( "$sum", "$date")
			    )
		);
		
		/*
		 *     MongoClient mongoClient = null;

    try {
        mongoClient = new MongoClient("localhost", 27017);
    } catch (UnknownHostException e) {}

    DB db = mongoClient.getDB("test");

    DBCollection collection = db.getCollection("user");

    DBObject match = new BasicDBObject("$match", new BasicDBObject("id", "11"));
    DBObject groupFields = new BasicDBObject("_id", "$name");
    groupFields.put("name", new BasicDBObject("$addToSet", "$name"));
    DBObject group = new BasicDBObject("$group", groupFields);

    AggregationOutput output = collection.aggregate(match, group);

    Iterable<DBObject> itResult = output.results();

    for (DBObject dbo : itResult) {
        List<String> items = (List<String>) dbo.get("name");
        for(String item : items){
            System.out.println(item);
        }

    }
		 */

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
       		String sum = basicDBObject.get("total").toString();
  
       		System.out.println(key + " : " + sum);
        }
	}

}
