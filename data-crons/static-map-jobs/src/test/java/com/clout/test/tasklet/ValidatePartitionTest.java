package com.clout.test.tasklet;

import java.util.HashMap;
import java.util.Map;

import org.apache.log4j.Logger;
import org.junit.Test;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.Users;

import junit.framework.Assert;

public class ValidatePartitionTest {
	private final static Logger logger = Logger.getLogger(ValidatePartitionTest.class);
	
	private int quota = 1010;
	
	private int lastProcessedKey = 3;
	
	private int gridSize = 3;
	
	@Test
	public void testPartitionLogic() {

		int remaining = quota % gridSize;
		
		logger.debug( "remaining " + remaining);

		int chunkSize = quota / gridSize;
		
		if(remaining > 0 ) {
			chunkSize++;
		}
		logger.debug("chunkSize " + chunkSize);
		
		int total = 0;
		for (int i = 0; i < gridSize; i++) {

			logger.debug("\nStarting : Thread" + i);
			
			int fromId = lastProcessedKey + (i * chunkSize) + 1;
			int toId = lastProcessedKey + ((i + 1) * chunkSize);
			
			total += chunkSize;
			
			if(total > quota) {
				toId = quota;
			}

			logger.debug("totalIds " + total);
			logger.debug("fromId : " + fromId);
			logger.debug("toId : " + toId);

			logger.info("Thread " + i + " from " + fromId + " to " + toId);
		}
	}

	@Test
	public void testThreadSafe() {
		final JobState state = new JobState();

		Runnable r1 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementAlreadyExist();
			}
		};

		Runnable r2 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementAlreadyExist();
			}
		};

		Runnable r3 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementSuccess();
			}
		};

		Runnable r4 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementSuccess();
			}
		};
		
		Runnable r5 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementFailed();
			}
		};
		
		Runnable r6 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementFailed();
			}
		};
		
		Runnable r7 = () -> {
			for(int i = 0; i < 100; i ++) {
				state.incrementSuccess();
			}
		};
		
		
		r1.run();
		r2.run();
		r3.run();
		r4.run();
		r5.run();
		r6.run();
		r7.run();
		
		
		Assert.assertEquals(200, state.getAlreadyExist());
		Assert.assertEquals(300,state.getSuccess());
		Assert.assertEquals(200,state.getFailed());
	}
	
	@Test
	public void testURLFormat() {
		Map<String, String> arg0 = new HashMap<String, String>();
		arg0.put("name", "Love Target");
		arg0.put("address_line1", "123 holy st apt #2");
		arg0.put("address_line2", "");
		arg0.put("city", "los angeles");
		arg0.put("state", "CA");
		arg0.put("zipcode", "90036");
		arg0.put("_country_code", "USA");
		
		StringBuilder builder = new StringBuilder(arg0.get("name") == null? "" : arg0.get("name").replaceAll("[\\s|\\W)]+", "-"));
		builder.append("=");
		builder.append(arg0.get("address_line1") == null? "" : arg0.get("address_line1").replaceAll("[\\s|\\W]+", "-"));
		builder.append("-");
		builder.append(arg0.get("address_line2") == null? "" :arg0.get("address_line2").replace("[\\s|\\W]+", "-"));
		builder.append("-");
		builder.append(arg0.get("city") == null? "" :arg0.get("city").replace("([\\s|\\W]+)", "-"));
		builder.append("-");
		builder.append(arg0.get("state") == null? "" :arg0.get("state").replace( "([\\s|\\W]+)", "-"));
		builder.append("-");		
		builder.append(arg0.get("zipcode") == null? "" :arg0.get("zipcode").replace("([\\s|\\W]+)", "-"));
		builder.append("-");
		builder.append(arg0.get("_country_code") == null? "" :arg0.get("_country_code").replace("([\\s|\\W]+)", "-"));
		builder.append(".html");
		
		System.out.println(builder.toString());
	}
	

}
