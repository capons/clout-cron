package com.clout.cron.batch.jobs.cache;

import java.util.LinkedList;
import java.util.List;
import java.util.Queue;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.core.StepExecution;
import org.springframework.batch.core.annotation.BeforeStep;
import org.springframework.batch.item.ItemReader;
import org.springframework.batch.item.NonTransientResourceException;
import org.springframework.batch.item.ParseException;
import org.springframework.batch.item.UnexpectedInputException;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.mapper.StoreMapper;
import com.clout.cron.batch.model.Store;

/*
 * Only used for testing when DB has issues
 */
@Component("simpleItemReader")
@Scope("step")
public class SimpleItemReader implements ItemReader<Store> {
	
	private final static Logger logger = Logger.getLogger(SimpleItemReader.class);
	
	@Resource(name = "readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;
	
	private Queue<Store> queue;
	
	@Value("#{stepExecutionContext[fromId]}")
	private Integer fromId;
	
	@Value("#{stepExecutionContext[toId]}")
	private Integer toId;
	
	private StepExecution stepExecution;

	public void init(){
		queue = new LinkedList<Store>();
		
		String sql = "SELECT id, latitude, longitude, name, address_line_1, "
				+ "address_line_2, city, state, zipcode, _country_code, phone_number, "
				+ "website, public_store_key, key_words FROM stores "
				+ "WHERE id >= " + fromId
				+ " AND id <= " + toId;
		
		List<Store> stores = readStoreJdbcTemplate.query(sql, new StoreMapper(), new Object[]{});
		queue.addAll(stores);
	}
	
	@Override
	public Store read() throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException {
		if(queue == null) {
			logger.info("queue is null");
			init();
		}
		
		if(queue == null || queue.isEmpty()) {
			logger.info("queue is empty");
			return null;
		}
		
		logger.info("Removing a queue record for processing");
		
		return queue.remove();		
	}

	@BeforeStep
	public void setStepExecution(StepExecution stepExecution) {
	    this.stepExecution = stepExecution;
	}

}
