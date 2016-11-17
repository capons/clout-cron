package com.clout.test.tasklet;

import org.apache.log4j.Logger;
import org.junit.Test;

import com.clout.cron.batch.model.Users;

public class UserIdGeneratorTest {
	
	private final static Logger logger = Logger.getLogger(ValidatePartitionTest.class);

	
	@Test
	public void testUserIdGeneratorWithLengthGreaterThan10() {
		Users item = new Users();
		item.setId(1378765656);
		item.setCloutId(item.getId().toString());
		
		String paddedId = "CT" + item.getId();
		
		int pad = 10 - paddedId.length();
		
		StringBuilder builder = new StringBuilder();
		if(pad > 0) {
			
			for(int i = 0; i < pad; i ++) {
				builder.append("0");
			}

		}
		
		item.setCloutId("CT" + builder.toString() + item.getId());
		logger.info("Generated New ID " + item.getCloutId());

	}
	
	
	
	
	@Test
	public void testUserIdGeneratorWithLengthLessThan10() {
		Users item = new Users();
		item.setId(1);
		item.setCloutId(item.getId().toString());
		
		String paddedId = "CT" + item.getId();
		
		int pad = 10 - paddedId.length();
		
		StringBuilder builder = new StringBuilder();
		if(pad > 0) {
			
			for(int i = 0; i < pad; i ++) {
				builder.append("0");
			}

		}

		item.setCloutId("CT" + builder.toString() + item.getId());
		logger.info("Generated New ID " + item.getCloutId());

	}
}
